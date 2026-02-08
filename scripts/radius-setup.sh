#!/bin/bash

#############################################################################
# FreeRADIUS + SQLite Automated Setup Script for RadTik
# 
# Description: Automated installation and configuration of FreeRADIUS with
#              SQLite backend, optimized for RadTik voucher authentication
#
# OS Support:  Ubuntu 22.04 LTS
# Version:     1.0.0
# Author:      RadTik Team
# License:     MIT
#############################################################################

set -e  # Exit on any error

#############################################################################
# CONFIGURATION VARIABLES
#############################################################################

RADIUS_VERSION="3.0"
SQLITE_DIR="/etc/freeradius/${RADIUS_VERSION}/sqlite"
RADIUS_DB="${SQLITE_DIR}/radius.db"
SQL_CONFIG="/etc/freeradius/${RADIUS_VERSION}/mods-available/sql"
CLIENTS_CONFIG="/etc/freeradius/${RADIUS_VERSION}/clients.conf"
DEFAULT_SITE="/etc/freeradius/${RADIUS_VERSION}/sites-enabled/default"

# Default client configuration
DEFAULT_SECRET="$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)"
CLIENT_NAME="radtik"
CLIENT_IPADDR="0.0.0.0/0"

# Test user credentials
TEST_USERNAME="testuser"
TEST_PASSWORD="testpass"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

#############################################################################
# UTILITY FUNCTIONS
#############################################################################

