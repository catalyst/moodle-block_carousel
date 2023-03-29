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
 * Add slide form for carousel
 *
 * @package     block_carousel
 * @copyright   Peter Burnett <peterburnett@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
$blockid = required_param('bid', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);

$context = context_block::instance($blockid);
$PAGE->set_context(context_block::instance($blockid));
$PAGE->set_title(get_string('addslide', 'block_carousel'));
$PAGE->set_heading(get_string('addslide', 'block_carousel'));
$url = new moodle_url('/blocks/carousel/add_slide.php', ['bid' => $blockid, 'action' => $action, 'id' => $id]);
$PAGE->set_url($url);

require_login();
require_capability('moodle/block:edit', $context);

$editurl = "carousel_{$blockid}_editurl";
$prevurl = $SESSION->$editurl;
$prevurl->param('sesskey', sesskey());
$storage = get_file_storage();

// Form data.
$data = null;

// Handle actions.
switch ($action) {
    case 'delete':
        $confirmdel = optional_param('confirm', 0, PARAM_BOOL);
        if (!$confirmdel) {
            $continue = new moodle_url('/blocks/carousel/add_slide.php', [
                'bid' => $blockid,
                'action' => 'delete',
                'id' => $id,
                'confirm' => 1,
                'sesskey' => sesskey()]);
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string('confirmdelete', 'block_carousel'), $continue, $prevurl);
            echo $OUTPUT->footer();
            die();
        } else {
            require_sesskey();
            $DB->delete_records('block_carousel', ['id' => $id]);
            // Delete file for this slide.
            $storage->delete_area_files($context->id, 'block_carousel', 'image', $id);
            \block_carousel\local\slide_manager::remove_id_from_order($blockid, $id);

            redirect($prevurl);
        }
        // Should never get here.
        break;

    case 'edit':
        $data = $DB->get_record('block_carousel', ['id' => $id]);
        $draftitemid = file_get_submitted_draft_itemid('content');
        file_prepare_draft_area($draftitemid, $context->id, 'block_carousel', 'content', $id);
        $data->content = $draftitemid;
        $data->modal = ['text' => $data->modalcontent, 'format' => FORMAT_HTML];
        break;

    case 'clone':
        $origrow = $DB->get_record('block_carousel', ['id' => $id]);
        unset($origrow->id);
        $origrow->interactions = 0;
        $newid = $DB->insert_record('block_carousel', $origrow, true);
        \block_carousel\local\slide_manager::add_id_to_order($blockid, $newid);

        // Now we need to copy the file to the new filearea.
        $storage = get_file_storage();
        $files = $storage->get_area_files(
            $context->id,
            'block_carousel',
            'content',
            $id
        );
        foreach ($files as $file) {
            $storage->create_file_from_storedfile(['itemid' => $newid], $file);
        }

        redirect($prevurl);

    case 'disable':
        \block_carousel\local\slide_manager::disable_slide($id);
        redirect($prevurl);

    case 'enable':
        \block_carousel\local\slide_manager::enable_slide($id);
        redirect($prevurl);

    default:
        break;
}

$form = new \block_carousel\form\add_slide($url);
$form->set_data($data);
if ($form->is_cancelled()) {

    redirect($prevurl);

} else if ($fromform = $form->get_data()) {
    $record = new \stdClass();
    $record->url = $fromform->url;
    if (!empty($fromform->url)) {
        $courseurl = new moodle_url('course/view.php');
        $formurl = new moodle_url($fromform->url);
        // If URL is a course link, autofill some data.
        if ($courseurl->compare($formurl, URL_MATCH_BASE)) {
            $record->courseid = $formurl->get_param('id');
        }
    }

    $record->blockid = $blockid;
    $record->title = $fromform->title;
    $record->notitle = $fromform->notitle;
    $record->text = $fromform->text;
    $record->notext = $fromform->notext;
    $record->newtab = $fromform->newtab;
    $record->cohorts = !empty($fromform->cohorts) ? implode(',', $fromform->cohorts) : '';
    $record->disabled = 0;

    $modaltext = trim(html_to_text($fromform->modal['text']));
    if (!empty($modaltext)) {
        // Store the richtext, its not just whitespace.
        $record->modalcontent = $fromform->modal['text'];
    } else {
        $record->modalcontent = '';
    }
    $record->timedstart = $fromform->timedstart;
    $record->timedend = $fromform->timedend;

    $record->contenttype = 'image';
    if ($action !== 'edit') {
        $record->interactions = 0;
        $id = $DB->insert_record('block_carousel', $record, true);
        $recordid = $id;
        $slidecount = count(\block_carousel\local\slide_manager::get_current_order($blockid));
        \block_carousel\local\slide_manager::add_id_to_order($blockid, $recordid);
        \block_carousel\local\slide_manager::move_order_position($blockid, $recordid, -$slidecount);
    } else {
        $record->id = $id;
        $DB->update_record('block_carousel', $record);
        $recordid = $id;

        // Invalidate cache for this record.
        cache_helper::invalidate_by_definition('block_carousel', 'slides', [], [(string) $recordid]);
    }
    file_save_draft_area_files($fromform->content, $context->id, 'block_carousel', 'content', $recordid);

    // Now that the file is saved, the type can be inspected now it is ingested into the file API.
    $files = $storage->get_area_files(
        $context->id,
        'block_carousel',
        'content',
        $recordid
    );

    // Look and see if there is a valid image.
    $image = false;
    foreach ($files as $file) {
        if ($file->is_valid_image()) {
            $image = true;
        }
    }
    if (!$image && count($files) > 1) {
        // No file was a valid image. Update row to video mode.
        $DB->set_field('block_carousel', 'contenttype', 'video', ['id' => $recordid]);
    }

    redirect($prevurl);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
