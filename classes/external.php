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
 * External API for block_vibecheck.
 *
 * @package    block_vibecheck
 * @copyright  2025 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vibecheck;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External functions for the study companion block.
 *
 * @package    block_vibecheck
 * @copyright  2025 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    /**
     * Returns description of update_vibe parameters.
     *
     * @return external_function_parameters
     */
    public static function update_vibe_parameters() {
        return new external_function_parameters([
            'rating' => new external_value(PARAM_ALPHA, 'The vibe rating (GOOD or BAD)'),
        ]);
    }

    /**
     * Store the user's vibe rating on their profile.
     *
     * @param string $rating The vibe rating (GOOD or BAD)
     * @return array Operation result
     */
    public static function update_vibe($rating) {
        global $USER;

        $params = self::validate_parameters(self::update_vibe_parameters(), [
            'rating' => $rating,
        ]);

        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('moodle/user:editownprofile', $context);

        if ($params['rating'] !== 'GOOD' && $params['rating'] !== 'BAD') {
            throw new \invalid_parameter_exception('Rating must be either GOOD or BAD');
        }

        $result = \block_vibecheck\local\profile_fields::save_rating((int) $USER->id, $params['rating']);

        return [
            'status' => 'success',
            'result' => $result,
        ];
    }

    /**
     * Returns description of update_vibe_returns.
     *
     * @return external_single_structure
     */
    public static function update_vibe_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Operation status'),
            'result' => new external_single_structure([
                'VIBE_UPDATED_AT' => new external_value(PARAM_TEXT, 'Result of updating timestamp'),
                'VIBE_RATING' => new external_value(PARAM_TEXT, 'Result of updating rating'),
            ]),
        ]);
    }
}
