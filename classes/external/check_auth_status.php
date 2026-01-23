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
 * External function to check KIPMI authentication status.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_kipmi\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External function to check the authentication status for a KIPMI wallet session.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_auth_status extends external_api {
    /**
     * Describes the parameters for check_auth_status.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_ALPHANUMEXT, 'The authentication session ID'),
        ]);
    }

    /**
     * Check the authentication status for a KIPMI wallet session.
     *
     * @param string $sessionid The session ID to check.
     * @return array The authentication status.
     */
    public static function execute(string $sessionid): array {
        global $SESSION;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
        ]);
        $sessionid = $params['sessionid'];

        // Validate sessionid format: moodle-{16-hex-chars}-{unix-timestamp}.
        if (!preg_match('/^moodle-[a-f0-9]{16}-\d+$/', $sessionid)) {
            return ['status' => 'error', 'message' => 'Invalid session ID format'];
        }

        // Check that this session belongs to the current user's auth attempt.
        if (empty($SESSION->auth_kipmi) || $SESSION->auth_kipmi->sessionid !== $sessionid) {
            return ['status' => 'error', 'message' => 'Session mismatch'];
        }

        // Check cache for callback data from VP Verifier.
        $cache = \cache::make('auth_kipmi', 'authsessions');
        $callbackdata = $cache->get($sessionid);

        if ($callbackdata === false) {
            // Still waiting for VP Verifier callback.
            return ['status' => 'pending', 'message' => ''];
        }

        // Store credentials in session for callback.php to use.
        $SESSION->auth_kipmi->credentials = $callbackdata['result'] ?? null;
        $SESSION->auth_kipmi->vp_status = $callbackdata['status'] ?? 'FAILED';

        // Return status based on VP Verifier response.
        if ($callbackdata['status'] === 'SUCCESS') {
            return ['status' => 'success', 'message' => ''];
        } else {
            return ['status' => 'failed', 'message' => ''];
        }
    }

    /**
     * Describes the return value for check_auth_status.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status: pending, success, failed, or error'),
            'message' => new external_value(PARAM_TEXT, 'Optional message for errors'),
        ]);
    }
}
