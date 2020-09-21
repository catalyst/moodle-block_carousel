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
 * Load the carousel.
 *
 * @module    block_carousel/carousel
 * @copyright 2016 Brendan Heywood (brendan@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal_factory', 'block_carousel/slick'], function($, ModalFactory) {
    return {
        init: function(blockid, playspeed) {
            $('#carousel' + blockid + ' .slidewrap').show();
            $('#carousel' + blockid).slick({
                dots: true,
                infinite: true,
                speed: 300,
                slidesToShow: 1,
                adaptiveHeight: true,
                autoplay: true,
                autoplaySpeed: playspeed
            });
        },

        modal: function(rowid, modalContent) {
            var slide = document.querySelector('#id_slide' + rowid);
            slide.addEventListener('click', function(){
                ModalFactory.create(
                    {
                        type: ModalFactory.types.CANCEL,
                        title: '',
                        body: modalContent
                    }
                ).then($.proxy(function(modal) {
                    modal.setLarge();
                    modal.show();
                }));
            });
        },

        videocontrol: function(blockid, slideid) {
            var slide = document.querySelector('#carousel' + blockid);
            slide.addEventListener('afterChange', function() {
                var video = document.querySelector('#id_slidevideo' + slideid);
                if (slide.attr('tabindex') ==  0) {
                    // This is the active slide. Unpause the video with this slideID.
                    video.play();
                } else {
                    // Non active slide. Pause it.
                    video.pause();
                }
            });
        }
    };
});
