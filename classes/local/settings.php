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
 * Plugin settings helpers.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_vibecheck\local;

/**
 * Read block settings.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {
    /**
     * Whether the block should force the Boost blocks drawer open.
     *
     * Unset means on, which is what production did before the setting existed.
     *
     * @return bool
     */
    public static function force_drawer(): bool {
        $value = get_config('block_vibecheck', 'force_drawer');
        if ($value === false) {
            return true;
        }
        return $value === '1';
    }
}
