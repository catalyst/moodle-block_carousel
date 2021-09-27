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
 * Carousel block backup stepslib class
 *
 * @package   block_carousel
 * @copyright 2021 Nicholas Hoobin (nicholashoobin@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Define the complete carousel structure for backup, with file and id annotations
 */
class backup_carousel_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Get the block.
        $block = $DB->get_record('block_instances', ['id' => $this->task->get_blockid()]);

        // Extract configdata.
        $config = unserialize(base64_decode($block->configdata));

        // Get array of slides.
        if (!empty($config->order)) {
            $slideids = explode(',', $config->order);
            // Get the IN corresponding query
            list($in_sql, $in_params) = $DB->get_in_or_equal($slideids);
            // Define all the in_params as sqlparams
            foreach ($in_params as $key => $value) {
                $in_params[$key] = backup_helper::is_sqlparam($value);
            }
        }

        // Define each element separated.
        $carousel = new backup_nested_element('carousel', ['id'], null);

        $slides = new backup_nested_element('slides');

        $slide = new backup_nested_element('slide', ['id'], [
            'blockid', 'title', 'text', 'url', 'contenttype', 'interactions', 'modalcontent', 'newtab',
            'disabled', 'timedstart', 'timedend', 'courseid', 'notitle', 'notext'
        ]);

        // Build the tree
        $carousel->add_child($slides);
        $slides->add_child($slide);

        // Define sources
        $carousel->set_source_array([(object)['id' => $this->task->get_blockid()]]);

        // Only if there are slides
        if (!empty($config->order)) {
            $slide->set_source_sql("
                SELECT *
                  FROM {block_carousel}
                 WHERE id $in_sql", $in_params);
        }

        // Annotations
        $contextid = $this->get_setting_value(backup::VAR_CONTEXTID);
        $slide->annotate_files('block_carousel', 'content', null, $contextid);

        // Return the root element (carousel), wrapped into standard block structure
        return $this->prepare_block_structure($carousel);
    }
}
