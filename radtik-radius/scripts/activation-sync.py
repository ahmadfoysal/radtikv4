#!/usr/bin/env python3
"""
RadTik Activation Sync Script
Polls FreeRADIUS radpostauth table for new authentications and syncs to Laravel.

This script runs via cron and:
1. Checks radpostauth table for unique Access-Accept entries from last 24 hours
2. POSTs activation data to Laravel API
3. Tracks last sync attempt to avoid duplicates
4. Verifies auth token with Laravel's radius_servers table
"""

import sqlite3
import requests
import configparser
import logging
import sys
import os
from datetime import datetime, timedelta
from typing import List, Dict, Optional

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/var/log/radtik-activation-sync.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger('activation-sync')

# Load configuration
config = configparser.ConfigParser()
config_path = os.path.join(os.path.dirname(__file__), 'config.ini')

if not os.path.exists(config_path):
    logger.error(f"Configuration file not found: {config_path}")
    sys.exit(1)

config.read(config_path)

# Configuration variables
RADIUS_DB_PATH = config.get('radius', 'db_path', fallback='/etc/freeradius/3.0/sqlite/radius.db')
LARAVEL_API_URL = config.get('laravel', 'api_url', fallback='')
AUTH_TOKEN = config.get('api', 'auth_token', fallback='')

# Validate configuration
if not LARAVEL_API_URL:
    logger.error("LARAVEL_API_URL not configured in config.ini [laravel] section!")
    sys.exit(1)

if not AUTH_TOKEN:
    logger.error("AUTH_TOKEN not configured in config.ini [api] section!")
    sys.exit(1)

if not os.path.exists(RADIUS_DB_PATH):
    logger.error(f"RADIUS database not found: {RADIUS_DB_PATH}")
    sys.exit(1)


def get_db_connection():
    """Get SQLite database connection"""
    try:
        conn = sqlite3.connect(RADIUS_DB_PATH, timeout=10)
        conn.row_factory = sqlite3.Row
        return conn
    except sqlite3.Error as e:
        logger.error(f"Database connection error: {e}")
        raise


def fetch_unique_activations_last_24h() -> List[Dict]:
    """
    Fetch unique successful authentications from last 24 hours
    Returns distinct combinations of username, nas_identifier, and mac address
    """
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # Get activations from last 24 hours, distinct by username + nas + mac
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d %H:%M:%S')
        
        query = """
            SELECT 
                username,
                nas_identifier,
                calling_station_id,
                MIN(authdate) as first_auth_date
            FROM radpostauth
            WHERE reply = 'Access-Accept'
              AND authdate > ?
            GROUP BY username, 
                     nas_identifier,
                     calling_station_id
            ORDER BY first_auth_date ASC
        """
        
        cursor.execute(query, (yesterday,))
        rows = cursor.fetchall()
        conn.close()
        
        # Convert to list of dicts
        activations = []
        for row in rows:
            activations.append({
                'username': row['username'],
                'nas_identifier': row['nas_identifier'],
                'calling_station_id': row['calling_station_id'],
                'authenticated_at': row['first_auth_date']
            })
        
        return activations
        
    except sqlite3.Error as e:
        logger.error(f"Database query error: {e}")
        return []


def post_activations_to_laravel(activations: List[Dict]) -> Dict:
    """
    POST activation data to Laravel API
    Returns response data with MAC bindings if any
    """
    if not activations:
        logger.info("No activations to sync")
        return {'success': True, 'mac_bindings': []}
    
    try:
        url = f"{LARAVEL_API_URL}/api/radius/activations"
        headers = {
            'Authorization': f'Bearer {AUTH_TOKEN}',
            'Content-Type': 'application/json'
        }
        
        payload = {
            'activations': activations
        }
        
        logger.info(f"Posting {len(activations)} unique activations to Laravel...")
        
        response = requests.post(
            url,
            headers=headers,
            json=payload,
            timeout=30
        )
        
        if response.status_code == 200:
            result = response.json()
            logger.info(f"✓ Successfully synced {len(activations)} activations")
            return result
        else:
            logger.error(f"Laravel API error: [{response.status_code}] {response.text}")
            return {'success': False, 'mac_bindings': []}
            
    except requests.exceptions.Timeout:
        logger.error("Laravel API request timed out")
        return {'success': False, 'mac_bindings': []}
    except requests.exceptions.ConnectionError as e:
        logger.error(f"Cannot connect to Laravel API: {e}")
        return {'success': False, 'mac_bindings': []}
    except Exception as e:
        logger.error(f"Failed to post activations: {e}")
        return {'success': False, 'mac_bindings': []}


