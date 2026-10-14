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
 * Web service definitions for block_vibecheck.
 *
 * @package    block_vibecheck
 * @copyright  2025 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_vibecheck_update_vibe' => [
        'classname' => 'block_vibecheck\external',
        'methodname' => 'update_vibe',
        'classpath' => '',
        'description' => 'Update the user vibe rating',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/user:editownprofile',
    ],
];
