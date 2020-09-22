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
 * External lib for block_carousel
 *
 * @package   block_carousel
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT 2020
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_carousel\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;

require_once("$CFG->libdir/externallib.php");

class external extends external_api {

    /**
     * Defines the parameters for the webservice function.
     *
     * @return external_function_parameters the parameters for the function
     */
    public static function process_update_action_parameters() {
        return new external_function_parameters([
            'blockid' => new \external_value(PARAM_INT, 'block id of the carousel instance'),
            'rowid' => new \external_value(PARAM_INT, 'row id to move'),
            'position' => new \external_value(PARAM_INT, 'the new position in the array of the row')
        ]);
    }

    /**
     * Processes the order update action
     *
     * @param int $blockid the block to modify.
     * @param int $rowid the row to change position of.
     * @param int $position the number of positions to move.
     * @return bool
     */
    public static function process_update_action($blockid, $rowid, $position) {
        $params = self::validate_parameters(self::process_update_action_parameters(), [
            'blockid' => $blockid,
            'rowid' => $rowid,
            'position' => $position,
        ]);

        // Now params are validated, update references.
        $blockid = $params['blockid'];
        $rowid = $params['rowid'];
        $position = $params['position'];

        return \block_carousel\local\slide_manager::move_order_position($blockid, $rowid, $position);
    }

    /**
     * Defines the return value from the webservice function.
     *
     * @return \external_value the value returned from the function.
     */
    public static function process_update_action_returns() {
        return new \external_value(PARAM_BOOL, 'Whether the move was completed.');
    }

    /**
     * Defines the parameters for the webservice function.
     *
     * @return external_function_parameters the parameters for the function
     */
    public static function record_interaction_parameters() {
        return new external_function_parameters([
            'rowid' => new \external_value(PARAM_INT, 'row id to move'),
        ]);
    }

    /**
     * Records an interaction with a row.
     *
     * @param int $rowid the row to record the interaction for.
     * @return bool
     */
    public static function record_interaction($rowid) {
        $params = self::validate_parameters(self::record_interaction_parameters(), [
            'rowid' => $rowid,
        ]);

        // Now params are validated, update references.
        $rowid = $params['rowid'];
        return \block_carousel\local\slide_manager::record_interaction($rowid);
    }

    /**
     * Defines the return value from the webservice function.
     *
     * @return \external_value the value returned from the function.
     */
    public static function record_interaction_returns() {
        return new \external_value(PARAM_BOOL, 'Whether the interaction was successfully recorded.');
    }
}
