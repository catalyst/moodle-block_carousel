<?php
// This file is part of Moodle - https://moodle.org/
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

namespace block_carousel;

/**
 * Event observers.
 *
 * @package   block_carousel
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2023, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * Removes deleted cohorts from carousel slides.
     *
     * @param \core\event\cohort_deleted $event
     */
    public static function on_cohort_deleted(\core\event\cohort_deleted $event) {
        global $DB;

        $deletedcohorts = [$event->objectid];
        $images = $DB->get_records('block_carousel');
        $invalidids = [];
        foreach ($images as $image) {
            // Skip if no cohorts.
            if ($image->cohorts !== '') {
                $cohorts = explode(',', $image->cohorts);
                $newcohorts = array_diff($cohorts, $deletedcohorts);
                // Update record if something has been removed.
                if ($newcohorts !== $cohorts) {
                    $image->cohorts = implode(',', $newcohorts);
                    $DB->update_record('block_carousel', $image, true);
                    $invalidids[] = $image->id;
                }
            }
        }
        // Invalidate cache for affected records.
        \cache_helper::invalidate_by_definition('block_carousel', 'slides', [], $invalidids);
    }
}
