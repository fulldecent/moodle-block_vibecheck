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
 * Profile fields that store a student's vibe rating.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vibecheck\local;

/**
 * Read and write the VIBE_RATING and VIBE_UPDATED_AT profile fields.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_fields {
    /** @var string Profile field for the latest rating. */
    public const RATING = 'VIBE_RATING';

    /** @var string Profile field for the time of the latest rating. */
    public const UPDATED_AT = 'VIBE_UPDATED_AT';

    /**
     * Shortnames this plugin owns.
     *
     * @return string[]
     */
    public static function shortnames(): array {
        return [self::RATING, self::UPDATED_AT];
    }

    /**
     * Create the profile fields when they are missing.
     *
     * Production created these on the first rating. Install and upgrade do it
     * first so a student request is only a value write.
     */
    public static function ensure(): void {
        global $DB;

        $categoryid = self::category_id();
        $sortorder = (int) $DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_field}');
        foreach (self::shortnames() as $shortname) {
            if ($DB->record_exists('user_info_field', ['shortname' => $shortname])) {
                continue;
            }
            $sortorder++;
            $field = (object) [
                'shortname' => $shortname,
                'name' => $shortname,
                'datatype' => 'text',
                'description' => '',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $categoryid,
                'sortorder' => $sortorder,
                'required' => 0,
                'locked' => 0,
                'visible' => \PROFILE_VISIBLE_NONE,
                'forceunique' => 0,
                'signup' => 0,
                'defaultdata' => '',
                'defaultdataformat' => 0,
                'param1' => '30',
                'param2' => '2048',
                'param3' => null,
                'param4' => null,
                'param5' => null,
            ];
            $DB->insert_record('user_info_field', $field);
        }
    }

    /**
     * Read one profile value for a user.
     *
     * @param int $userid The user id.
     * @param string $shortname VIBE_RATING or VIBE_UPDATED_AT.
     * @return string|null The stored value, or null when unset.
     */
    public static function get(int $userid, string $shortname): ?string {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
        if (!$field) {
            return null;
        }
        $value = $DB->get_field('user_info_data', 'data', [
            'fieldid' => $field->id,
            'userid' => $userid,
        ]);
        if ($value === false || $value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    /**
     * Store a GOOD or BAD rating and the current timestamp.
     *
     * @param int $userid The user id.
     * @param string $rating GOOD or BAD.
     * @return array<string,string> Per-field result messages.
     */
    public static function save_rating(int $userid, string $rating): array {
        $updatedat = date('Y-m-d H:i:s');
        return [
            self::UPDATED_AT => self::set($userid, self::UPDATED_AT, $updatedat),
            self::RATING => self::set($userid, self::RATING, $rating),
        ];
    }

    /**
     * Delete both values for one user.
     *
     * @param int $userid The user id.
     */
    public static function delete_for_user(int $userid): void {
        global $DB;

        foreach (self::shortnames() as $shortname) {
            $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
            if (!$field) {
                continue;
            }
            $DB->delete_records('user_info_data', [
                'fieldid' => $field->id,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Write one profile value.
     *
     * @param int $userid The user id.
     * @param string $shortname The profile field shortname.
     * @param string $value The value to store.
     * @return string Value set, or Value updated.
     */
    private static function set(int $userid, string $shortname, string $value): string {
        global $DB;

        self::ensure();
        $field = $DB->get_record('user_info_field', ['shortname' => $shortname], '*', MUST_EXIST);
        $data = $DB->get_record('user_info_data', [
            'fieldid' => $field->id,
            'userid' => $userid,
        ]);
        if ($data) {
            $data->data = $value;
            $DB->update_record('user_info_data', $data);
            return 'Value updated';
        }
        $data = (object) [
            'userid' => $userid,
            'fieldid' => $field->id,
            'data' => $value,
            'dataformat' => 0,
        ];
        $DB->insert_record('user_info_data', $data);
        return 'Value set';
    }

    /**
     * Use the first profile category, or create one.
     *
     * @return int
     */
    private static function category_id(): int {
        global $DB;

        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC', 'id', 0, 1);
        if ($categories) {
            $category = reset($categories);
            return (int) $category->id;
        }
        return (int) $DB->insert_record('user_info_category', (object) [
            'name' => 'Vibe check',
            'sortorder' => 1,
        ]);
    }
}
