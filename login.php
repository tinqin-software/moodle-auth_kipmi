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
 * KIPMI Wallet authentication login page.
 *
 * This script initializes a KIPMI wallet authentication session, generates a QR code
 * for the user to scan with their wallet application, and polls the authentication
 * backend to detect when the user has successfully verified their credentials.
 *
 * The page displays a QR code that encodes the authentication request URL, which the
 * user scans with their KIPMI wallet. The page then polls the status endpoint until
 * the authentication is complete, at which point it redirects to the callback handler.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_sesskey();

$wantsurl = optional_param('wantsurl', '/', PARAM_RAW_TRIMMED);

// Generate unique session ID and anti-replay state token
$sessionid = 'moodle-' . bin2hex(random_bytes(8)) . '-' . time();
$state = bin2hex(random_bytes(16));

// Store session data in Moodle session
$SESSION->auth_kipmi = (object)[
    'state' => $state,
    'sessionid' => $sessionid,
    'wantsurl' => $wantsurl ?: '/',
    'starttime' => time(),
];

// Get VP Verifier URL from configuration
$verifierurl = get_config('auth_kipmi', 'vp_verifier_url');
$moodlebaseurl = get_config('auth_kipmi', 'moodle_base_url') ?: $CFG->wwwroot;

if (empty($verifierurl)) {
    throw new \moodle_exception('vp_verifier_not_configured', 'auth_kipmi');
}

$sslverify = get_config('auth_kipmi', 'ssl_verify');

// Construct callback URL for VP Verifier webhook
$callbackurl = $moodlebaseurl . '/auth/kipmi/vp_callback.php?sessionid=' . urlencode($sessionid);

// Call VP Verifier to initialize authentication request and get QR code URL
// Respect SSL verification setting (default: enabled)
if ($sslverify) {
    // SSL verification enabled - use secure connection
    $curloptions = [];
} else {
    // SSL verification disabled (for development only)
    $curloptions = ['ignoresecurity' => true];
}

$curl = new curl($curloptions);

// Get credential configuration from settings.
$credentialname = get_config('auth_kipmi', 'credential_name') ?: 'StudentStatusCredential';
$requiredfieldsraw = get_config('auth_kipmi', 'required_fields') ?: "given_name\nfamily_name\nstudentId\nemail";

// Parse required fields (one per line, trim whitespace).
$requiredfields = array_filter(array_map('trim', explode("\n", $requiredfieldsraw)));

// Build constraint fields dynamically.
$constraintfields = [];
$destinations = [];

foreach ($requiredfields as $field) {
    $constraintfields[] = [
        'path' => ['$.vc.credentialSubject.' . $field],
        'id' => $field,
    ];
    $destinations[$field] = '$.' . $field;
}

// Add VC type constraint with filter.
$constraintfields[] = [
    'path' => ['$.vc.type'],
    'id' => $credentialname,
    'filter' => [
        'type' => 'string',
        'pattern' => $credentialname,
    ],
];

$payload = json_encode([
    'credential_type' => 'any',
    'callback_url' => $callbackurl,
    'constraints' => [
        'fields' => $constraintfields,
    ],
    'destinations' => $destinations,
], JSON_UNESCAPED_SLASHES);

// Debug: Show what we're about to send
debugging('=== REQUEST DEBUG ===', DEBUG_DEVELOPER);
debugging('URL: ' . $verifierurl . '/authorization-request?presentation-type=presentation-exchange', DEBUG_DEVELOPER);
debugging('Payload: ' . $payload, DEBUG_DEVELOPER);
debugging('Payload length: ' . strlen($payload) . ' bytes', DEBUG_DEVELOPER);

// Use native PHP cURL to avoid Moodle's curl class issues
$ch = curl_init($verifierurl . '/authorization-request?presentation-type=presentation-exchange');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: text/plain',
    'Content-Length: ' . strlen($payload),
]);

// Respect SSL verification setting
if (!$sslverify) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
}

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlerror = curl_error($ch);
curl_close($ch);

// Debug: Show what we received
debugging('=== RESPONSE DEBUG ===', DEBUG_DEVELOPER);
debugging('HTTP Status: ' . $httpcode, DEBUG_DEVELOPER);
debugging('Response: ' . $response, DEBUG_DEVELOPER);
debugging('cURL Error: ' . $curlerror, DEBUG_DEVELOPER);

