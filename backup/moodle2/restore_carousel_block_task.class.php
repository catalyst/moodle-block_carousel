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
 * Carousel block restore task class
 *
 * @package   block_carousel
 * @copyright 2021 Nicholas Hoobin (nicholashoobin@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/carousel/backup/moodle2/restore_carousel_stepslib.php'); // We have structure steps.

/**
 * Specialised restore task for the carousel block.
 *
 * @package   block_carousel
 * @copyright 2021 Nicholas Hoobin (nicholashoobin@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_carousel_block_task extends restore_block_task {

    /**
     * Define (add) particular settings that each block can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps that each block can have
     */
    protected function define_my_steps() {
        // Block carousel has one structure step.
        $this->add_step(new restore_carousel_block_structure_step('carousel_structure', 'carousel.xml'));
    }

    /**
     * Define one array() of fileareas that each block controls
     */
    public function get_fileareas() {
        return ['content'];
    }

    /**
     * Define one array() of configdata attributes
     * that need to be processed by the contenttransformer
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        return [];
    }
}
