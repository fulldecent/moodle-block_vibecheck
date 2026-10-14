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

namespace block_vibecheck;

use block_vibecheck\local\eligibility;
use block_vibecheck\local\profile_fields;

/**
 * Integration tests for when the rating is shown.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \block_vibecheck\local\eligibility
 */
final class eligibility_test extends \advanced_testcase {
    /**
     * A user with no enrolments is not asked for a rating.
     *
     * @covers ::should_show
     */
    public function test_no_enrolments(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->assertFalse(eligibility::should_show((int) $user->id));
    }

    /**
     * One unfinished course hides the rating.
     *
     * @covers ::should_show
     */
    public function test_incomplete_course_hides_rating(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->assertFalse(eligibility::should_show((int) $user->id));
    }

    /**
     * Every enrolled course must be complete, and a recent rating hides the prompt.
     *
     * @covers ::should_show
     */
    public function test_complete_courses_and_recent_rating(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $finished = $this->getDataGenerator()->create_course();
        $open = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $finished->id);
        $this->getDataGenerator()->enrol_user($user->id, $open->id);
        $this->mark_complete($user->id, $finished->id);
        $this->assertFalse(eligibility::should_show((int) $user->id));

        $this->mark_complete($user->id, $open->id);
        $this->assertTrue(eligibility::should_show((int) $user->id));

        profile_fields::save_rating((int) $user->id, 'GOOD');
        $this->assertFalse(eligibility::should_show((int) $user->id));

        $field = $DB->get_record('user_info_field', ['shortname' => profile_fields::UPDATED_AT], '*', MUST_EXIST);
        $DB->set_field('user_info_data', 'data', date('Y-m-d H:i:s', time() - eligibility::RECENT_SECONDS - 60), [
            'fieldid' => $field->id,
            'userid' => $user->id,
        ]);
        $this->assertTrue(eligibility::should_show((int) $user->id));
    }

    /**
     * The block body follows the same rule, and guests see nothing.
     *
     * @covers \block_vibecheck::get_content
     */
    public function test_block_content(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $guestcontent = $this->render_block();
        $this->assertSame('', $guestcontent->text);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $hidden = $this->render_block();
        $this->assertStringContainsString('Start Pomodoro Timer', $hidden->text);
        $this->assertStringContainsString('Start Breathing Exercise', $hidden->text);
        $this->assertStringNotContainsString('data-rating="GOOD"', $hidden->text);
        $this->assertStringContainsString('data-force-drawer="1"', $hidden->text);

        set_config('force_drawer', '0', 'block_vibecheck');
        $nodrawer = $this->render_block();
        $this->assertStringContainsString('data-force-drawer="0"', $nodrawer->text);

        $this->mark_complete($user->id, $course->id);
        $shown = $this->render_block();
        $this->assertStringContainsString('data-rating="GOOD"', $shown->text);
        $this->assertStringContainsString('How was your experience today?', $shown->text);
    }

    /**
     * Insert a completion row.
     *
     * @param int $userid The user id.
     * @param int $courseid The course id.
     */
    private function mark_complete(int $userid, int $courseid): void {
        global $DB;
        $DB->insert_record('course_completions', (object) [
            'userid' => $userid,
            'course' => $courseid,
            'timeenrolled' => time() - 100,
            'timestarted' => time() - 100,
            'timecompleted' => time() - 50,
            'reaggregate' => 0,
        ]);
    }

    /**
     * Render a fresh block instance.
     *
     * @return \stdClass
     */
    private function render_block(): \stdClass {
        $record = $this->getDataGenerator()->create_block('vibecheck');
        $block = block_instance_by_id($record->id);
        return $block->get_content();
    }
}
