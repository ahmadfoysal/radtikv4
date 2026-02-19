#!/bin/bash

###############################################################################
# RadTik FreeRADIUS Bootstrap Installer
# This script clones the repository and runs the complete installation
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REPO_URL="${RADTIK_REPO_URL:-https://github.com/ahmadfoysal/radtik-radius.git}"
REPO_BRANCH="${RADTIK_BRANCH:-main}"
TEMP_DIR="/tmp/radtik-install-$$"
INSTALL_DIR="/opt/radtik-radius"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run with sudo${NC}" 
   exit 1
fi

clear
echo -e "${GREEN}"
cat << "EOF"
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║   RadTik FreeRADIUS Bootstrap Installation Script        ║
║                                                           ║
║  Automated installation from GitHub repository           ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"
echo ""

###############################################################################
# Install Git if not available
###############################################################################
echo -e "${YELLOW}Checking dependencies...${NC}"
if ! command -v git &> /dev/null; then
    echo -e "${GREEN}✓${NC} Installing git..."
    apt-get update -qq
    apt-get install -y git
else
    echo -e "${GREEN}✓${NC} Git is already installed"
fi
echo ""

###############################################################################
# Clone or Update Repository
###############################################################################
if [ -d "$INSTALL_DIR" ]; then
    echo -e "${YELLOW}Installation directory exists. Updating...${NC}"
    cd "$INSTALL_DIR"
    
    # Check if it's a git repository
    if [ -d .git ]; then
        echo -e "${GREEN}✓${NC} Pulling latest changes..."
        git pull origin $REPO_BRANCH || echo -e "${YELLOW}⚠${NC} Could not pull updates, continuing with existing files..."
    else
        echo -e "${YELLOW}⚠${NC} Not a git repository, backing up and cloning fresh..."
        mv "$INSTALL_DIR" "$INSTALL_DIR.backup.$(date +%s)"
        mkdir -p "$INSTALL_DIR"
        git clone -b $REPO_BRANCH "$REPO_URL" "$TEMP_DIR"
        cp -r "$TEMP_DIR"/* "$INSTALL_DIR/"
        rm -rf "$TEMP_DIR"
    fi
else
    echo -e "${YELLOW}Cloning repository...${NC}"
    mkdir -p "$INSTALL_DIR"
    git clone -b $REPO_BRANCH "$REPO_URL" "$TEMP_DIR"
    
    # Copy repository contents directly (no subdirectory)
    echo -e "${GREEN}✓${NC} Copying installation files..."
    cp -r "$TEMP_DIR"/* "$INSTALL_DIR/"
    
    rm -rf "$TEMP_DIR"
    echo -e "${GREEN}✓${NC} Repository cloned successfully"
fi
echo ""

###############################################################################
# Run Installation Script
###############################################################################
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Starting FreeRADIUS Installation${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

if [ ! -f "$INSTALL_DIR/install.sh" ]; then
    echo -e "${RED}✗${NC} Installation script not found at $INSTALL_DIR/install.sh"
    exit 1
fi

# Make install script executable
chmod +x "$INSTALL_DIR/install.sh"

# Run the installation
cd "$INSTALL_DIR"
bash "$INSTALL_DIR/install.sh"

###############################################################################
# Display Configuration Summary
###############################################################################
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Configuration Details${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Extract the API token from config.ini
if [ -f "$INSTALL_DIR/scripts/config.ini" ]; then
    API_TOKEN=$(grep "^auth_token" "$INSTALL_DIR/scripts/config.ini" | cut -d'=' -f2 | tr -d ' ')
    echo -e "${YELLOW}API Authentication Token:${NC}"
    echo -e "${GREEN}$API_TOKEN${NC}"
else
    echo -e "${RED}⚠ Could not find config.ini to retrieve API token${NC}"
fi
echo ""

# Extract RADIUS shared secret from clients.conf
if [ -f "/etc/freeradius/3.0/clients.conf" ]; then
    RADIUS_SECRET=$(grep -A 5 "^client radtik" /etc/freeradius/3.0/clients.conf | grep "secret" | cut -d'=' -f2 | tr -d ' ')
    echo -e "${YELLOW}RADIUS Shared Secret:${NC}"
    echo -e "${GREEN}$RADIUS_SECRET${NC}"
else
    echo -e "${RED}⚠ Could not find clients.conf to retrieve RADIUS secret${NC}"
fi
echo ""

echo -e "${YELLOW}Quick Test Commands:${NC}"
if [ -n "$API_TOKEN" ]; then
    echo "  • Test API: curl -H 'Authorization: Bearer $API_TOKEN' http://localhost:5000/health"
fi
if [ -n "$RADIUS_SECRET" ]; then
    echo "  • Test RADIUS: radtest testuser testpass localhost 0 $RADIUS_SECRET"
fi
echo ""

echo -e "${BLUE}Laravel Configuration:${NC}"
echo "Add this RADIUS server in Laravel admin panel:"
echo "  - Host: $(hostname -I | awk '{print $1}')"
if [ -n "$API_TOKEN" ]; then
    echo "  - Auth Token: $API_TOKEN"
fi
if [ -n "$RADIUS_SECRET" ]; then
    echo "  - Shared Secret: $RADIUS_SECRET"
fi
echo "  - Port: 1812"
echo ""
