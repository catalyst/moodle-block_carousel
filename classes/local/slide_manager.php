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
 * Slide manager for block_carousel
 *
 * @package   block_carousel
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT 2020
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_carousel\local;

defined('MOODLE_INTERNAL') || die();

class slide_manager {

    /**
     * Gets the current config for a block.
     *
     * @param int $blockid the block to get config for.
     * @return stdClass the current block config.
     */
    public static function get_current_config($blockid) {
        global $DB;

        $blockrec = $DB->get_record('block_instances', ['id' => $blockid]);
        $config = unserialize(base64_decode($blockrec->configdata));
        return empty($config) ? new \stdClass() : $config;
    }

    /**
     * Writes the current config for a block.
     *
     * @param int $blockid the block to write config for.
     * @param stdClass the current block config.
     * @return void
     */
    public static function write_config($blockid, $config) {
        global $DB;

        $encoded = base64_encode(serialize($config));
        $DB->set_field('block_instances', 'configdata', $encoded, ['id' => $blockid]);
    }

    /**
     * Get the current block ordering.
     *
     * @param int $blockid the block to get ordering for.
     * @return array
     */
    public static function get_current_order($blockid) {
        $config = self::get_current_config($blockid);
        return empty($config->order) ? [] : explode(',', $config->order);
    }

    /**
     * Inserts a slide id into the ordering.
     *
     * @param int $blockid the block to modify.
     * @param int $id the slide id to add.
     * @return void
     */
    public static function add_id_to_order($blockid, $id) {
        $config = self::get_current_config($blockid);
        $order = self::get_current_order($blockid);

        array_push($order, $id);
        $config->order = implode(',', $order);
        self::write_config($blockid, $config);
    }

    /**
     * Removes a slide id from the ordering.
     *
     * @param int $blockid the block to modify.
     * @param int $id the slide id to remove.
     * @return void
     */
    public static function remove_id_from_order($blockid, $id) {
        $config = self::get_current_config($blockid);
        $order = self::get_current_order($blockid);

        $removed = array_values(array_diff($order, [(string) $id]));
        $config->order = implode(',', $removed);
        self::write_config($blockid, $config);
    }

    /**
     * Moves a slideid in the ordering.
     *
     * @param int $blockid the block to modify.
     * @param int $id the slide id to move.
     * @param int $position the number of positions to move
     * @return bool
     */
    public static function move_order_position($blockid, $rowid, $position) {
        // Force Rowid to string if int.
        $rowid = (string) $rowid;

        // Get current block config.
        $config = self::get_current_config($blockid);
        $origorder = self::get_current_order($blockid);

        // Nothing to do if there is only one element in order.
        if (count($origorder) === 1) {
            return false;
        }

        $currentpos = array_search($rowid, $origorder);

        // Split the arrays at the position to insert at.
        // If position is positive, we need to increment to fight slice off by one.
        if ($position > 0) {
            $position++;
        }
        $first = array_slice($origorder, 0, $currentpos + $position);
        $second = array_slice($origorder, $currentpos + $position);

        // Remove the current rowid entry, wherever it is.
        $first = array_values(array_diff($first, [$rowid]));
        $second = array_values(array_diff($second, [$rowid]));

        // Finally stitch back together with element in the middle.
        array_push($first, $rowid);
        $config->order = implode(',', array_merge($first, $second));
        self::write_config($blockid, $config);
        return true;
    }

    /**
     * Records an interaction with a slide.
     *
     * @param int $rowid the row to record for.
     * @return bool
     */
    public static function record_interaction($rowid) {
        global $DB;

        $curr = $DB->get_field('block_carousel', 'interactions', ['id' => $rowid]);
        if ($curr === false) {
            return false;
        }
        return $DB->set_field('block_carousel', 'interactions', $curr + 1, ['id' => $rowid]);
    }

    /**
     * Enables a slide.
     *
     * @param int $rowid the row to enable.
     * @return void
     */
    public static function enable_slide($rowid) {
        global $DB;

        $DB->set_field('block_carousel', 'disabled', 0, ['id' => $rowid]);
        // Invalidate cache for this record.
        \cache_helper::invalidate_by_definition('block_carousel', 'slides', [], [(string) $rowid]);
    }

    /**
     * Disables a slide.
     *
     * @param int $rowid the row to disable.
     * @return void
     */
    public static function disable_slide($rowid) {
        global $DB;

        $DB->set_field('block_carousel', 'disabled', 1, ['id' => $rowid]);
        // Invalidate cache for this record.
        \cache_helper::invalidate_by_definition('block_carousel', 'slides', [], [(string) $rowid]);
    }
}
