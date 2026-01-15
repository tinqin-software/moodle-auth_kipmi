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
 * KIPMI Wallet authentication plugin class.
 *
 * Provides authentication using KIPMI wallet and Verifiable Credentials (OIDC4VP).
 * This plugin enables users to authenticate by scanning a QR code with their KIPMI
 * wallet application, which presents verifiable credentials directly to the VP Verifier
 * service via webhook callbacks.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * KIPMI Wallet authentication plugin.
 *
 * Provides authentication using KIPMI wallet and Verifiable Credentials.
 */
class auth_plugin_kipmi extends auth_plugin_base {
    /**
     * Constructor.
     *
     * Initializes the authentication plugin and loads its configuration.
     */
    public function __construct() {
        $this->authtype = 'kipmi';
        $this->config = get_config('auth_kipmi');
    }

    /**
     * Indicates if the username and password are stored in Moodle's database.
     *
     * For KIPMI wallet authentication, credentials are managed externally
     * through the wallet and authentication backend service.
     *
     * @return bool False, as this is an external authentication method
     */
    public function is_internal() {
        return false;
    }

    /**
     * Indicates if this authentication plugin allows password changes.
     *
     * KIPMI wallet authentication does not support password changes as
     * authentication is handled through verifiable credentials.
     *
     * @return bool False, password changes are not supported
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Checks if the plugin is properly configured.
     *
     * The plugin is considered configured if the VP Verifier URL setting
     * has been set by an administrator.
     *
     * @return bool True if vp_verifier_url is configured, false otherwise
     */
    public function is_configured() {
        return !empty($this->config->vp_verifier_url);
    }

    /**
     * Hook called on the login page to inject custom CSS or JavaScript.
     *
     * Currently not used, but available for future customization of the
     * login page appearance.
     *
     * @return void
     */
    public function loginpage_hook() {
        global $PAGE;
        // Add custom styles if needed
        // $PAGE->requires->css(new moodle_url('/auth/kipmi/styles/styles.css'));
    }

    /**
     * Returns a list of identity providers to display on the login page.
     *
     * This method provides the configuration for displaying a KIPMI wallet
     * login button on the Moodle login page. The button includes the configured
     * label, icon, and login URL.
     *
     * @param string $wantsurl The URL the user was trying to access before login
     * @return array Array of identity provider configurations, empty if not configured
     */
    public function loginpage_idp_list($wantsurl) {
        if (!$this->is_configured()) {
            return [];
        }

        $buttonlabel = !empty($this->config->button_label)
            ? $this->config->button_label
            : get_string('pluginname', 'auth_kipmi');

        $iconurl = new moodle_url('/auth/kipmi/pix/kipmi.png');

        $loginurl = new moodle_url('/auth/kipmi/login.php', [
            'sesskey' => sesskey(),
            'wantsurl' => empty($wantsurl) ? '/' : $wantsurl,
        ]);

        return [
            [
                'url' => $loginurl,
                'name' => $buttonlabel,
                'iconurl' => $iconurl,
            ],
        ];
    }

    /**
     * Synchronizes user data from external source.
     *
     * This method is not required for KIPMI wallet authentication as user
     * data is retrieved and updated at login time from verifiable credentials.
     * No periodic synchronization is needed.
     *
     * @param bool $do_updates Whether to update user records (unused)
     * @return bool Always returns true
     */
    public function sync_users($do_updates = true) {
        return true;
    }
}
