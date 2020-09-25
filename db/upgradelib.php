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
 * Carousel block upgrade script
 *
 * @package     block_carousel
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   Catalyst IT Australia
 */

defined('MOODLE_INTERNAL') || die();

class block_carousel_upgrade_helper {
    public static function upgrade_carousels_to_db() {
        global $DB;

        $carousels = $DB->get_recordset('block_instances', ['blockname' => 'carousel']);
        if ($carousels->valid()) {
            $storage = get_file_storage();

            foreach ($carousels as $carousel) {
                $configdata = unserialize(base64_decode($carousel->configdata));
                $ids = [];

                foreach ($configdata->image as $i => $imageid) {
                    $newdata = new stdClass();
                    $newdata->blockid = $carousel->id;

                    if (!empty($configdata->title[$i])) {
                        $newdata->title = $configdata->title[$i];
                    }
                    if (!empty($configdata->text[$i])) {
                        $newdata->text = $configdata->text[$i];
                    }
                    if (!empty($configdata->url[$i])) {
                        $newdata->url = $configdata->url[$i];
                    }

                    $newdata->newtab = 1;
                    $newdata->disabled = 0;
                    $newdata->interactions = 0;
                    $newdata->modalcontent = '';
                    $newdata->contenttype = 'image';
                    $newdata->timedstart = 0;
                    $newdata->timedend = 0;

                    $id = $DB->insert_record('block_carousel', $newdata, true);
                    $context = \context_block::instance($carousel->id);

                    // Now migrate the image across the new filearea, and delete the old one.
                    $files = $storage->get_area_files(
                        $context->id,
                        'block_carousel',
                        'slide',
                        $i
                    );
                    foreach ($files as $file) {
                        $storage->create_file_from_storedfile(['itemid' => $id, 'filearea' => 'content'], $file);
                    }
                    // Now cleanup the original file.
                    $storage->delete_area_files(
                        $context->id,
                        'block_carousel',
                        'slide',
                        $i
                    );

                    $ids[] = $id;
                }

                // Now delete unnecessary config, and store the order.
                unset($configdata->title);
                unset($configdata->text);
                unset($configdata->url);
                unset($configdata->image);
                $configdata->order = implode(',', $ids);
                $configdata->autoplay = 1;
                $configdata->slides = 1;

                $encoded = base64_encode(serialize($configdata));
                $DB->set_field('block_instances', 'configdata', $encoded, ['id' => $carousel->id]);
            }
        }
        $carousels->close();
    }
}
