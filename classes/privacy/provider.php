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
 * Privacy Subsystem implementation for auth_kipmi.
 *
 * This plugin implements the Privacy API to declare what personal data is collected,
 * stored, and shared with external systems during the KIPMI wallet authentication process.
 *
 * @package    auth_kipmi
 * @copyright  2025 Tinqin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_kipmi\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
class provider implements
    // This plugin stores personal data in external systems.
    \core_privacy\local\metadata\provider,
    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about the data stored and shared by this plugin.
     *
     * This method describes:
     * 1. Personal data sent to the external authentication backend
     * 2. Data stored in Moodle session during authentication
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Describe data sent to external authentication backend.
        $collection->add_external_location_link(
            'auth_kipmi_backend',
            [
                'sessionid' => 'privacy:metadata:auth_kipmi_backend:sessionid',
                'personal_administrative_number' => 'privacy:metadata:auth_kipmi_backend:personal_administrative_number',
                'given_name' => 'privacy:metadata:auth_kipmi_backend:given_name',
                'family_name' => 'privacy:metadata:auth_kipmi_backend:family_name',
                'email' => 'privacy:metadata:auth_kipmi_backend:email',
            ],
            'privacy:metadata:auth_kipmi_backend'
        );

        // Describe temporary session data stored in Moodle session.
        $collection->add_session_store(
            'auth_kipmi_session',
            [
                'state' => 'privacy:metadata:auth_kipmi_session:state',
                'sessionid' => 'privacy:metadata:auth_kipmi_session:sessionid',
                'wantsurl' => 'privacy:metadata:auth_kipmi_session:wantsurl',
                'starttime' => 'privacy:metadata:auth_kipmi_session:starttime',
            ],
            'privacy:metadata:auth_kipmi_session'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * The KIPMI authentication plugin does not store user data in specific contexts
     * beyond what is handled by the core authentication system. User authentication
     * data is managed through the standard Moodle user table.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // This plugin does not store data in specific contexts.
        // All user data is in the core user table, which is handled by core privacy.
        return new contextlist();
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * The KIPMI authentication plugin does not store any user data beyond what is
     * managed by the core authentication system. All user profile data (name, email, etc.)
     * is stored in Moodle's user table and exported by core privacy providers.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // This plugin does not store any user data that needs to be exported.
        // User authentication data is handled by core.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * The KIPMI authentication plugin does not store any plugin-specific user data
     * in contexts, so there is nothing to delete beyond core user data.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // This plugin does not store any context-specific data.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * The KIPMI authentication plugin does not store any plugin-specific user data,
     * so there is nothing to delete beyond what core handles.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // This plugin does not store any user-specific data that needs to be deleted.
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // This plugin does not store any context-specific user data.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // This plugin does not store any user-specific data that needs to be deleted.
    }
}
