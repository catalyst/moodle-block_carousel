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
 * Form for editing a carousel block instance.
 *
 * @package   block_carousel
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/blocks/carousel/lib.php');

/**
 * Form for editing carousel block instances.
 *
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_carousel_edit_form extends block_edit_form {

    /**
     * Form def
     * @param object $mform the form being built.
     */
    protected function specific_definition($mform) {
        global $SESSION;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_carousel'));

        $mform->addElement('text', 'config_blockname', get_string('name'));
        $mform->setType('config_blockname', PARAM_TEXT);
        $mform->setDefault('config_blockname', get_string('pluginname', 'block_carousel'));

        $mform->addElement('text', 'config_height', get_string('configheight', 'block_carousel'));
        $mform->setType('config_height', PARAM_TEXT);
        $mform->setDefault('config_height', '50%');

        $mform->addElement('text', 'config_slides', get_string('configslides', 'block_carousel'));
        $mform->setType('config_slides', PARAM_INT);
        $mform->setDefault('config_slides', 1);

        $mform->addElement('advcheckbox', 'config_autoplay', get_string('configautoplay', 'block_carousel'));
        $mform->setType('config_autoplay', PARAM_BOOL);
        $mform->setDefault('config_autoplay', 1);

        $mform->addElement('text', 'config_playspeed', get_string('configplayspeed', 'block_carousel'));
        $mform->setType('config_playspeed', PARAM_FLOAT);
        $mform->setDefault('config_playspeed', '4');
        $mform->disabledIf('config_playspeed', 'config_autoplay');

        $mform->addElement('header', 'configheaderslides', get_string('slideheader', 'block_carousel'));
        $mform->setExpanded('configheaderslides');

        // Slides table.
        $blockid = $this->block->instance->id;
        // Setup the return url for slide actions.
        $editurl = "carousel_{$blockid}_editurl";
        $currurl = $this->page->url;
        // If bui_editid is set, we are on a standard block page. Add the param back in.
        if (!empty(optional_param('bui_editid', null, PARAM_INT))) {
            $currurl->param('bui_editid', required_param('bui_editid', PARAM_INT));
        }
        $SESSION->$editurl = $currurl;
        $table = new \block_carousel\output\slide_table('carousel_slides');
        $html = $table->out($blockid);
        $mform->addElement('html', $html);
    }
}
