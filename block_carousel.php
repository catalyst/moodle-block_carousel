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
 * Carousel block
 *
 * @package   block_carousel
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Carousel block
 *
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_carousel extends block_base {

    /**
     * Init
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_carousel');
    }

    /**
     * Can appear on any page
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Hide the header
     * @return boolean
     */
    public function hide_header() {
        return true;
    }
    /**
     * Unless we are in editing mode, remove all visual block chrome
     *
     * @return array attribute name => value.
     */
    public function html_attributes() {
        if ($this->page->user_is_editing()) {
            return parent::html_attributes();
        }
        $attributes = array(
            'id' => 'inst' . $this->instance->id,
            'class' => 'block_' . $this->name(),
            'role' => $this->get_aria_role()
        );
        return $attributes;
    }

    /**
     * We could have multiple carousels
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * The html for the carousel
     */
    public function get_content() {
        global $CFG, $DB, $PAGE;

        require_once($CFG->libdir . '/filelib.php');

        $blockid = $this->context->id;
        $html = html_writer::start_tag('div', array('id' => 'carousel' . $blockid));

        if ($this->content !== null) {
            return $this->content;
        }

        $config = $this->config;
        $this->content = new stdClass;

        if (empty($config) || empty($config->order)) {
            $this->content->text = '';
            return $this->content;
        }

        $fs = get_file_storage();

        $height = $config->height;

        // Default value.
        if (empty($height)) {
            $height = "50%";
        }

        // TODO : Cache ordering and records.
        $order = explode(',', $config->order);
        $numslides = count($order);
        foreach ($order as $slideid) {
            $slide = $DB->get_record('block_carousel', ['id' => $slideid]);

            if ($slide->disabled) {
                continue;
            }

            $title = $slide->title;
            $text = $slide->text;
            $url = $slide->url;
            $modalcontent = $slide->modalcontent;
            $html .= html_writer::start_tag('div'); // This will be modified by slick.
            $files   = $fs->get_area_files($this->context->id, 'block_carousel', 'content', $slideid);

            $image = '';
            foreach ($files as $file) {

                if ($file->get_filesize() == 0) {
                    continue; // TODO fix the broken dud records.
                }

                $image = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                        $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

                $imageinfo = $file->get_imageinfo();
                if (isset($imageinfo["width"]) && isset($imageinfo["height"])) {
                    preg_match('!\d+!', $height, $matches);
                    if (isset($matches[0])) {
                        $heightvalue = $matches[0];
                        $unit = trim(str_replace($heightvalue, '', $height));
                        $ratio = ($imageinfo["width"] / $imageinfo["height"]);
                        $paddingbottom = (round((1 / $ratio), 4) * 100) . '%';
                        $width = (round(($ratio * $heightvalue), 2)) . $unit;
                    }
                } else {
                    $paddingbottom = $height;
                }
            }

            // Wrapping the slide in an object is a neat trick allowing the slide to be a link
            // and for the text within it to also have sub-links.
            if ($modalcontent || $url) {
                $attr = [
                    'class' => 'slidelink',
                    'id' => 'id_slide' . $slideid
                ];
                if ($modalcontent) {
                    $PAGE->requires->js_call_amd('block_carousel/modal', 'init', [$slideid, $modalcontent]);
                } else if ($url) {
                    $attr['href'] = $url;
                    if ($slide->newtab) {
                        $attr['target'] = '_blank';
                    }
                }

                $html .= html_writer::start_tag('a', $attr);
                // Add interaction event listener on the a tag.
                $PAGE->requires->js_call_amd('block_carousel/interaction', 'init', [$slideid]);
                $html .= html_writer::start_tag('object');
            }
            $show = ($numslides == 0) ? 'block' : 'none';

            if (!empty($width)) {
                $html .= html_writer::start_tag('div', array('style' => "max-width: $width; margin: auto;"));
            }
            $html .= html_writer::start_tag('div', array(
                'class' => 'slidewrap',
                'style' => "padding-bottom: $paddingbottom; background-image: url($image); display: $show;"
            ));
            if ($title) {
                $html .= html_writer::tag('h4', $title, array('class' => 'title'));
            }
            if ($text) {
                $html .= html_writer::tag('div', $text, array('class' => 'text'));
            }
            $html .= html_writer::end_tag('div');
            if (!empty($width)) {
                $html .= html_writer::end_tag('div');
            }
            if ($url) {
                $html .= html_writer::end_tag('object');
                $html .= html_writer::end_tag('a');
            }
            $html .= html_writer::end_tag('div');
        }

        $this->page->requires->css('/blocks/carousel/extlib/slick-1.5.9/slick/slick.css');
        $this->page->requires->css('/blocks/carousel/extlib/slick-1.5.9/slick/slick-theme.css');
        $this->page->requires->js_call_amd('block_carousel/carousel', 'init', array($blockid, $config->playspeed * 1000));

        $html .= html_writer::end_tag('div');
        $this->content->text = $html;

        return $this->content;
    }

    /**
     * Can never be docked
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return false;
    }

    /**
     * Serialize and store config data
     * @param object $data Form data
     * @param boolean $nolongerused boolean Not used
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = new stdClass();
        $config->height = $data->height;
        $config->playspeed = $data->playspeed;
        // Saving needs to maintain order.
        if (!empty($this->config) && !empty($this->config->order)) {
            $config->order = $this->config->order;
        }
        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Delete an instance
     */
    public function instance_delete() {
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_carousel');
        return true;
    }
}
