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

// Get backend URL from configuration
$backendurl = get_config('auth_kipmi', 'backend_url');

// Debug: Log what we got
debugging('KIPMI backend_url from get_config: ' . var_export($backendurl, true), DEBUG_DEVELOPER);
debugging('KIPMI all config: ' . var_export(get_config('auth_kipmi'), true), DEBUG_DEVELOPER);

if (empty($backendurl)) {
    throw new \moodle_exception('Backend URL not configured. Please configure the KIPMI authentication plugin.');
}
$sslverify = get_config('auth_kipmi', 'ssl_verify');

// Call authentication-be to initialize session and get QR code
// Respect SSL verification setting (default: enabled)
if ($sslverify) {
    // SSL verification enabled - use secure connection
    $curloptions = [];
} else {
    // SSL verification disabled (for development only)
    $curloptions = ['ignoresecurity' => true];
}

$curl = new curl($curloptions);
$payload = json_encode(['sessionId' => $sessionid], JSON_UNESCAPED_SLASHES);
$options = [
    'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
    'CURLOPT_TIMEOUT' => 10,
];

$response = $curl->post($backendurl . '/api/sessions', $payload, $options);
$data = json_decode($response, true);

if (!$response || $data === null || empty($data['url'])) {
    debugging('KIPMI auth init failed: http=' . $curl->info['http_code'] . ' error=' . $curl->error, DEBUG_DEVELOPER);
    throw new \moodle_exception('auth_init_failed', 'auth_kipmi');
}

$qrurl = $data['url'];

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
                alert('Authentication timeout. Please try again.');
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
            return response.json();
        })
        .then(function(data) {
            if (data && data.status === 'success') {
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
                alert('Authentication failed. Please try again.');
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
