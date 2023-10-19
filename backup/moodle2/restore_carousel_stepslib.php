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
 * Carousel block restore stepslib class
 *
 * @package   block_carousel
 * @copyright 2021 Nicholas Hoobin (nicholashoobin@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Define the complete carousel structure for restore
 */
class restore_carousel_block_structure_step extends restore_structure_step {

    /** @var array Slide map. */
    private $slidemapping;

    /**
     * Returns the structure to be processed by this restore_step.
     *
     * @return array
     */
    protected function define_structure() {
        return [
            new restore_path_element('block', '/block', true),
            new restore_path_element('carousel', '/block/carousel'),
            new restore_path_element('slide', '/block/carousel/slides/slide')
        ];
    }

    /**
     * Processes the data for the block.
     *
     * @param array|object $data
     */
    public function process_block($data) {
        global $DB;

        $data = (object)$data;
        $slidesarr = []; // To accumulate the slide IDs. Referenced with the old slide ID as the array key.

        // For any reason (non multiple, dupe detected...) block not restored, return.
        if (!$this->task->get_blockid()) {
            return;
        }

        // Iterate over all the slide elements.
        if (isset($data->carousel['slides']['slide'])) {
            foreach ($data->carousel['slides']['slide'] as $slide) {
                $slide = (object)$slide;
                $previd = $slide->id;

                // Update the blockid reference.
                $slide->blockid = $this->task->get_blockid();

                $slideid = $DB->insert_record('block_carousel', $slide);

                $slidesarr[$previd] = $slideid;
            }
        }

        // Save the mapping to reuse in the after_execute step.
        $this->slidemapping = $slidesarr;

        // If no slides are present (e.g. user has not configured the carousel), return early.
        if (empty($slidesarr)) {
            return;
        }

        // Syncs the order for the carousel block.
        $this->update_slide_references($slidesarr);
    }

    /**
     * This method will be executed after the whole structure step have been processed
     *
     * After execution method for code needed to be executed after the whole structure
     * has been processed. Useful for cleaning tasks, files process and others. Simply
     * overwrite in in your steps if needed
     */
    protected function after_execute() {
        global $DB;

        $this->add_related_files('block_carousel', 'content', null);

        $contextid = $this->task->get_contextid();

        $params = [
            'contextid' => $contextid,
            'component' => 'block_carousel',
            'filearea' => 'content',
        ];

        $files = $DB->get_recordset('files', $params);

        foreach ($files as $file) {
            if (in_array($file->itemid, array_keys($this->slidemapping))) {
                // Update the itemid to the new mapping.
                $file->itemid = $this->slidemapping[$file->itemid];

                // Update the pathname hash as the itemid has changed.
                $file->pathnamehash = sha1("/{$file->contextid}/{$file->component}/{$file->filearea}/{$file->itemid}"
                        .$file->filepath.$file->filename);

                $DB->update_record('files', $file);
            }
        }

        $files->close();
    }

    /**
     * Adjust the serialized configdata->order to the created/mapped feeds.
     *
     * @param array $slidesarr
     */
    private function update_slide_references(array $slidesarr) {
        global $DB;

        $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $this->task->get_blockid()]);
        $config = unserialize(base64_decode($configdata));
        if ($config === false) {
            return;
        }

        // If there are no slides or the order is not defined, there is nothing to update, return early.
        if (empty($slidesarr) || !isset($config->order)) {
            return;
        }

        // Update the slide references to the new slides (preserving original order).
        $oldorder = explode(',', $config->order);
        $neworder = [];
        foreach ($oldorder as $oldid) {
            $neworder[] = $slidesarr[$oldid];
        }

        // Set csv of new slide order.
        $config->order = implode(',', $neworder);
        $configdata = base64_encode(serialize($config));

        $DB->set_field('block_instances', 'configdata', $configdata, ['id' => $this->task->get_blockid()]);
    }
}
