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
 * Data source for slide cache
 *
 * @package   block_carousel
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT 2020
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_carousel\cache;

defined('MOODLE_INTERNAL') || die();

class slide_cache implements \cache_data_source {
    /** @var experiment_cache the singleton instance of this class. */
    protected static $slidecache = null;

    /**
     * Returns the instance of cache definition
     *
     * @param string $definition the definition of the cache
     * @return mixed The singleton instance of the cache data source
     */
    public static function get_instance_for_cache(\cache_definition $definition) {
        if (is_null(self::$slidecache)) {
            self::$slidecache = new slide_cache();
        }
        return self::$slidecache;
    }

    /**
     * Returns an array of data for given key
     *
     * @param string $key the key to get the
     * @return mixed A data array of all data for key or false if key not found
     */
    public function load_for_cache($key) {
        global $DB;

        $record = $DB->get_record('block_carousel', ['id' => $key]);

        if (empty($record)) {
            return [];
        }

        $data = (array) $record;

        // If courseid is set, generate data based on course info.
        $iscourse = false;
        if (!empty($data['courseid'])) {
            $invalid = false;
            try {
                $course = get_course($data['courseid']);
            } catch (\dml_exception $e) {
                // The course was not found.
                $invalid = true;
            }
            // Ensure we have a good course before doing anything.
            if ($invalid) {
                $data['title'] = '';
                $data['text'] = '';
            } else {
                $iscourse = true;
                $data['title'] = empty($data['title']) ? $course->fullname : $data['title'];
                $data['text'] = empty($data['text']) ? $course->fullname : $data['text'];
            }
        }

        // If either text or title is disabled, override here.
        $data['title'] = $data['notitle'] ? '' : $data['title'];
        $data['text'] = $data['notext'] ? '' : $data['text'];

        // Find the relevant content for the slide.
        try {
            $context = \context_block::instance($record->blockid);
        } catch (\dml_missing_record_exception $e) {
            return [];
        }

        $selectedfile = null;
        $storage = get_file_storage();

        // Try to find any files in the direct file area first.
        $files = $storage->get_area_files($context->id, 'block_carousel', 'content', $key);
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $selectedfile = $file;
            }
        }

        // There was no image supplied, try to find course image.
        if (empty($selectedfile) && $iscourse) {
            $context = \context_course::instance($data['courseid']);
            $files = $storage->get_area_files(
                $context->id,
                'course',
                'overviewfiles'
            );
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    $selectedfile = $file;
                    break;
                }
            }
        }

        // Now we have the correct file.
        if (!empty($selectedfile)) {
            $itemid = empty($selectedfile->get_itemid()) ? null : $selectedfile->get_itemid();
            $data['link'] = \moodle_url::make_pluginfile_url(
                $selectedfile->get_contextid(),
                $selectedfile->get_component(),
                $selectedfile->get_filearea(),
                $itemid,
                $selectedfile->get_filepath(),
                $selectedfile->get_filename()
            );
        } else {
            $data['link'] = '';
        }

        // Cache information for layout.
        if (empty($selectedfile)) {
            $data['heightres'] = 0;
            $data['widthres'] = 0;
        } else if ($record->contenttype === 'image') {
            $imageinfo = $selectedfile->get_imageinfo();
            $data['heightres'] = $imageinfo['height'];
            $data['widthres'] = $imageinfo['width'];
        } else {
            // Use FFMpeg to get resolution.
            $path = $selectedfile->copy_content_to_temp();
            $pathtoffprobe = get_config('block_carousel', 'pathtoffprobe');

            if (empty($pathtoffprobe)) {
                // Default to 720p resolution. Most wide standard.
                $data['heightres'] = 720;
                $data['widthres'] = 1280;

            } else {
                $command = $pathtoffprobe . ' -of json -v error -show_format -show_streams ' .  escapeshellarg($path);
                $json = shell_exec($command);
                $rawresults = json_decode($json);
                // Just grab the first video stream.
                foreach ($rawresults->streams as $stream) {
                    if ($stream->codec_type === 'video') {
                        $data['heightres'] = $stream->height;
                        $data['widthres'] = $stream->width;
                        break;
                    }
                }
            }

            // Now unlink the path.
            unlink($path);
        }

        return $data;
    }

    /**
     * Returns an array of data for all given keys
     *
     * @param array $keys the keys of the datasets to be loaded
     * @return mixed A data array of all datasets
     */
    public function load_many_for_cache(array $keys) {
        // Return array of all data items.
        $data = array();
        foreach ($keys as $key) {
            $data[$key] = $this->load_for_cache($key);
        }
        return $data;
    }
}
