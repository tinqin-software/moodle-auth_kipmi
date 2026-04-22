# Changelog

All notable changes to the KIPMI Wallet Authentication plugin will be documented in this file.

## [Unreleased]

### Fixed
- Anonymous QR polling (`auth_kipmi_check_auth_status` external function) no
  longer throws `requireloginerror`. Removed the `validate_context()` call that
  was incorrectly added to an intentionally-anonymous endpoint; authorization
  is already enforced by the `$SESSION->auth_kipmi->sessionid` ownership check
  a few lines below. Without this fix, the QR login page polls silently forever
  and never completes, even after a successful wallet scan.

## [1.0.0] - 2026-01-08

### Added
- Initial release of KIPMI Wallet Authentication plugin
- Direct VP Verifier integration using OIDC4VP protocol
- QR code-based authentication flow
- Webhook callback endpoint for VP Verifier responses
- Moodle Cache API for temporary session storage
- Configurable settings:
  - `vp_verifier_url` - VP Verifier service URL
  - `moodle_base_url` - Public URL for webhook callbacks
  - `credential_name` - Configurable credential type (default: StudentStatusCredential)
  - `required_fields` - List of attributes to request from credentials
  - `user_id_field` - Which attribute to use as user identifier
  - `map_field` - Moodle user field mapping
  - `autocreate` - Auto-create user accounts
  - `default_firstname` / `default_lastname` - Fallback names
  - `ssl_verify` - SSL certificate verification
- User auto-creation support
- Privacy API implementation for GDPR compliance
