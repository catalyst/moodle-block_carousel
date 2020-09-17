<?php

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
                        $storage->create_file_from_storedfile(['itemid' => $id, 'filearea' => 'image'], $file);
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

                $encoded = base64_encode(serialize($configdata));
                $DB->set_field('block_instances', 'configdata', $encoded, ['id' => $carousel->id]);
            }
        }
        $carousels->close();
    }
}
