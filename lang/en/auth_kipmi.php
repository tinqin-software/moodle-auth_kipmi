<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the KIPMI Wallet authentication plugin.
 *
 * This file contains all English language strings used by the plugin,
 * including settings labels, descriptions, error messages, and user-facing text.
 * Translations to other languages should be contributed at lang.moodle.org.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin information
$string['pluginname'] = 'KIPMI wallet authentication';
$string['auth_kipmi_description'] = 'Authenticate users using KIPMI wallet and verifiable credentials (OIDC4VP).';

// Cache definitions
$string['cachedef_authsessions'] = 'Temporary storage for KIPMI authentication sessions during VP verification';

// Settings
$string['vp_verifier_url'] = 'VP Verifier URL';
$string['vp_verifier_url_desc'] = 'URL of the VP Verifier service for OIDC4VP verification (e.g., https://api.be-ys.com/vp-verifier/v1)';
$string['moodle_base_url'] = 'Moodle Base URL';
$string['moodle_base_url_desc'] = 'The base URL of your Moodle installation, used for VP Verifier webhook callbacks. Leave as default unless Moodle is behind a proxy or has a different public URL.';
$string['button_label'] = 'Login button label';
$string['button_label_desc'] = 'Text displayed on the KIPMI login button';
$string['map_field'] = 'User mapping field';
$string['map_field_desc'] = 'Which Moodle user field to map the verified studentId to';
$string['autocreate'] = 'Auto-create users';
$string['autocreate_desc'] = 'Automatically create Moodle user accounts for verified wallet users if they don\'t exist';
$string['ssl_verify'] = 'Verify SSL certificates';
$string['ssl_verify_desc'] = 'Enable SSL certificate verification when calling VP Verifier (disable only for local development)';
$string['credential_name'] = 'Credential name';
$string['credential_name_desc'] = 'The name of the verifiable credential type to request (e.g., StudentStatusCredential, identity-credential)';
$string['required_fields'] = 'Required fields';
$string['required_fields_desc'] = 'List of attribute fields to request from the credential, one per line (e.g., given_name, family_name, studentId, email)';
$string['user_id_field'] = 'User identifier field';
$string['user_id_field_desc'] = 'Which credential attribute to use as the user identifier for matching Moodle accounts (e.g., studentId, personal_administrative_number)';
$string['default_firstname'] = 'Default first name';
$string['default_firstname_desc'] = 'Default first name to use when the wallet credential does not provide a given_name attribute';
$string['default_lastname'] = 'Default last name';
$string['default_lastname_desc'] = 'Default last name to use when the wallet credential does not provide a family_name attribute';

// Login page strings
$string['scan_with_wallet'] = 'Scan QR code with KIPMI wallet';
$string['scan_instructions'] = 'Open your KIPMI wallet app and scan this QR code to authenticate. The page will automatically continue once verification is complete.';
$string['qr_code_alt'] = 'KIPMI authentication QR code';
$string['auth_timeout'] = 'Authentication timeout. Please try again.';
$string['auth_failed_try_again'] = 'Authentication failed. Please try again.';

// Error messages
$string['vp_verifier_not_configured'] = 'VP Verifier URL not configured. Please configure the KIPMI authentication plugin.';
$string['auth_init_failed'] = 'Failed to initialize KIPMI authentication. Please check the VP Verifier URL configuration and try again.';
$string['invalid_state'] = 'Invalid authentication state. This may be a session timeout or security issue. Please try logging in again.';
$string['verification_failed'] = 'Wallet verification failed. Please try again.';
$string['no_user_identifier'] = 'No user identifier received from wallet. The wallet credential may be missing required attributes.';
$string['user_not_found'] = 'User not found in Moodle and auto-creation is disabled. Please contact your administrator.';
$string['user_creation_failed'] = 'Failed to create user account. Please contact your administrator.';

// Privacy API
$string['privacy:metadata'] = 'The KIPMI Wallet Authentication plugin does not store any personal data itself. It relies on verified credentials from the KIPMI wallet for authentication.';
$string['privacy:metadata:auth_kipmi_vp_verifier'] = 'The external VP Verifier service that handles OIDC4VP credential verification requests.';
$string['privacy:metadata:auth_kipmi_vp_verifier:credential_type'] = 'The type of verifiable credential being requested (e.g., identity-credential).';
$string['privacy:metadata:auth_kipmi_vp_verifier:callback_url'] = 'The webhook URL where VP Verifier sends authentication results back to Moodle.';
$string['privacy:metadata:auth_kipmi_vp_verifier:requested_attributes'] = 'The list of user attributes requested from the verifiable credential (e.g., given_name, family_name, studentId, email).';
$string['privacy:metadata:auth_kipmi_vp_callback'] = 'Authentication results received from the VP Verifier via webhook callback.';
$string['privacy:metadata:auth_kipmi_vp_callback:studentId'] = 'The user\'s student ID extracted from their verified wallet credential, used to identify and match the user in Moodle.';
$string['privacy:metadata:auth_kipmi_vp_callback:given_name'] = 'The user\'s first name extracted from their verified wallet credential.';
$string['privacy:metadata:auth_kipmi_vp_callback:family_name'] = 'The user\'s last name extracted from their verified wallet credential.';
$string['privacy:metadata:auth_kipmi_vp_callback:email'] = 'The user\'s email address extracted from their verified wallet credential.';
$string['privacy:metadata:auth_kipmi_session'] = 'Temporary session data stored during the KIPMI wallet authentication process. This data is automatically deleted after login completes or the session expires.';
$string['privacy:metadata:auth_kipmi_session:state'] = 'A CSRF protection token to prevent replay attacks during authentication.';
$string['privacy:metadata:auth_kipmi_session:sessionid'] = 'The unique session identifier for this authentication attempt.';
$string['privacy:metadata:auth_kipmi_session:wantsurl'] = 'The URL the user was trying to access before authentication, used for redirection after successful login.';
$string['privacy:metadata:auth_kipmi_session:starttime'] = 'The timestamp when the authentication session was initiated.';
