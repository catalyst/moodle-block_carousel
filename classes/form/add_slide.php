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
 * Form for setting questions to be used on the site
 *
 * @package     tool_securityquestions
 * @copyright   Peter Burnett <peterburnett@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_carousel\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/blocks/carousel/lib.php');

class add_slide extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'url', get_string('slideurl', 'block_carousel'), ['size' => 40]);
        $mform->setType('url', PARAM_URL);
        $mform->addHelpButton('url', 'slideurl', 'block_carousel');

        $mform->addElement('advcheckbox', 'newtab', get_string('openinnewtab', 'block_carousel'));
        $mform->setType('newtab', PARAM_BOOL);
        $mform->setDefault('newtab', 1);

        $mform->addElement('text', 'title', get_string('slidetitle', 'block_carousel'), ['size' => 40]);
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('textarea', 'text', get_string('slidetext', 'block_carousel'), ['cols' => 39]);
        // Raw. This is formatted before being stored.
        $mform->setType('text', PARAM_RAW);

        $mform->addElement('filemanager', 'content',
                get_string('slideimage', 'block_carousel'), null, block_carousel_file_options());
        $mform->setType('content', PARAM_FILE);

        $mform->addElement('editor', 'modal', get_string('modaltext', 'block_carousel'), ['maxfiles' => 0]);
        $mform->setType('modal', PARAM_RAW);

        $mform->addElement('date_time_selector', 'timedstart', get_string('from'), [
            'startyear' => 2020,
            'stopyear' => 2030,
            'optional' => true,
        ]);
        $mform->setType('timedstart', PARAM_INT);

        $mform->addElement('date_time_selector', 'timedend', get_string('to'), [
            'startyear' => 2020,
            'stopyear' => 2030,
            'optional' => true,
        ]);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        // Ensure that the active period is valid if selected.
        $errors = parent::validation($data, $files);
        if (!empty($data['timedstart']) && !empty($data['timedend'])) {
            if ($data['timedstart'] >= $data['timedend']) {
                $errors['timedstart'] = get_string('timeperioderror', 'block_carousel');
            }
        }

        return $errors;
    }
}
