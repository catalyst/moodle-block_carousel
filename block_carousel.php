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
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');


        $blockid = $this->context->id;
        $html = html_writer::start_tag('div', array('id' => 'carousel' . $blockid));

        $html .= "blockid $blockid";

        if ($this->content !== null) {
            return $this->content;
        }

        $config = $this->config;
        $this->content = new stdClass;

        if (empty($config)) {
            $this->content->text = '';
            return $this->content;
        }

        for($c=0; $c < sizeof($config->title); $c++) {
            $title = $config->title[$c];
            $text = $config->text[$c];
            $url = $config->url[$c];
            $html .= "<div>
==
$title
$text
$url
</div>";
        }

        $this->page->requires->css('/blocks/carousel/extlib/slick-1.5.9/slick/slick.css');
        $this->page->requires->css('/blocks/carousel/extlib/slick-1.5.9/slick/slick-theme.css');
        $this->page->requires->js_call_amd('block_carousel/carousel', 'init', array($blockid));

        $html .= '</div>';
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

}

