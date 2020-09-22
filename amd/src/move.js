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
 * Drag and drop order handler. This is adapted from the XMLDB editor in 3.6+
 *
 * @module    block_carousel/move
 * @copyright 2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/sortable_list', 'core/ajax', 'core/notification'], function($, SortableList, Ajax, Notification) {
    return {
        init: function(blockId) {
            // Initialise sortable for the given list.
            var sort = new SortableList($('#slidetable' + ' tbody'));

            sort.getElementName = function(element) {
                return $.Deferred().resolve(element.attr('data-name'));
            };
            var origIndex;
            $('tr.slidetable').on(SortableList.EVENTS.DRAGSTART, function(_, info) {
                // Remember position of the element in the beginning of dragging.
                origIndex = info.sourceList.children().index(info.element);
                // Resize the "proxy" element to be the same width as the main element.
                setTimeout(function() {
                    $('.sortable-list-is-dragged').width(info.element.width());
                }, 501);
            }).on(SortableList.EVENTS.DROP, function(_, info) {
                // When a list element was moved send AJAX request to the server.
                var newIndex = info.targetList.children().index(info.element);
                var t = info.element.find('[data-action=move]');
                if (info.positionChanged) {
                    var request = {
                        methodname: 'block_carousel_update_slide_order',
                        args: {
                            blockid: blockId,
                            rowid: t.attr('data-rowid'),
                            position: newIndex - origIndex
                        }
                    };
                    Ajax.call([request])[0].fail(Notification.exception);
                }
            });
        }
    };
});
