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

namespace block_vibecheck\privacy;

use block_vibecheck\local\profile_fields;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Integration tests for privacy export and delete.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \block_vibecheck\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The metadata names the profile fields and the optional YouTube embed.
     *
     * @covers ::get_metadata
     */
    public function test_metadata_lists_youtube(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('block_vibecheck'));
        $items = [];
        foreach ($collection->get_collection() as $item) {
            $items[$item->get_name()] = $item;
        }
        $this->assertArrayHasKey('core_user', $items);
        $this->assertArrayHasKey('www.youtube-nocookie.com', $items);
        $youtube = $items['www.youtube-nocookie.com'];
        $this->assertInstanceOf(\core_privacy\local\metadata\types\external_location::class, $youtube);
        $this->assertSame('privacy:metadata:youtube', $youtube->get_summary());
        $this->assertSame('privacy:metadata:youtube:embedurl', $youtube->get_privacy_fields()['embedurl']);
    }

    /**
     * Export and delete the rating stored for one user.
     *
     * @covers ::get_contexts_for_userid
     * @covers ::export_user_data
     * @covers ::delete_data_for_user
     */
    public function test_export_and_delete(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        profile_fields::save_rating((int) $user->id, 'GOOD');
        $this->setUser($other);
        profile_fields::save_rating((int) $other->id, 'BAD');

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertCount(1, $contextlist->get_contexts());

        writer::reset();
        $approved = new approved_contextlist($user, 'block_vibecheck', $contextlist->get_contextids());
        provider::export_user_data($approved);
        $context = $contextlist->get_contexts()[0];
        $data = writer::with_context($context)->get_data([]);
        $this->assertSame('GOOD', $data->rating);

        provider::delete_data_for_user($approved);
        $this->assertNull(profile_fields::get((int) $user->id, profile_fields::RATING));
        $this->assertSame('BAD', profile_fields::get((int) $other->id, profile_fields::RATING));
    }
}
