# Changelog

All notable changes to the RadTik FreeRADIUS installer will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-15

### Added
- Initial release of RadTik FreeRADIUS installer
- One-command installation script for Ubuntu 22.04 LTS
- FreeRADIUS 3.0 with SQLite backend configuration
- Production-ready SQLite database template:
  - Enhanced radpostauth table with MAC tracking columns (calling_station_id, nas_identifier)
  - Sync processing flag (processed) for Laravel integration
  - WAL mode pre-enabled for better performance
  - Performance indexes pre-configured
  - Clean state with no test data
- Python synchronization scripts for Laravel integration
  - Voucher synchronization (every 2 minutes)
  - Activation monitoring with MAC binding (every 1 minute)
  - Deleted users cleanup (every 5 minutes)
- Automated cron job setup
- Validation script (validate.sh) for installation verification
- SQLite optimizations (WAL mode, indexes, busy timeout)
- MikroTik client configuration
- Comprehensive documentation:
  - README.md with badges and structure
  - QUICKSTART.md for quick setup
  - CONTRIBUTING.md for contributors
  - DATABASE.md for database details
- Pre-configured database schema with:
  - radcheck table for authentication
  - radreply table for response attributes
  - radacct table for accounting
  - radpostauth table with RadTik enhancements

### Security
- Added .gitignore to prevent committing secrets
- Configurable API authentication via tokens
- Encrypted communication with Laravel API
- Proper file permissions for freerad user

## [Unreleased]

### Planned
- Support for multiple database backends (MySQL, PostgreSQL)
- Web-based configuration interface
- Enhanced monitoring and alerting
- Rate limiting configuration per profile
- Multi-tenancy support for ISPs
