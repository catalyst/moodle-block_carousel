<?php

namespace block_carousel\local;

class slide_manager {
    public static function get_current_config($blockid) {
        global $DB;

        $blockrec = $DB->get_record('block_instances', ['id' => $blockid]);
        $config = unserialize(base64_decode($blockrec->configdata));
        return empty($config) ? new \stdClass() : $config;
    }

    public static function write_config($blockid, $config) {
        global $DB;

        $encoded = base64_encode(serialize($config));
        $DB->set_field('block_instances', 'configdata', $encoded, ['id' => $blockid]);
    }

    public static function get_current_order($blockid) {
        $config = self::get_current_config($blockid);
        return empty($config->order) ? [] : explode(',', $config->order);
    }

    public static function add_id_to_order($blockid, $id) {
        $config = self::get_current_config($blockid);
        $order = self::get_current_order($blockid);

        array_push($order, $id);
        $config->order = implode(',', $order);
        self::write_config($blockid, $config);
    }

    public static function remove_id_from_order($blockid, $id) {
        $config = self::get_current_config($blockid);
        $order = self::get_current_order($blockid);

        $removed = array_values(array_diff($order, [(string) $id]));
        $config->order = implode(',', $removed);
        self::write_config($blockid, $config);
    }

    public static function move_order_position($blockid, $rowid, $position) {
        //Force Rowid to string if int.
        $rowid = (string) $rowid;

        // Get current block config.
        $config = self::get_current_config($blockid);
        $origorder = self::get_current_order($blockid);

        // Nothing to do if there is only one element in order.
        if (count($origorder) === 1) {
            return false;
        }

        $currentpos = array_search($rowid, $origorder);

        // Split the arrays at the position to insert at.
        // If position is positive, we need to increment to fight slice off by one.
        if ($position > 0) {
            $position++;
        }
        $first = array_slice($origorder, 0, $currentpos + $position);
        $second = array_slice($origorder, $currentpos + $position);

        // Remove the current rowid entry, wherever it is.
        $first = array_values(array_diff($first, [$rowid]));
        $second = array_values(array_diff($second, [$rowid]));

        // Finally stitch back together with element in the middle.
        array_push($first, $rowid);
        $config->order = implode(',', array_merge($first, $second));
        self::write_config($blockid, $config);
        return true;
    }

    public static function record_interaction($rowid) {
        global $DB;

        $curr = $DB->get_field('block_carousel', 'interactions', ['id' => $rowid]);
        if ($curr === false) {
            return false;
        }
        return $DB->set_field('block_carousel', 'interactions', $curr + 1, ['id' => $rowid]);
    }

    public static function enable_slide($rowid) {
        global $DB;

        $DB->set_field('block_carousel', 'disabled', 0, ['id' => $rowid]);
    }

    public static function disable_slide($rowid) {
        global $DB;

        $DB->set_field('block_carousel', 'disabled', 1, ['id' => $rowid]);
    }
}