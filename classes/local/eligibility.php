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
 * Rules for showing the experience rating.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vibecheck\local;

/**
 * Show the rating after every enrolled course is complete, at most once a year.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eligibility {
    /** @var int Twelve 30-day months. Matches the production block. */
    public const RECENT_SECONDS = 12 * 30 * 24 * 60 * 60;

    /**
     * Whether the rating buttons should be shown.
     *
     * @param int $userid The user id.
     * @return bool
     */
    public static function should_show(int $userid): bool {
        global $DB;

        $courses = enrol_get_users_courses($userid, true);
        if (!$courses) {
            return false;
        }
        foreach ($courses as $course) {
            $completion = $DB->get_record('course_completions', [
                'userid' => $userid,
                'course' => $course->id,
            ]);
            if (!$completion || (int) $completion->timecompleted === 0) {
                return false;
            }
        }

        $updatedat = profile_fields::get($userid, profile_fields::UPDATED_AT);
        if ($updatedat === null) {
            return true;
        }
        $lastupdate = strtotime($updatedat);
        if ($lastupdate === false) {
            return true;
        }
        return $lastupdate <= time() - self::RECENT_SECONDS;
    }
}
