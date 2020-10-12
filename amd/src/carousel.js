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
define(['jquery', 'core/modal_factory', 'core/ajax', 'block_carousel/slick'], function($, ModalFactory, Ajax) {
    return {
        init: function(blockid, numslides, autoplay, playspeed) {
            $('#carousel' + blockid + ' .slidewrap').show();

            // Setup breakpoint array.
            // Breakpoints set to 1 above the standard width, as expressions like >= 1024 don't work.
            if (numslides > 1) {
                var breakpoints = [{
                    breakpoint: 1025,
                    settings: {
                        slidesToShow: 2,
                    }
                }, {
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 1,
                    }
                }];
            } else {
                var breakpoints = [];
            }

            var carousel = $('#carousel' + blockid);
            carousel.slick({
                dots: true,
                infinite: true,
                speed: 300,
                slidesToShow: numslides,
                adaptiveHeight: true,
                autoplay: autoplay,
                autoplaySpeed: playspeed,
                responsive: breakpoints
            });

            // This is a special case for when carousel is embedded in another block.
            // When the carousel is resized, we need to resize.
            // This causes slick to natively resize itself.
            var observer = new ResizeObserver(function() {
                $(window).trigger('resize');
            });

            observer.observe(carousel.get('0'));
        },

        modal: function(rowid, modalTitle) {
            var slide = document.querySelector('#id_slide' + rowid);
            slide.addEventListener('click', function(){
                ModalFactory.create(
                    {
                        type: ModalFactory.types.CANCEL,
                        title: modalTitle,
                        body: slide.dataset.modalcontent
                    }
                ).then($.proxy(function(modal) {
                    modal.getRoot().addClass('block_carousel');

                    modal.setLarge();
                    modal.show();
                }));
            });
        },

        videocontrol: function(blockid, slideid) {
            var videocontrol = function() {
                var video = document.querySelector('#id_slidevideo' + slideid);
                var slideparent = $('#id_slidecontainer' + slideid).parents('.slick-slide');
                if (slideparent.attr('aria-hidden') === 'false') {
                    // This is the active slide. Unpause the video with this slideID.
                    video.play();
                } else {
                    // Non active slide. Pause it.
                    video.pause();
                }
            };
            var carousel = $('#carousel' + blockid);
            carousel.on('afterChange', videocontrol);
            carousel.on('init', videocontrol);
        },

        interaction: function(slideid) {
            var slide = document.querySelector('#id_slide' + slideid);
            slide.addEventListener('click', function(){
                var request = {
                    methodname: 'block_carousel_record_interaction',
                    args: {
                        rowid: slideid
                    }
                };
                Ajax.call([request])[0].fail();
            });
        }
    };
});
