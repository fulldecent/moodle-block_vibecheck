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
 * Study companion block.
 *
 * @package    block_vibecheck
 * @copyright  2025 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Focus tools, a breathing exercise, and an experience rating.
 *
 * @package    block_vibecheck
 * @copyright  2025 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_vibecheck extends block_base {
    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_vibecheck');
    }

    /**
     * The block has an admin setting.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * The block can be added on any page.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Build the block body.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $showrating = \block_vibecheck\local\eligibility::should_show((int) $USER->id);
        $this->content->text = $OUTPUT->render_from_template('block_vibecheck/content', [
            'showrating' => $showrating,
            'wwwroot' => $CFG->wwwroot,
            'sesskey' => sesskey(),
            'forcedrawer' => \block_vibecheck\local\settings::force_drawer() ? '1' : '0',
            'instanceid' => html_writer::random_id('vibecheck'),
        ]);
        return $this->content;
    }
}
