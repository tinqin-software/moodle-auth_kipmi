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
 * KIPMI Wallet authentication status polling endpoint.
 *
 * This AJAX endpoint is called periodically by the login page to check if the
 * user has completed the wallet verification process. It queries the authentication
 * backend for the current status of the authentication session and returns it as JSON.
 *
 * The endpoint validates that the session ID matches the one stored in the Moodle
 * session to prevent unauthorized status checks. It returns one of three statuses:
 * - 'pending': User has not yet scanned the QR code or completed verification
 * - 'success': Wallet verification completed successfully
 * - 'failed': Wallet verification failed or was rejected
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define AJAX_SCRIPT before config.php to suppress debug output in response.
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_sesskey();

$sessionid = required_param('sessionid', PARAM_RAW_TRIMMED);

header('Content-Type: application/json');

// Validate that this session exists in Moodle session.
if (empty($SESSION->auth_kipmi) || $SESSION->auth_kipmi->sessionid !== $sessionid) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
    exit;
}

// Check Moodle Cache API for callback data from VP Verifier webhook.
$cache = cache::make('auth_kipmi', 'authsessions');
$callbackdata = $cache->get($sessionid);

if ($callbackdata !== false) {
    // Callback received from VP Verifier - store credentials in session for callback.php
    $SESSION->auth_kipmi->credentials = $callbackdata['result'];
    $SESSION->auth_kipmi->vp_status = $callbackdata['status'];

    // Return status to frontend (lowercase for consistency)
    echo json_encode(['status' => strtolower($callbackdata['status'])]);
} else {
    // No callback yet - still waiting for user to scan QR code
    echo json_encode(['status' => 'pending']);
}
