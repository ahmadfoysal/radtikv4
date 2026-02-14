#!/usr/bin/env python3
"""
RadTik FreeRADIUS Voucher Synchronization
Syncs vouchers from Laravel to FreeRADIUS radcheck/radreply tables
"""

import requests
import sqlite3
import configparser
import sys
import os
from datetime import datetime

# Load configuration
config = configparser.ConfigParser()
config_path = os.path.join(os.path.dirname(__file__), 'config.ini')

if not os.path.exists(config_path):
    print(f"❌ Configuration file not found: {config_path}", file=sys.stderr)
    sys.exit(1)

config.read(config_path)

LARAVEL_API = config['laravel']['api_url']
API_SECRET = config['laravel']['api_secret']
RADIUS_DB = config['radius']['db_path']


def parse_rate_limit(rate_limit):
    """
    Parse rate limit string (e.g., "10M/10M") to bytes per second
    Supports: K (kilobits), M (megabits), G (gigabits)
    """
    try:
        parts = rate_limit.upper().split('/')
        
        def to_bytes(value):
            if 'G' in value:
                return int(value.replace('G', '')) * 1000000000
            elif 'M' in value:
                return int(value.replace('M', '')) * 1000000
            elif 'K' in value:
                return int(value.replace('K', '')) * 1000
            else:
                return int(value)
        
        upload = to_bytes(parts[0].strip())
        download = to_bytes(parts[1].strip()) if len(parts) > 1 else upload
        
        return upload, download
    except Exception as e:
        print(f"⚠️  Failed to parse rate limit '{rate_limit}': {e}", file=sys.stderr)
        return 10000000, 10000000  # Default 10Mbps


def parse_validity(validity_str):
    """
    Parse validity string to seconds
    Formats: "1h", "24h", "1d", "7d", "30d", or raw seconds
    """
    try:
        validity_str = str(validity_str).strip().lower()
        
        if validity_str.endswith('h'):
            return int(validity_str[:-1]) * 3600
        elif validity_str.endswith('d'):
            return int(validity_str[:-1]) * 86400
        elif validity_str.endswith('m'):
            return int(validity_str[:-1]) * 60
        else:
            return int(validity_str)
    except Exception as e:
        print(f"⚠️  Failed to parse validity '{validity_str}': {e}", file=sys.stderr)
        return 86400  # Default 24 hours


def update_radcheck(cursor, voucher):
    """Update radcheck table with user credentials and MAC binding"""
    username = voucher['username']
    password = voucher['password']
    mac_address = voucher.get('mac_address')
    
    # Delete existing entries
    cursor.execute("DELETE FROM radcheck WHERE username = ?", (username,))
    
    # Insert password
    cursor.execute("""
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES (?, 'Cleartext-Password', ':=', ?)
    """, (username, password))
    
    # Add MAC binding if present
    if mac_address:
        cursor.execute("""
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES (?, 'Calling-Station-Id', '==', ?)
        """, (username, mac_address))


def update_radreply(cursor, voucher):
    """Update radreply table with profile attributes"""
    username = voucher['username']
    
    # Delete existing entries
    cursor.execute("DELETE FROM radreply WHERE username = ?", (username,))
    
    # Rate limit (bandwidth)
    if voucher.get('rate_limit'):
        upload, download = parse_rate_limit(voucher['rate_limit'])
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'WISPr-Bandwidth-Max-Up', ':=', ?)
        """, (username, str(upload)))
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'WISPr-Bandwidth-Max-Down', ':=', ?)
        """, (username, str(download)))
    
    # Session timeout (validity)
    if voucher.get('validity'):
        timeout = parse_validity(voucher['validity'])
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'Session-Timeout', ':=', ?)
        """, (username, str(timeout)))
    
    # Idle timeout (optional)
    if voucher.get('idle_timeout'):
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'Idle-Timeout', ':=', ?)
        """, (username, str(voucher['idle_timeout'])))
    
    # Simultaneous-Use (shared users)
    shared_users = voucher.get('shared_users', 1)
    cursor.execute("""
        INSERT INTO radreply (username, attribute, op, value)
        VALUES (?, 'Simultaneous-Use', ':=', ?)
    """, (username, str(shared_users)))


def sync_vouchers():
    """Main synchronization function"""
    try:
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Starting voucher sync...")
        
        # Call Laravel API
        response = requests.post(
            f"{LARAVEL_API}/sync/vouchers",
            headers={"X-RADIUS-SECRET": API_SECRET},
            json={},
            timeout=30
        )
        response.raise_for_status()
        
        data = response.json()
        vouchers = data.get('vouchers', [])
        
        if not vouchers:
            print("ℹ️  No vouchers to sync")
            return
        
        # Connect to FreeRADIUS database
        conn = sqlite3.connect(RADIUS_DB)
        cursor = conn.cursor()
        
        # Sync each voucher
        synced_count = 0
        for voucher in vouchers:
            try:
                update_radcheck(cursor, voucher)
                update_radreply(cursor, voucher)
                synced_count += 1
            except Exception as e:
                print(f"⚠️  Failed to sync voucher '{voucher.get('username')}': {e}", file=sys.stderr)
        
        # Commit all changes
        conn.commit()
        conn.close()
        
        print(f"✅ Successfully synced {synced_count}/{len(vouchers)} vouchers")
        
    except requests.exceptions.RequestException as e:
        print(f"❌ API request failed: {e}", file=sys.stderr)
        sys.exit(1)
    except sqlite3.Error as e:
        print(f"❌ Database error: {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"❌ Unexpected error: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    sync_vouchers()