print_header() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BLUE}$1${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_step() {
    echo ""
    echo -e "${CYAN}▶${NC} ${YELLOW}$1${NC}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

check_os() {
    if [[ ! -f /etc/os-release ]]; then
        print_error "Cannot determine OS version"
        exit 1
    fi
    
    . /etc/os-release
    
    if [[ "$ID" != "ubuntu" ]] || [[ "$VERSION_ID" != "22.04" ]]; then
        print_warning "This script is tested on Ubuntu 22.04 LTS"
        print_info "Current OS: $ID $VERSION_ID"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

#############################################################################
# INSTALLATION FUNCTIONS
#############################################################################

install_packages() {
    print_step "Step 1/12: Installing FreeRADIUS and dependencies..."
    
    print_info "Updating package list..."
    apt update -qq
    
    print_info "Installing packages..."
    DEBIAN_FRONTEND=noninteractive apt install -y freeradius freeradius-utils sqlite3 curl > /dev/null 2>&1
    
    # Verify installation
    if command -v freeradius &> /dev/null; then
        local version=$(freeradius -v 2>&1 | head -n 1 | awk '{print $2}')
        print_success "FreeRADIUS ${version} installed successfully"
    else
        print_error "FreeRADIUS installation failed"
        exit 1
    fi
}

create_database() {
    print_step "Step 2/12: Creating SQLite database..."
    
    # Stop FreeRADIUS if running
    systemctl stop freeradius 2>/dev/null || true
    
    # Create directory
    print_info "Creating database directory..."
    mkdir -p "${SQLITE_DIR}"
    
    # Create database file
    print_info "Initializing database..."
    sqlite3 "${RADIUS_DB}" ".quit"
    
    print_success "Database directory and file created"
}

import_schema() {
    print_step "Step 3/12: Importing FreeRADIUS schema..."
    
    local schema_file="/etc/freeradius/${RADIUS_VERSION}/mods-config/sql/main/sqlite/schema.sql"
    
    if [[ ! -f "$schema_file" ]]; then
        print_error "Schema file not found: $schema_file"
        exit 1
    fi
    
    sqlite3 "${RADIUS_DB}" < "$schema_file"
    
    # Verify tables were created
    local table_count=$(sqlite3 "${RADIUS_DB}" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null)
    
    if [[ $table_count -gt 0 ]]; then
        print_success "Schema imported successfully ($table_count tables created)"
    else
        print_error "Schema import failed"
        exit 1
    fi
}

fix_permissions() {
    print_step "Step 4/12: Fixing database permissions (CRITICAL)..."
    
    print_info "Setting ownership to freerad:freerad..."
    chown -R freerad:freerad "${SQLITE_DIR}"
    
    print_info "Setting directory permissions..."
    chmod 775 "${SQLITE_DIR}"
    
    print_info "Setting database file permissions..."
    chmod 664 "${RADIUS_DB}"
    
    # Verify permissions
    local owner=$(stat -c '%U:%G' "${RADIUS_DB}")
    if [[ "$owner" == "freerad:freerad" ]]; then
        print_success "Permissions set correctly"
    else
        print_warning "Permission verification failed. Owner: $owner"
    fi
}

enable_wal_mode() {
    print_step "Step 5/12: Enabling SQLite WAL mode..."
    
    print_info "Setting journal mode to WAL..."
    sqlite3 "${RADIUS_DB}" "PRAGMA journal_mode=WAL;" > /dev/null
    
    print_info "Setting busy timeout to 30 seconds..."
    sqlite3 "${RADIUS_DB}" "PRAGMA busy_timeout=30000;" > /dev/null
    
    # Verify WAL mode
    local journal_mode=$(sqlite3 "${RADIUS_DB}" "PRAGMA journal_mode;" 2>/dev/null)
    if [[ "$journal_mode" == "wal" ]]; then
        print_success "WAL mode enabled (prevents database locks)"
    else
        print_warning "WAL mode verification failed. Mode: $journal_mode"
    fi
}

configure_sql_module() {
    print_step "Step 6/12: Configuring SQL module..."
    
    if [[ ! -f "$SQL_CONFIG" ]]; then
        print_error "SQL config file not found: $SQL_CONFIG"
        exit 1
    fi
    
    # Backup original config
    cp "$SQL_CONFIG" "${SQL_CONFIG}.backup"
    
    print_info "Setting driver to rlm_sql_sqlite..."
    sed -i 's/^[#[:space:]]*driver[[:space:]]*=.*/\tdriver = "rlm_sql_sqlite"/' "$SQL_CONFIG"
    
    print_info "Setting dialect to sqlite..."
    sed -i 's/^[#[:space:]]*dialect[[:space:]]*=.*/\tdialect = "sqlite"/' "$SQL_CONFIG"
    
    # Ensure sqlite filename is set correctly
    print_info "Configuring database path..."
    sed -i "/sqlite {/,/}/ s|filename = .*|filename = \"${RADIUS_DB}\"|" "$SQL_CONFIG"
    
    print_success "SQL module configured"
}

enable_sql_module() {
    print_step "Step 7/12: Enabling SQL module..."
    
    local mods_enabled="/etc/freeradius/${RADIUS_VERSION}/mods-enabled"
    local sql_link="${mods_enabled}/sql"
    
    # Remove existing link if present
    rm -f "$sql_link"
    
    # Create symbolic link
    ln -s "$SQL_CONFIG" "$sql_link"
    
    if [[ -L "$sql_link" ]]; then
        print_success "SQL module enabled"
    else
        print_error "Failed to enable SQL module"
        exit 1
    fi
}

configure_client() {
    print_step "Step 8/12: Configuring RadTik client access..."
    
    # Backup original config
    cp "$CLIENTS_CONFIG" "${CLIENTS_CONFIG}.backup"
    
    # Check if client already exists
    if grep -q "client ${CLIENT_NAME}" "$CLIENTS_CONFIG"; then
        print_info "Client '$CLIENT_NAME' already exists, updating..."
        # Remove existing client block
        sed -i "/client ${CLIENT_NAME}/,/^}/d" "$CLIENTS_CONFIG"
    fi
    
    # Add client configuration
    cat >> "$CLIENTS_CONFIG" << EOF

# RadTik Client Configuration
# Auto-generated by radius-setup.sh
client ${CLIENT_NAME} {
    ipaddr = ${CLIENT_IPADDR}
    secret = ${DEFAULT_SECRET}
    require_message_authenticator = no
    nastype = other
}
EOF
    
    print_success "Client configured with auto-generated secret"
    print_warning "IMPORTANT: Save this secret for RadTik configuration:"
    echo ""
    echo -e "  ${GREEN}Secret: ${DEFAULT_SECRET}${NC}"
    echo ""
}

disable_postauth_logging() {
    print_step "Step 9/12: Optimizing SQLite configuration..."
    
    print_info "Disabling radpostauth logging to prevent lock issues..."
    
    # Comment out sql in post-auth sections
    sed -i '/post-auth {/,/}/ s/^[[:space:]]*sql/#\tsql/' "$DEFAULT_SITE"
    sed -i '/Post-Auth-Type REJECT {/,/}/ s/^[[:space:]]*sql/#\tsql/' "$DEFAULT_SITE"
    sed -i '/Post-Auth-Type Challenge {/,/}/ s/^[[:space:]]*sql/#\tsql/' "$DEFAULT_SITE"
    
    print_success "Post-auth logging disabled"
}

configure_firewall() {
    print_step "Step 10/12: Configuring firewall..."
    
    if command -v ufw &> /dev/null; then
        print_info "Opening RADIUS ports in UFW..."
        ufw allow 1812/udp comment "RADIUS Authentication" > /dev/null 2>&1 || true
        ufw allow 1813/udp comment "RADIUS Accounting" > /dev/null 2>&1 || true
        print_success "Firewall rules added"
    else
        print_info "UFW not installed, skipping firewall configuration"
    fi
}

restart_service() {
    print_step "Step 11/12: Restarting FreeRADIUS service..."
    
    print_info "Enabling service on boot..."
    systemctl enable freeradius > /dev/null 2>&1
    
    print_info "Restarting FreeRADIUS..."
    systemctl restart freeradius
    
    # Wait for service to start
    sleep 2
    
    # Check service status
    if systemctl is-active --quiet freeradius; then
        print_success "FreeRADIUS service is running"
    else
        print_error "FreeRADIUS service failed to start"
        print_info "Run 'sudo freeradius -X' for debugging"
        exit 1
    fi
}

create_test_user() {
    print_step "Step 12/12: Creating test user..."
    
    print_info "Adding test user: ${TEST_USERNAME}"
    
    sqlite3 "${RADIUS_DB}" << EOF
INSERT OR REPLACE INTO radcheck (username, attribute, op, value)
VALUES ('${TEST_USERNAME}', 'Cleartext-Password', ':=', '${TEST_PASSWORD}');
EOF
    
    # Verify user was created
    local user_exists=$(sqlite3 "${RADIUS_DB}" "SELECT COUNT(*) FROM radcheck WHERE username='${TEST_USERNAME}';")
    
    if [[ $user_exists -gt 0 ]]; then
        print_success "Test user created successfully"
    else
        print_error "Failed to create test user"
        exit 1
    fi
}

test_authentication() {
    print_header "Testing Authentication"
    
    print_info "Testing local authentication..."
    
    local test_output=$(radtest "${TEST_USERNAME}" "${TEST_PASSWORD}" 127.0.0.1 0 "${DEFAULT_SECRET}" 2>&1)
    
    if echo "$test_output" | grep -q "Access-Accept"; then
        print_success "Authentication test PASSED"
        echo ""
        echo -e "${GREEN}✓ FreeRADIUS is working correctly!${NC}"
    else
        print_error "Authentication test FAILED"
        echo ""
        echo "Test output:"
        echo "$test_output"
        echo ""
        print_info "Try running 'sudo freeradius -X' for detailed debugging"
        return 1
    fi
}

print_summary() {
    print_header "Installation Complete"
    
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                                   ║${NC}"
    echo -e "${GREEN}║  ${BLUE}FreeRADIUS + SQLite Setup Completed Successfully!${GREEN}             ║${NC}"
    echo -e "${GREEN}║                                                                   ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}📋 Configuration Details${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  ${BLUE}Database Location:${NC}      ${RADIUS_DB}"
    echo -e "  ${BLUE}Client Name:${NC}            ${CLIENT_NAME}"
    echo -e "  ${BLUE}Client IP Range:${NC}        ${CLIENT_IPADDR}"
    echo -e "  ${BLUE}Shared Secret:${NC}          ${GREEN}${DEFAULT_SECRET}${NC}"
    echo ""
    echo -e "  ${BLUE}Test Username:${NC}          ${TEST_USERNAME}"
    echo -e "  ${BLUE}Test Password:${NC}          ${TEST_PASSWORD}"
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}📝 Next Steps for RadTik Integration${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  1. ${YELLOW}Save the shared secret above${NC} - you'll need it in RadTik"
    echo -e "  2. Add this RADIUS server in RadTik admin panel"
    echo -e "  3. Configure your Python sync script to update the database"
    echo -e "  4. Test authentication from RadTik MikroTik routers"
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🔧 Useful Commands${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  ${BLUE}Check service status:${NC}"
    echo -e "    sudo systemctl status freeradius"
    echo ""
    echo -e "  ${BLUE}View logs:${NC}"
    echo -e "    sudo journalctl -u freeradius -f"
    echo ""
    echo -e "  ${BLUE}Debug mode:${NC}"
    echo -e "    sudo freeradius -X"
    echo ""
    echo -e "  ${BLUE}Test authentication:${NC}"
    echo -e "    radtest ${TEST_USERNAME} ${TEST_PASSWORD} 127.0.0.1 0 ${DEFAULT_SECRET}"
    echo ""
    echo -e "  ${BLUE}Access database:${NC}"
    echo -e "    sudo sqlite3 ${RADIUS_DB}"
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${GREEN}✓${NC} Setup completed in ${SECONDS} seconds"
    echo ""
}

print_banner() {
    clear
    echo ""
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                                                                   ║${NC}"
    echo -e "${CYAN}║       ${BLUE}FreeRADIUS + SQLite Automated Setup for RadTik${CYAN}            ║${NC}"
    echo -e "${CYAN}║                                                                   ║${NC}"
    echo -e "${CYAN}║       ${YELLOW}Version 1.0.0${CYAN}                                              ║${NC}"
    echo -e "${CYAN}║       ${YELLOW}Ubuntu 22.04 LTS${CYAN}                                           ║${NC}"
    echo -e "${CYAN}║                                                                   ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    sleep 1
}

#############################################################################
# MAIN EXECUTION
#############################################################################

main() {
    print_banner
    
    # Pre-flight checks
    print_info "Running pre-flight checks..."
    check_root
    check_os
    
    echo ""
    print_warning "This script will install and configure FreeRADIUS with SQLite"
    print_info "Estimated time: 2-3 minutes"
    echo ""
    read -p "Continue with installation? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        print_info "Installation cancelled"
        exit 0
    fi
    
    # Main installation steps
    install_packages
    create_database
    import_schema
    fix_permissions
    enable_wal_mode
    configure_sql_module
    enable_sql_module
    configure_client
    disable_postauth_logging
    configure_firewall
    restart_service
    create_test_user
    
    # Testing
    test_authentication
    
    # Summary
    print_summary
}

# Error handler
trap 'print_error "An error occurred on line $LINENO. Installation failed."; exit 1' ERR

# Run main function
main "$@"
