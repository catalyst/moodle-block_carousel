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
 * Slide table for block_carousel
 *
 * @package   block_carousel
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT 2020
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_carousel\output;

use context_block;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class slide_table extends \flexible_table implements \renderable {

    /**
     * The current slide number.
     *
     * @var int
     */
    private $slideno;

    /**
     * Sets up the parameters.
     *
     * @param string $uniqueid Unique id of form.
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'slidetable');
        $this->set_attribute('class', 'slidetable generaltable generalbox');
        $this->define_columns(array(
                'title',
                'text',
                'url',
                'content',
                'interactions',
                'timed',
                'actions',
        ));
        $this->define_headers(array(
                get_string('slidetitle', 'block_carousel'),
                get_string('slidetext', 'block_carousel'),
                get_string('link', 'block_carousel'),
                get_string('content'),
                get_string('interactions', 'block_carousel'),
                get_string('timedrelease', 'block_carousel'),
                get_string('actions'),
            )
        );

        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(false);
        $this->is_downloadable(false);
        $this->column_style('text', 'word-wrap', 'break-word');
        $this->slideno = 1;
    }

    /**
     * Adds block data to the table.
     *
     * @param int $blockid the blockid to get data for.
     * @return void
     */
    public function populate_block_table($blockid) {
        global $DB, $OUTPUT;

        $currorder = \block_carousel\local\slide_manager::get_current_order($blockid);
        $context = context_block::instance($blockid);

        $cache = \cache::make('block_carousel', 'slides');
        $slidedata = $cache->get_many($currorder);

        $slidenum = 1;
        foreach ($currorder as $id) {
            $slide = (object) $slidedata[$id];
            $data = [];
            $data['title'] = !empty($slide->title) ? $slide->title : get_string('none');
            $data['text'] = !empty($slide->text) ? $slide->text : get_string('none');
            if ($slide->modalcontent) {
                $data['url'] = get_string('modal', 'block_carousel');
            } else if (!empty($slide->url)) {
                $data['url'] = \html_writer::link($slide->url, $slide->url);
            } else {
                $data['url'] = get_string('none');
            }
            // We need to get interactions from the DB, as they will likely be wrong in cache.
            $data['interactions'] = $DB->get_field('block_carousel', 'interactions', ['id' => $slide->id]);

            // Is it a timed release?
            if (!empty($slide->timedstart) || !empty($slide->timedend)) {
                $data['timed'] = get_string('yes');
            } else {
                $data['timed'] = get_string('no');
            }

            // Get file preview.
            $url = $slide->link;
            if (!empty($url)) {
                $url->param('preview', 'thumb');
                $data['content'] = \html_writer::img($url, $data['text']);
            }

            if (empty($data['content'])) {
                // No thumbnail could be generated.
                $data['content'] = 'No thumbnail found.';
            }

            // Setup actions.
            $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
            $actions = \html_writer::link(new \moodle_url('/blocks/carousel/add_slide.php',
                ['bid' => $blockid, 'id' => $id, 'action' => 'edit']), $icon);

            $icon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
            $actions .= \html_writer::link(new \moodle_url('/blocks/carousel/add_slide.php',
                ['bid' => $blockid, 'id' => $id, 'action' => 'delete']), $icon);

            $icon = $OUTPUT->pix_icon('t/copy', get_string('copy'));
            $actions .= \html_writer::link(new \moodle_url('/blocks/carousel/add_slide.php',
                ['bid' => $blockid, 'id' => $id, 'action' => 'clone']), $icon);

            $classes = 'slidetable';

            // Enable / Disable.
            if (!$slide->disabled) {
                $icon = $OUTPUT->pix_icon('t/hide', get_string('disable'));
                $actions .= \html_writer::link(new \moodle_url('/blocks/carousel/add_slide.php',
                    ['bid' => $blockid, 'id' => $id, 'action' => 'disable']), $icon);

            } else {
                $icon = $OUTPUT->pix_icon('t/show', get_string('enable'));
                $actions .= \html_writer::link(new \moodle_url('/blocks/carousel/add_slide.php',
                    ['bid' => $blockid, 'id' => $id, 'action' => 'enable']), $icon);

                $classes .= ' table-secondary';
            }

            // Setup the drag handles for DnD.
            $actions .= \html_writer::span($OUTPUT->render_from_template('core/drag_handle',
            ['movetitle' => get_string('move')]), '', [
                'data-action' => 'move',
                'data-rowid' => $id,
                'data-name' => "Slide {$slidenum}"
            ]);

            $data['actions'] = $actions;

            // Add slidetable class for DnD JS to attach to.
            $this->add_data_keyed($data, $classes);
            $this->slideno++;
        }
    }

    /**
     * Adds data to the table then returns the table HTML.
     *
     * @param  $id the block id to view.
     * @param [type] $currorder the current slide ordering.
     * @return void
     */
    public function out($id) {
        global $DB, $PAGE;

        // Setup JS for DnD.
        $PAGE->requires->js_call_amd('block_carousel/move', 'init', [$id]);

        ob_start();
        $this->define_baseurl($PAGE->url);
        $this->setup();

        // Get the total row count for setting pagesize.
        $count = $DB->count_records('block_carousel', ['id' => $id]);
        $this->pagesize($count, $count);

        $this->populate_block_table($id);
        $this->finish_output();

        // Add slide button.
        $url = new \moodle_url('/blocks/carousel/add_slide.php', ['bid' => $id]);
        // Hack to add button without a submit action.
        echo \html_writer::link($url, get_string('addslide', 'block_carousel'), ['class' => 'btn btn-primary']);
        echo '<br><br>';

        return ob_get_clean();
    }

    /**
     * Generate html code for the passed row.
     * This is overridden to add attributes to the <tr> elements
     *
     * @param array $row Row data.
     * @param string $classname classes to add.
     *
     * @return string $html html code for the row passed.
     */
    public function get_row_html($row, $classname = '') {
        static $suppresslastrow = null;
        $rowclasses = array();

        if ($classname) {
            $rowclasses[] = $classname;
        }

        $rowid = $this->uniqueid . '_r' . $this->currentrow;
        $html = '';

        $html .= \html_writer::start_tag('tr', array('class' => implode(' ', $rowclasses), 'id' => $rowid,
            'data-name' => "Slide {$this->slideno}"));

        // If we have a separator, print it.
        if ($row === null) {
            $colcount = count($this->columns);
            $html .= \html_writer::tag('td', \html_writer::tag('div', '',
                    array('class' => 'tabledivider')), array('colspan' => $colcount));

        } else {
            $colbyindex = array_flip($this->columns);
            foreach ($row as $index => $data) {
                $column = $colbyindex[$index];

                $attributes = [
                    'class' => "cell c{$index}" . $this->column_class[$column],
                    'id' => "{$rowid}_c{$index}",
                    'style' => $this->make_styles_string($this->column_style[$column]),
                ];

                $celltype = 'td';
                if ($this->headercolumn && $column == $this->headercolumn) {
                    $celltype = 'th';
                    $attributes['scope'] = 'row';
                }

                if (empty($this->prefs['collapse'][$column])) {
                    if ($this->column_suppress[$column] && $suppresslastrow !== null && $suppresslastrow[$index] === $data) {
                        $content = '&nbsp;';
                    } else {
                        $content = $data;
                    }
                } else {
                    $content = '&nbsp;';
                }

                $html .= \html_writer::tag($celltype, $content, $attributes);
            }
        }

        $html .= \html_writer::end_tag('tr');

        $suppressenabled = array_sum($this->column_suppress);
        if ($suppressenabled) {
            $suppresslastrow = $row;
        }
        $this->currentrow++;
        return $html;
    }
}
