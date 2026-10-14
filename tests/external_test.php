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

use block_vibecheck\local\profile_fields;

/**
 * Integration tests for the rating web service.
 *
 * @package    block_vibecheck
 * @copyright  2026 Modern Training Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \block_vibecheck\external
 */
final class external_test extends \advanced_testcase {
    /**
     * A student can save GOOD and then replace it with BAD.
     *
     * @covers ::update_vibe
     */
    public function test_update_vibe_writes_profile_fields(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $first = \block_vibecheck\external::update_vibe('GOOD');
        $this->assertSame('success', $first['status']);
        $this->assertSame('Value set', $first['result']['VIBE_RATING']);
        $this->assertSame('GOOD', profile_fields::get((int) $user->id, profile_fields::RATING));
        $this->assertNotNull(profile_fields::get((int) $user->id, profile_fields::UPDATED_AT));

        $second = \block_vibecheck\external::update_vibe('BAD');
        $this->assertSame('Value updated', $second['result']['VIBE_RATING']);
        $this->assertSame('BAD', profile_fields::get((int) $user->id, profile_fields::RATING));
    }

    /**
     * Anything other than GOOD or BAD is rejected.
     *
     * @covers ::update_vibe
     */
    public function test_update_vibe_rejects_other_ratings(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(\invalid_parameter_exception::class);
        \block_vibecheck\external::update_vibe('MAYBE');
    }

    /**
     * Guests cannot store a rating.
     *
     * @covers ::update_vibe
     */
    public function test_update_vibe_rejects_guests(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->expectException(\moodle_exception::class);
        \block_vibecheck\external::update_vibe('GOOD');
    }
}
