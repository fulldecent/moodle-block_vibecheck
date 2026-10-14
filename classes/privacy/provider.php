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
 * Privacy subsystem implementation for block_vibecheck.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vibecheck\privacy;

use block_vibecheck\local\profile_fields;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Ratings are stored in the user profile. This provider exports and deletes them.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The collection to add items to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_subsystem_link('core_user', [
            'rating' => 'privacy:metadata:vibecheck:rating',
            'userid' => 'privacy:metadata:vibecheck:userid',
            'timecreated' => 'privacy:metadata:vibecheck:timecreated',
        ], 'privacy:metadata:vibecheck');

        // The server does not send the rating. The browser loads this embed after the student opts in.
        $collection->add_external_location_link('youtube.com', [
            'embedurl' => 'privacy:metadata:youtube:embedurl',
        ], 'privacy:metadata:youtube');

        return $collection;
    }

    /**
     * Get the contexts that contain personal data for this user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contexts used by this plugin for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        if (profile_fields::get($userid, profile_fields::RATING) !== null) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * Get the users who have personal data in this context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        if (profile_fields::get((int) $context->instanceid, profile_fields::RATING) !== null) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export personal data for the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts for the user.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_USER || (int) $context->instanceid !== $userid) {
                continue;
            }
            $rating = profile_fields::get($userid, profile_fields::RATING);
            $updatedat = profile_fields::get($userid, profile_fields::UPDATED_AT);
            if ($rating === null && $updatedat === null) {
                continue;
            }
            writer::with_context($context)->export_data([], (object) [
                'rating' => $rating,
                'updatedat' => $updatedat,
            ]);
        }
    }

    /**
     * Delete all personal data in this context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        profile_fields::delete_for_user((int) $context->instanceid);
    }

    /**
     * Delete personal data for one user.
     *
     * @param approved_contextlist $contextlist The approved contexts for the user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && (int) $context->instanceid === $userid) {
                profile_fields::delete_for_user($userid);
            }
        }
    }

    /**
     * Delete personal data for each approved user in the context.
     *
     * @param approved_userlist $userlist The approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            if ((int) $userid === (int) $context->instanceid) {
                profile_fields::delete_for_user((int) $userid);
            }
        }
    }
}
