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
 * KIPMI Wallet authentication callback handler.
 *
 * This script handles the callback after a successful wallet verification.
 * It validates the session state, retrieves the verified credentials from the
 * authentication backend, finds or creates the corresponding Moodle user account,
 * and completes the login process.
 *
 * The script performs the following steps:
 * 1. Validates the CSRF state token to prevent replay attacks
 * 2. Retrieves the authentication session data from the backend
 * 3. Extracts user attributes from the verifiable credentials
 * 4. Finds existing user or creates a new one (if auto-creation is enabled)
 * 5. Updates user information if changed
 * 6. Completes Moodle login and redirects to the original destination
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/user/lib.php');

require_sesskey();

$state = required_param('state', PARAM_ALPHANUMEXT);
$sessionid = required_param('sessionid', PARAM_RAW_TRIMMED);

// Validate session and state token (CSRF protection)
if (
    empty($SESSION->auth_kipmi)
    || $SESSION->auth_kipmi->state !== $state
    || $SESSION->auth_kipmi->sessionid !== $sessionid
) {
    throw new \moodle_exception('invalid_state', 'auth_kipmi');
}

// Get backend URL from configuration
$backendurl = get_config('auth_kipmi', 'backend_url');
if (empty($backendurl)) {
    throw new \moodle_exception('Backend URL not configured. Please configure the KIPMI authentication plugin.');
}
$sslverify = get_config('auth_kipmi', 'ssl_verify');

// Get final session status from authentication-be
// Respect SSL verification setting (default: enabled)
if ($sslverify) {
    // SSL verification enabled - use secure connection
    $curloptions = [];
} else {
    // SSL verification disabled (for development only)
    $curloptions = ['ignoresecurity' => true];
}

$curl = new curl($curloptions);
$statusendpoint = $backendurl . '/api/sessions/' . rawurlencode($sessionid);
$response = $curl->get($statusendpoint);
$data = json_decode($response, true);

if (empty($data) || $data['status'] !== 'success') {
    throw new \moodle_exception('verification_failed', 'auth_kipmi');
}

// Extract attributes - they may be nested under 'attributes' key or sessionId key
$attributes = $data;
if (isset($data['attributes']) && is_array($data['attributes'])) {
    // Attributes are nested under 'attributes' key (standard format)
    $attributes = $data['attributes'];
} else if (isset($data[$sessionid]) && is_array($data[$sessionid])) {
    // Attributes are nested under sessionId key (alternative format)
    $attributes = $data[$sessionid];
}

// Extract user identifier - personal_administrative_number is the key attribute
$userid = $attributes['personal_administrative_number'] ?? null;

if (empty($userid)) {
    debugging('Session data structure: ' . json_encode($data), DEBUG_DEVELOPER);
    debugging('Extracted attributes: ' . json_encode($attributes), DEBUG_DEVELOPER);
    throw new \moodle_exception('no_user_identifier', 'auth_kipmi');
}

// Get additional attributes with configurable defaults
$defaultfirstname = get_config('auth_kipmi', 'default_firstname') ?: 'KIPMI';
$defaultlastname = get_config('auth_kipmi', 'default_lastname') ?: 'User';
$givenname = !empty($attributes['given_name']) ? $attributes['given_name'] : $defaultfirstname;
$familyname = !empty($attributes['family_name']) ? $attributes['family_name'] : $defaultlastname;
$email = $attributes['email'] ?? null;

// Find or create Moodle user
global $DB;

$mapfield = get_config('auth_kipmi', 'map_field') ?: 'idnumber';
$user = $DB->get_record('user', [
    $mapfield => $userid,
    'mnethostid' => $CFG->mnet_localhost_id,
    'deleted' => 0,
]);

// Auto-create user if enabled and user not found
if (!$user && get_config('auth_kipmi', 'autocreate')) {
    $newuser = new stdClass();
    $newuser->auth = 'kipmi';
    $newuser->username = 'kipmi_' . strtolower($userid);
    $newuser->idnumber = $userid;
    $newuser->firstname = $givenname;
    $newuser->lastname = $familyname;

    // Generate email if not provided
    if (empty($email)) {
        $newuser->email = 'kipmi.' . strtolower($userid) . '@noreply.local';
    } else {
        $newuser->email = $email;
    }

    $newuser->confirmed = 1;
    $newuser->mnethostid = $CFG->mnet_localhost_id;
    $newuser->timecreated = time();
    $newuser->timemodified = time();

    try {
        $newuser->id = user_create_user($newuser, false, false);
        $user = $DB->get_record('user', ['id' => $newuser->id], '*', MUST_EXIST);
    } catch (Exception $e) {
        debugging('Failed to create user: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw new \moodle_exception('user_creation_failed', 'auth_kipmi');
    }
}

if (!$user) {
    throw new \moodle_exception('user_not_found', 'auth_kipmi');
}

// Update user information if it has changed
if ($user->firstname !== $givenname || $user->lastname !== $familyname) {
    $user->firstname = $givenname;
    $user->lastname = $familyname;
    $user->timemodified = time();
    $DB->update_record('user', $user);
}

// Complete Moodle login
complete_user_login($user);
\core\session\manager::apply_concurrent_login_limit($user->id, session_id());

// Determine redirect URL
$wantsurl = $SESSION->auth_kipmi->wantsurl ?? '/';

// Security: Don't redirect non-admins to admin pages
if (!is_siteadmin($user->id) && !empty($wantsurl) && strpos($wantsurl, '/admin/') !== false) {
    $redirecturl = $CFG->wwwroot . '/';
} else {
    $redirecturl = !empty($wantsurl) ? $wantsurl : $CFG->wwwroot . '/';
}

// Cleanup: Delete session from authentication-be
$deleteendpoint = $backendurl . '/api/sessions/' . rawurlencode($sessionid);
$curl->delete($deleteendpoint);

// Cleanup: Remove from Moodle session
unset($SESSION->auth_kipmi);

// Redirect to final destination
redirect($redirecturl);
