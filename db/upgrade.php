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
 * Carousel block upgrade script
 *
 * @package     block_carousel
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   Catalyst IT Australia
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

function xmldb_block_carousel_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2020092200) {

        // Define table block_carousel to be created.
        $table = new xmldb_table('block_carousel');

        // Adding fields to table block_carousel.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('blockid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('contenttype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('interactions', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modalcontent', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('newtab', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timedstart', XMLDB_TYPE_INTEGER, '15', null, null, null, null);
        $table->add_field('timedend', XMLDB_TYPE_INTEGER, '15', null, null, null, null);

        // Adding keys to table block_carousel.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_carousel.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Run upgrade script.
        \block_carousel_upgrade_helper::upgrade_carousels_to_db();

        // Carousel savepoint reached.
        upgrade_block_savepoint(true, 2020092200, 'carousel');
    }

    if ($oldversion < 2020092400) {

        // Define field courseid to be added to block_carousel.
        $table = new xmldb_table('block_carousel');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timedend');

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Carousel savepoint reached.
        upgrade_block_savepoint(true, 2020092400, 'carousel');
    }

    if ($oldversion < 2020100100) {

        // Define field notitle to be added to block_carousel.
        $table = new xmldb_table('block_carousel');
        $field = new xmldb_field('notitle', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'courseid');

        // Conditionally launch add field notitle.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field notext to be added to block_carousel.
        $table = new xmldb_table('block_carousel');
        $field = new xmldb_field('notext', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'notitle');

        // Conditionally launch add field notext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Carousel savepoint reached.
        upgrade_block_savepoint(true, 2020100100, 'carousel');
    }

    return true;
}