// VP Verifier returns the openid4vp:// URL directly as a string (not JSON)
if (!$response || empty($response)) {
    debugging('VP Verifier init failed: http=' . $httpcode . ' error=' . $curlerror, DEBUG_DEVELOPER);
    throw new \moodle_exception('auth_init_failed', 'auth_kipmi');
}

$qrurl = trim($response);

// Validate it's an openid4vp URL
if (strpos($qrurl, 'openid4vp://') !== 0) {
    debugging('Invalid VP Verifier response: ' . substr($response, 0, 100), DEBUG_DEVELOPER);
    throw new \moodle_exception('auth_init_failed', 'auth_kipmi');
}

// Set up page
$PAGE->set_url(new moodle_url('/auth/kipmi/login.php', ['wantsurl' => $wantsurl, 'sesskey' => sesskey()]));
$PAGE->set_pagelayout('login');
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('scan_with_wallet', 'auth_kipmi'));

// Display instructions
echo html_writer::div(
    get_string('scan_instructions', 'auth_kipmi'),
    'alert alert-info mb-3'
);

// Render QR code
$qr = new core_qrcode($qrurl);
$png = $qr->getBarcodePngData(6, 6, [0, 0, 0]);

if ($png) {
    // PNG image available (requires GD or Imagick)
    $src = 'data:image/png;base64,' . base64_encode($png);
    echo html_writer::div(
        html_writer::empty_tag('img', [
            'src' => $src,
            'alt' => get_string('qr_code_alt', 'auth_kipmi'),
            'width' => 300,
            'height' => 300,
            'class' => 'kipmi-qr-code',
        ]),
        'kipmi-qr-container text-center mb-3'
    );
} else {
    // Fallback: HTML blocks (no GD/Imagick)
    echo html_writer::div($qr->getBarcodeHTML(4, 4, 'black'), 'kipmi-qr-container text-center mb-3');
}

// Display session ID only in debug mode
if (debugging('', DEBUG_DEVELOPER)) {
    echo html_writer::div(
        html_writer::tag('strong', 'Session ID: ') . s($sessionid),
        'alert alert-warning mt-3'
    );
}

// Status polling endpoint
$statusurl = (new moodle_url('/auth/kipmi/status.php', [
    'sessionid' => $sessionid,
    'sesskey' => sesskey(),
]))->out(false);

// Callback URL
$callbackurl = (new moodle_url('/auth/kipmi/callback.php'))->out(false);
$sesskey = sesskey();

// Get localized strings for JavaScript.
$strtimeout = get_string('auth_timeout', 'auth_kipmi');
$strfailed = get_string('auth_failed_try_again', 'auth_kipmi');

// JavaScript for status polling
$js = <<<JAVASCRIPT
(function() {
    var polling = true;
    var pollInterval = 2000; // 2 seconds
    var maxAttempts = 150; // 5 minutes total
    var attempts = 0;

    function poll() {
        if (!polling || attempts >= maxAttempts) {
            if (attempts >= maxAttempts) {
                alert('{$strtimeout}');
                window.location.href = '{$CFG->wwwroot}/login/index.php';
            }
            return;
        }

        attempts++;

        fetch("{$statusurl}", {
            credentials: "same-origin",
            method: "GET"
        })
        .then(function(response) {
            console.log('Status response:', response.status);
            return response.json();
        })
        .then(function(data) {
            console.log('Status data:', data);
            if (data && data.status === 'success') {
                console.log('SUCCESS - submitting form to callback');
                polling = false;

                // Submit to callback
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = "{$callbackurl}";
                form.innerHTML =
                    '<input type="hidden" name="state" value="{$state}">' +
                    '<input type="hidden" name="sessionid" value="{$sessionid}">' +
                    '<input type="hidden" name="sesskey" value="{$sesskey}">';
                document.body.appendChild(form);
                form.submit();
            } else if (data && data.status === 'failed') {
                polling = false;
                alert('{$strfailed}');
                window.location.href = '{$CFG->wwwroot}/login/index.php';
            } else {
                // Still waiting
                setTimeout(poll, pollInterval);
            }
        })
        .catch(function(error) {
            console.error('Polling error:', error);
            setTimeout(poll, pollInterval);
        });
    }

    // Start polling
    poll();
})();
JAVASCRIPT;

$PAGE->requires->js_init_code($js);

echo $OUTPUT->footer();
