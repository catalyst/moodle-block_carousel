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
     * Allow global config
     *
     * @return boolean
     */
    public function has_config() {
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
        global $CFG, $USER, $DB;

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
        $height = $config->height ?? '50%';
        $autoplay = $config->autoplay ?? 1;
        $slides = $config->slides ?? 1;
        $playspeed = $config->playspeed ?? 4;

        $order = explode(',', $config->order);

        $cache = cache::make('block_carousel', 'slides');
        $data = $cache->get_many($order);

        // If its a multislide, we need to pick a consistent ratio.
        // Lets just pick the first slide.
        if ($slides > 1) {
            $firstslide = reset($data);
            $height = $firstslide['heightres'];
            if (empty($height)) {
                $ratio = 1;
            } else {
                $ratio = ($firstslide['widthres'] / $firstslide['heightres']);
            }
        }

        if (function_exists('cohort_get_user_cohorts')) {
            // In Moodle from 3.5.
            $cohorts = array_keys(cohort_get_user_cohorts($USER->id));
        } else if (function_exists('totara_cohort_get_user_cohorts')) {
            // In Totara.
            $cohorts = totara_cohort_get_user_cohorts($USER->id);
        } else {
            // Fallback if the above two functions are not available.
            $sql = 'SELECT c.id
              FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
             WHERE cm.userid = ?';
            $cohorts = array_keys($DB->get_records_sql($sql, array($USER->id)));
        }

        $numslides = count($order);
        foreach ($data as $slideid => $data) {
            if (empty($data)) {
                continue;
            }
            $data = (object) $data;
            if ($data->disabled) {
                continue;
            }

            // Filter any files that are not present or broken.
            if (is_null($data->heightres) && is_null($data->widthres) && $data->contenttype === 'image') {
                continue;
            }

            // Filter for cohorts. Admins can always see the slide. If cohort list is empty, then everyone can see it.
            if (!is_siteadmin($USER) && !empty($data->cohorts) && empty(array_intersect($cohorts, explode(',', $data->cohorts)))) {
                continue;
            }

            // Check release timing.
            if ((!empty($data->timedstart) && time() < $data->timedstart) ||
                (!empty($data->timedend) && time() > $data->timedend)) {
                continue;
            }

            $title = $data->title;
            $text = $data->text;
            $url = $data->url;
            $modalcontent = format_text($data->modalcontent);
            $contenttype = $data->contenttype;
            $html .= html_writer::start_tag('div', ['id' => 'id_slidecontainer' . $slideid]); // This will be modified by slick.

            $paddingbottom = $height;
            preg_match('!\d+!', $height, $matches);
            if (isset($matches[0])) {
                $heightvalue = $matches[0];
                $unit = trim(str_replace($heightvalue, '', $height));

                // If not a multislide, find the ratio of this one slide.
                if ($slides <= 1) {
                    if ($data->heightres === 0) {
                        $ratio = 1;
                    } else {
                        $ratio = ($data->widthres / $data->heightres);
                    }
                }

                $paddingbottom = (round((1 / $ratio), 4) * 100) . '%';
                $width = (round(($ratio * $heightvalue), 2)) . $unit;
            }

            // Wrapping the slide in an object is a neat trick allowing the slide to be a link
            // and for the text within it to also have sub-links.
            if ($modalcontent || $url) {
                $attr = [
                    'class' => 'slidelink',
                    'id' => 'id_slide' . $slideid
                ];
                if ($modalcontent) {
                    $this->page->requires->js_call_amd('block_carousel/carousel', 'modal', [$slideid, $title]);
                    $attr['style'] = 'cursor: pointer;';
                    $attr['data-modalcontent'] = $modalcontent;
                } else if ($url) {
                    $attr['href'] = $url;
                    if ($data->newtab) {
                        $attr['target'] = '_blank';
                    }
                }

                $html .= html_writer::start_tag('a', $attr);
                // Add interaction event listener on the a tag.
                $this->page->requires->js_call_amd('block_carousel/carousel', 'interaction', [$slideid]);
                $html .= html_writer::start_tag('object');
            }
            $show = ($numslides == 0) ? 'block' : 'none';

            if (!empty($width)) {
                $html .= html_writer::start_tag('div', array('style' => "max-width: {$width}; margin: auto;"));
            }

            $style = "padding-bottom: $paddingbottom; display: $show;";
            if ($contenttype === 'image') {
                $style .= " background-image: url($data->link);";
            }
            $html .= html_writer::start_tag('div', array(
                'class' => 'slidewrap',
                'style' => $style,
            ));
            if ($contenttype === 'video') {
                // Setup the video tag.
                $html .= html_writer::start_tag('video', [
                    'id' => 'id_slidevideo' . $slideid,
                    'style' => "max-width: 100%",
                    'muted' => 'muted',
                    'autoplay' => 'autoplay',
                    'loop' => 'loop',
                ]);
                $html .= html_writer::tag('source', null, ['src' => $data->link]);
                $html .= html_writer::end_tag('video');

                // Setup the video control JS.
                $this->page->requires->js_call_amd('block_carousel/carousel', 'videocontrol', [$blockid, $slideid]);
            }

            if ($title) {
                $class = 'title';
                $class = $slides > 1 ? $class . ' multislide' : $class;
                $html .= html_writer::tag('h4', $title, array('class' => $class));
            }
            if ($text) {
                $class = 'text';
                $class = $slides > 1 ? $class . ' multislide' : $class;
                $html .= html_writer::tag('div', $text, array('class' => $class));
            }
            $html .= html_writer::end_tag('div');
            if (!empty($width)) {
                $html .= html_writer::end_tag('div');
            }
            if ($modalcontent || $url) {
                $html .= html_writer::end_tag('object');
                $html .= html_writer::end_tag('a');
            }
            $html .= html_writer::end_tag('div');
        }

        $this->page->requires->css('/blocks/carousel/extlib/slick-1.8.1/slick/slick.css');
        $this->page->requires->css('/blocks/carousel/extlib/slick-1.8.1/slick/slick-theme.css');
        $this->page->requires->js_call_amd('block_carousel/carousel', 'init', [
            $blockid,
            $slides,
            (bool) $autoplay,
            $playspeed * 1000
        ]);

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
        $config->autoplay = $data->autoplay;
        $config->slides = $data->slides;
        $config->title = $data->title;
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

    public function specialization() {
        $name = $this->config->title ?? get_string('pluginname', 'block_carousel');
        $this->title = $name;
    }
}
