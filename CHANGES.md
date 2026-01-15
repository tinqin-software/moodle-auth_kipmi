# Changelog

All notable changes to the KIPMI Wallet Authentication plugin will be documented in this file.

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
