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
 * KIPMI wallet authentication login page polling module.
 *
 * @module     auth_kipmi/login
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/** @type {boolean} Whether polling is active */
let polling = true;

/** @type {number} Polling interval in milliseconds */
const POLL_INTERVAL = 2000;

/** @type {number} Maximum number of polling attempts (5 minutes total) */
const MAX_ATTEMPTS = 150;

/** @type {number} Current attempt count */
let attempts = 0;

/**
 * Initialize the login polling module.
 *
 * @param {string} sessionId The authentication session ID.
 * @param {string} state The CSRF state token.
 * @param {string} callbackUrl The callback URL to submit to on success.
 * @param {string} loginUrl The login page URL to redirect to on failure.
 * @param {string} timeoutMessage The message to show on timeout.
 * @param {string} failedMessage The message to show on authentication failure.
 */
export const init = (sessionId, state, callbackUrl, loginUrl, timeoutMessage, failedMessage) => {
    /**
     * Poll for authentication status.
     */
    const poll = () => {
        if (!polling || attempts >= MAX_ATTEMPTS) {
            if (attempts >= MAX_ATTEMPTS) {
                Notification.alert('', timeoutMessage);
                window.location.href = loginUrl;
            }
            return;
        }

        attempts++;

        Ajax.call([{
            methodname: 'auth_kipmi_check_auth_status',
            args: {sessionid: sessionId},
            done: (response) => {
                if (response.status === 'success') {
                    polling = false;
                    submitCallback(callbackUrl, state, sessionId);
                } else if (response.status === 'failed') {
                    polling = false;
                    Notification.alert('', failedMessage);
                    window.location.href = loginUrl;
                } else if (response.status === 'error') {
                    // Error from server, continue polling.
                    setTimeout(poll, POLL_INTERVAL);
                } else {
                    // Still pending.
                    setTimeout(poll, POLL_INTERVAL);
                }
            },
            fail: () => {
                // Network error, continue polling.
                setTimeout(poll, POLL_INTERVAL);
            }
        }]);
    };

    /**
     * Submit to callback URL on successful authentication.
     *
     * @param {string} url The callback URL.
     * @param {string} stateToken The CSRF state token.
     * @param {string} sessId The session ID.
     */
    const submitCallback = (url, stateToken, sessId) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;

        const stateInput = document.createElement('input');
        stateInput.type = 'hidden';
        stateInput.name = 'state';
        stateInput.value = stateToken;
        form.appendChild(stateInput);

        const sessionInput = document.createElement('input');
        sessionInput.type = 'hidden';
        sessionInput.name = 'sessionid';
        sessionInput.value = sessId;
        form.appendChild(sessionInput);

        const sessKeyInput = document.createElement('input');
        sessKeyInput.type = 'hidden';
        sessKeyInput.name = 'sesskey';
        sessKeyInput.value = M.cfg.sesskey;
        form.appendChild(sessKeyInput);

        document.body.appendChild(form);
        form.submit();
    };

    // Start polling.
    poll();
};
