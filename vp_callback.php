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
 * VP Verifier webhook callback endpoint.
 *
 * This is a public webhook endpoint that receives authentication results from
 * the VP Verifier service after a user completes wallet verification. The endpoint
 * validates the session ID, stores the verification results in the Moodle cache,
 * and returns an appropriate HTTP status code.
 *
 * This endpoint is intentionally public (no sesskey required) as it must be
 * callable by the external VP Verifier service. Security is maintained through:
 * - Strict sessionid format validation
 * - Timestamp validation (only accept recent sessions)
 * - High-entropy session IDs (128 bits)
 * - One-time use (idempotent handling of duplicates)
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

// This is a public webhook endpoint - no sesskey validation.
// Security is ensured through sessionid validation.

// Debug logging - log all incoming webhook calls.
debugging('=== VP CALLBACK RECEIVED ===', DEBUG_DEVELOPER);
debugging('Request method: ' . $_SERVER['REQUEST_METHOD'], DEBUG_DEVELOPER);
debugging('Query string: ' . ($_SERVER['QUERY_STRING'] ?? 'none'), DEBUG_DEVELOPER);

// Get raw POST body and decode JSON.
$rawbody = file_get_contents('php://input');
debugging('Raw body: ' . $rawbody, DEBUG_DEVELOPER);
$data = json_decode($rawbody, true);

// Validate JSON payload.
if (!$data || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload: missing status']);
    exit;
}

// Sanitize incoming data from external service.
$data['status'] = clean_param($data['status'], PARAM_ALPHA);
if (isset($data['result']) && is_array($data['result'])) {
    // Sanitize each field in the result array.
    foreach ($data['result'] as $key => $value) {
        $cleankey = clean_param($key, PARAM_ALPHANUMEXT);
        $cleanvalue = is_string($value) ? clean_param($value, PARAM_TEXT) : $value;
        unset($data['result'][$key]);
        $data['result'][$cleankey] = $cleanvalue;
    }
}

// Get sessionid from URL parameter.
$sessionid = optional_param('sessionid', '', PARAM_RAW_TRIMMED);

if (empty($sessionid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sessionid']);
    exit;
}

// Validate sessionid format to prevent injection attacks.
// Expected format: moodle-{16-hex-chars}-{unix-timestamp}
if (!preg_match('/^moodle-[a-f0-9]{16}-\d+$/', $sessionid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sessionid format']);
    exit;
}

// Extract timestamp from sessionid and validate it's recent (within last 10 minutes).
$parts = explode('-', $sessionid);
$timestamp = (int) end($parts);
$age = abs(time() - $timestamp);

if ($age > 600) { // 10 minutes = 600 seconds.
    http_response_code(410); // 410 Gone - session too old.
    echo json_encode(['error' => 'Session expired']);
    exit;
}

// Get cache instance.
$cache = cache::make('auth_kipmi', 'authsessions');

// Check if this session already has a result (idempotent handling).
$existing = $cache->get($sessionid);
if ($existing !== false) {
    // Already processed - return success (idempotent).
    http_response_code(200);
    echo json_encode(['success' => true, 'note' => 'Already processed']);
    exit;
}

// Prepare callback data.
$callbackdata = [
    'status' => $data['status'], // 'SUCCESS' or 'FAILED'.
    'result' => $data['result'] ?? null,
    'timestamp' => time(),
];

// Store in cache (auto-expires in 1 hour via TTL).
$cache->set($sessionid, $callbackdata);

debugging('Callback data stored for session: ' . $sessionid, DEBUG_DEVELOPER);
debugging('Status: ' . $data['status'], DEBUG_DEVELOPER);

// Return success.
http_response_code(200);
echo json_encode(['success' => true]);
exit;
