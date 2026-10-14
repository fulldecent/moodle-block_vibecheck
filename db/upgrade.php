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
 * Upgrade the study companion block.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create profile fields that older copies made on the first rating.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_block_vibecheck_upgrade($oldversion) {
    if ($oldversion < 2026101300) {
        \block_vibecheck\local\profile_fields::ensure();
        if (get_config('block_vibecheck', 'force_drawer') === false) {
            set_config('force_drawer', '1', 'block_vibecheck');
        }
        upgrade_plugin_savepoint(true, 2026101300, 'block', 'vibecheck');
    }
    return true;
}
