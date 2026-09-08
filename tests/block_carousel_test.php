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

namespace block_carousel;

use advanced_testcase;
use block_carousel;
use cache;
use context_course;
use moodle_page;
use stdClass;

/**
 * Unit tests for block_carousel.
 *
 * @package   block_carousel
 * @category  test
 * @copyright 2026 Catalyst IT Australia
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @coversDefaultClass \block_carousel
 */
final class block_carousel_test extends advanced_testcase {
    /**
     * Setup
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
        require_once($CFG->dirroot . '/blocks/carousel/block_carousel.php');
        parent::setUpBeforeClass();
    }

    /**
     * Helper to instantiate a carousel block attached to a course page.
     *
     * @param stdClass|null $config Optional block config
     * @return block_carousel
     */
    protected function setup_block(?stdClass $config = null): block_carousel {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $page = new moodle_page();
        $page->set_context($context);
        $page->set_course($course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');

        $blockinstance = $this->getDataGenerator()->create_block('carousel', [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'course-view',
        ]);

        $block = block_instance('carousel', $blockinstance);
        $block->page = $page;

        if ($config !== null) {
            $block->config = $config;
        }

        return $block;
    }

    /**
     * Helper to generate a slide data array for cache injection.
     *
     * @param int $id Slide ID
     * @param array $overrides Property overrides
     * @return array
     */
    protected function generate_slide_data(int $id, array $overrides = []): array {
        return array_merge([
            'id' => $id,
            'blockid' => 1,
            'title' => 'Test Slide ' . $id,
            'text' => 'Slide text description',
            'url' => 'https://example.com',
            'contenttype' => 'image',
            'interactions' => 0,
            'modalcontent' => '',
            'cohorts' => '',
            'newtab' => 0,
            'disabled' => 0,
            'timedstart' => 0,
            'timedend' => 0,
            'courseid' => null,
            'notitle' => 0,
            'notext' => 0,
            'link' => 'https://example.com/image.jpg',
            'heightres' => 600,
            'widthres' => 800,
        ], $overrides);
    }

    /**
     * Test get_content with empty or missing config/order returns empty content.
     *
     * @covers ::get_content
     */
    public function test_get_content_empty_config(): void {
        $this->resetAfterTest();
        $block = $this->setup_block();

        $content = $block->get_content();
        $this->assertEmpty($content->text);

        // Config with empty order string.
        $config = new stdClass();
        $config->order = '';
        $block = $this->setup_block($config);
        $content = $block->get_content();
        $this->assertEmpty($content->text);
    }

    /**
     * Test get_content when slides in order do not exist in cache/DB.
     *
     * @covers ::get_content
     */
    public function test_get_content_missing_slide_in_order(): void {
        $this->resetAfterTest();

        $config = new stdClass();
        $config->order = '999,998';
        $config->height = '300px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test single slide with video and null height/width dimensions (recreates slide 199 division by zero).
     *
     * When a video slide is used and ffprobe is not configured or fails,
     * heightres is null. With strict === 0 check, null === 0 is false,
     * resulting in a division by null/0 error.
     *
     * @covers ::get_content
     */
    public function test_get_content_single_slide_null_heightres_video(): void {
        $this->resetAfterTest();

        $slideid = 199;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slideid, $this->generate_slide_data($slideid, [
            'contenttype' => 'video',
            'heightres' => null,
            'widthres' => null,
            'link' => 'https://example.com/video.mp4',
        ]));

        $config = new stdClass();
        $config->order = (string) $slideid;
        $config->height = '400px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test single slide with null image dimensions.
     *
     * @covers ::get_content
     */
    public function test_get_content_single_slide_null_dimensions_image(): void {
        $this->resetAfterTest();

        $slideid = 100;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slideid, $this->generate_slide_data($slideid, [
            'contenttype' => 'image',
            'heightres' => null,
            'widthres' => null,
        ]));

        $config = new stdClass();
        $config->order = (string) $slideid;
        $config->height = '300px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test single slide with zero width (causing ratio = 0, and 1 / ratio division by zero).
     *
     * @covers ::get_content
     */
    public function test_get_content_single_slide_zero_width(): void {
        $this->resetAfterTest();

        $slideid = 101;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slideid, $this->generate_slide_data($slideid, [
            'heightres' => 500,
            'widthres' => 0,
        ]));

        $config = new stdClass();
        $config->order = (string) $slideid;
        $config->height = '300px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test single slide with zero heightres.
     *
     * @covers ::get_content
     */
    public function test_get_content_single_slide_zero_height(): void {
        $this->resetAfterTest();

        $slideid = 102;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slideid, $this->generate_slide_data($slideid, [
            'heightres' => 0,
            'widthres' => 500,
        ]));

        $config = new stdClass();
        $config->order = (string) $slideid;
        $config->height = '300px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test multislide mode (slides > 1) when first slide has zero width (ratio = 0, causing 1 / ratio division by zero).
     *
     * @covers ::get_content
     */
    public function test_get_content_multislide_zero_width(): void {
        $this->resetAfterTest();

        $slide1 = 201;
        $slide2 = 202;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slide1, $this->generate_slide_data($slide1, [
            'heightres' => 400,
            'widthres' => 0,
        ]));
        $cache->set($slide2, $this->generate_slide_data($slide2, [
            'heightres' => 400,
            'widthres' => 800,
        ]));

        $config = new stdClass();
        $config->order = "{$slide1},{$slide2}";
        $config->height = '300px';
        $config->slides = 2;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test multislide mode (slides > 1) when first slide has null height.
     *
     * @covers ::get_content
     */
    public function test_get_content_multislide_null_height(): void {
        $this->resetAfterTest();

        $slide1 = 203;
        $slide2 = 204;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slide1, $this->generate_slide_data($slide1, [
            'heightres' => null,
            'widthres' => 800,
        ]));
        $cache->set($slide2, $this->generate_slide_data($slide2, [
            'heightres' => 400,
            'widthres' => 800,
        ]));

        $config = new stdClass();
        $config->order = "{$slide1},{$slide2}";
        $config->height = '300px';
        $config->slides = 2;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
    }

    /**
     * Test valid slides render expected HTML structure without errors.
     *
     * @covers ::get_content
     */
    public function test_get_content_valid_slides(): void {
        $this->resetAfterTest();

        $slide1 = 301;
        $slide2 = 302;
        $cache = cache::make('block_carousel', 'slides');
        $cache->set($slide1, $this->generate_slide_data($slide1, [
            'title' => 'First Slide',
            'heightres' => 600,
            'widthres' => 800,
        ]));
        $cache->set($slide2, $this->generate_slide_data($slide2, [
            'title' => 'Second Slide',
            'heightres' => 600,
            'widthres' => 800,
        ]));

        $config = new stdClass();
        $config->order = "{$slide1},{$slide2}";
        $config->height = '400px';
        $config->slides = 1;

        $block = $this->setup_block($config);
        $content = $block->get_content();

        $this->assertNotEmpty($content->text);
        $this->assertStringContainsString('First Slide', $content->text);
        $this->assertStringContainsString('Second Slide', $content->text);
        $this->assertStringContainsString('id_slidecontainer301', $content->text);
        $this->assertStringContainsString('id_slidecontainer302', $content->text);
    }
}
