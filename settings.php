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
 * Admin settings for the KIPMI Wallet authentication plugin.
 *
 * This file defines the configuration settings available to administrators
 * for managing the KIPMI wallet authentication plugin, including backend URL,
 * button labels, user mapping, auto-creation, and SSL verification options.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Backend URL setting
    $settings->add(new admin_setting_configtext(
        'auth_kipmi/backend_url',
        get_string('backend_url', 'auth_kipmi'),
        get_string('backend_url_desc', 'auth_kipmi'),
        '', // No default - must be configured
        PARAM_URL
    ));

    // Button label setting
    $settings->add(new admin_setting_configtext(
        'auth_kipmi/button_label',
        get_string('button_label', 'auth_kipmi'),
        get_string('button_label_desc', 'auth_kipmi'),
        'Login with KIPMI',
        PARAM_TEXT
    ));

    // User mapping field setting
    $settings->add(new admin_setting_configselect(
        'auth_kipmi/map_field',
        get_string('map_field', 'auth_kipmi'),
        get_string('map_field_desc', 'auth_kipmi'),
        'idnumber',
        [
            'idnumber' => get_string('idnumber'),
            'username' => get_string('username'),
            'email' => get_string('email'),
        ]
    ));

    // Auto-create users setting
    $settings->add(new admin_setting_configcheckbox(
        'auth_kipmi/autocreate',
        get_string('autocreate', 'auth_kipmi'),
        get_string('autocreate_desc', 'auth_kipmi'),
        1
    ));

    // SSL verification setting
    $settings->add(new admin_setting_configcheckbox(
        'auth_kipmi/ssl_verify',
        get_string('ssl_verify', 'auth_kipmi'),
        get_string('ssl_verify_desc', 'auth_kipmi'),
        1
    ));

    // Default first name setting
    $settings->add(new admin_setting_configtext(
        'auth_kipmi/default_firstname',
        get_string('default_firstname', 'auth_kipmi'),
        get_string('default_firstname_desc', 'auth_kipmi'),
        'KIPMI',
        PARAM_TEXT
    ));

    // Default last name setting
    $settings->add(new admin_setting_configtext(
        'auth_kipmi/default_lastname',
        get_string('default_lastname', 'auth_kipmi'),
        get_string('default_lastname_desc', 'auth_kipmi'),
        'User',
        PARAM_TEXT
    ));
}