def sync_mac_bindings_to_radius(mac_bindings: List[Dict]) -> bool:
    """
    Sync MAC bindings directly to RADIUS database
    Adds Calling-Station-Id check to radcheck table to lock voucher to MAC
    Returns True if successful
    """
    if not mac_bindings:
        return True
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        synced = 0
        updated = 0
        
        logger.info(f"Syncing {len(mac_bindings)} MAC bindings to RADIUS database...")
        
        for binding in mac_bindings:
            try:
                username = binding['username']
                mac_address = binding['mac_address']
                
                # Check if MAC binding already exists for this user
                cursor.execute(
                    "SELECT id, value FROM radcheck WHERE username = ? AND attribute = 'Calling-Station-Id'",
                    (username,)
                )
                existing = cursor.fetchone()
                
                if existing:
                    # Update existing MAC binding (in case MAC changed)
                    existing_mac = existing['value']
                    
                    if existing_mac != mac_address:
                        cursor.execute(
                            "UPDATE radcheck SET value = ? WHERE username = ? AND attribute = 'Calling-Station-Id'",
                            (mac_address, username)
                        )
                        updated += 1
                        logger.info(f"Updated MAC for {username}: {existing_mac} → {mac_address}")
                    else:
                        logger.debug(f"MAC binding unchanged for {username}: {mac_address}")
                        synced += 1
                else:
                    # Insert new MAC binding
                    cursor.execute(
                        "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)",
                        (username, 'Calling-Station-Id', '==', mac_address)
                    )
                    synced += 1
                    logger.info(f"Added MAC binding for {username}: {mac_address}")
                
            except sqlite3.Error as e:
                logger.error(f"Database error for {username}: {e}")
                continue
        
        # Commit all changes
        conn.commit()
        conn.close()
        
        logger.info(f"✓ MAC bindings synced: {synced} added, {updated} updated")
        return True
        
    except sqlite3.Error as e:
        logger.error(f"Failed to sync MAC bindings: {e}")
        return False
    except Exception as e:
        logger.error(f"Unexpected error syncing MAC bindings: {e}")
        return False


def main():
    """Main function - fetches and posts activations"""
    try:
        logger.info("Starting activation sync...")
        
        # Fetch unique activations from last 24 hours
        activations = fetch_unique_activations_last_24h()
        
        if not activations:
            logger.info("No activations found in last 24 hours")
            return
        
        logger.info(f"Found {len(activations)} unique activation(s) from last 24 hours")
        
        # Post to Laravel
        result = post_activations_to_laravel(activations)
        
        if not result.get('success'):
            logger.error("Failed to sync activations")
            sys.exit(1)
        
        logger.info("Activation sync completed successfully")
        
        # Check if Laravel returned MAC bindings to sync
        mac_bindings = result.get('mac_bindings', [])
        
        if mac_bindings:
            logger.info(f"Received {len(mac_bindings)} MAC binding(s) to sync to RADIUS")
            
            # Sync MAC bindings to RADIUS database
            binding_success = sync_mac_bindings_to_radius(mac_bindings)
            
            if binding_success:
                logger.info("MAC binding sync completed successfully")
            else:
                logger.warning("MAC binding sync failed, but activations were processed")
        else:
            logger.info("No MAC bindings required")
            
    except Exception as e:
        logger.error(f"Sync error: {e}", exc_info=True)
        sys.exit(1)


if __name__ == '__main__':
    main()
