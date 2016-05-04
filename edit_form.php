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

/**
 * Form for editing carousel block instances.
 *
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_carousel_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_carousel'));

        $options = array();
        $slidegroup = array();

        // $slidegroup[] = $mform->createElement('header', 'slide_header', get_string('slideheader', 'block_carousel'));
        // $options['config_header']['expanded'] = 'slide_header';
        // $mform->setExpanded('foo')

        $slidegroup[] = $mform->createElement('text', 'config_title', get_string('slidetitle', 'block_carousel'));
        $options['config_title']['type'] = PARAM_TEXT;

        $slidegroup[] = $mform->createElement('text', 'config_text', get_string('slidetext', 'block_carousel'));
        $options['config_text']['type'] = PARAM_TEXT;

        $slidegroup[] = $mform->createElement('text', 'config_url', get_string('slideurl', 'block_carousel'));
        $options['config_url']['type'] = PARAM_URL;

        $slidegroup[] = $mform->createElement('filepicker', 'config_image', get_string('slideimage', 'block_carousel'),
                null, array('accepted_types' => 'image'));
        $options['config_image']['type'] = PARAM_FILE;

        $this->repeat_elements($slidegroup, 3, $options, 'slides', 'add_slides', 1, null, true);

    }

}
