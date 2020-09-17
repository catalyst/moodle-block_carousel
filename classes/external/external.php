<?php

namespace block_carousel\external;

use external_api;
use external_function_parameters;

require_once("$CFG->libdir/externallib.php");
defined('MOODLE_INTERNAL') || die();

class external extends external_api {
    public static function process_update_action_parameters() {
        return new external_function_parameters([
            'blockid' => new \external_value(PARAM_INT, 'block id of the carousel instance'),
            'rowid' => new \external_value(PARAM_INT, 'row id to move'),
            'position' => new \external_value(PARAM_INT, 'the new position in the array of the row')
        ]);
    }

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

    public static function process_update_action_returns() {
        return new \external_value(PARAM_BOOL, 'Whether the move was completed.');
    }

    public static function record_interaction_parameters() {
        return new external_function_parameters([
            'rowid' => new \external_value(PARAM_INT, 'row id to move'),
        ]);
    }

    public static function record_interaction($rowid) {
        $params = self::validate_parameters(self::record_interaction_parameters(), [
            'rowid' => $rowid,
        ]);

        // Now params are validated, update references.
        $rowid = $params['rowid'];
        return \block_carousel\local\slide_manager::record_interaction($rowid);
    }

    public static function record_interaction_returns() {
        return new \external_value(PARAM_BOOL, 'Whether the interaction was successfully recorded.');
    }
}