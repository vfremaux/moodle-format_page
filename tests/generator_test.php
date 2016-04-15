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

defined('MOODLE_INTERNAL') || die();

/**
 * Genarator tests.
 *
 * @package    format_page
 * @copyright  2015 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

/**
 * Genarator tests class.
 *
 * @package    format_page
 * @copyright  2015 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_page_generator_testcase extends advanced_testcase {

    public function test_create_structure() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedcourse = new StdClass;
        $pagedcourse->shortname = 'PAGEDTEST'.$i;
        $pagedcourse->fullname = 'Paged Test Course '.$i;
        $pagedcourse->format = 'page';

        $course = $this->getDataGenerator()->create_course($pagedcourse);
        $this->assertFalse($DB->record_exists('format_page', array('course' => $course->id)));

        $pages = array();

        // build root pages
        for ($i = 0 ; $i < 10 ; $i++) {
            $pages[$i] = $this->getDataGenerator()->create_page($course, $pagedcourse);
            $this->assertTrue(is_object($pages[$i]));
            $this->assertEquals($i + 1, $DB->count_records('format_page', array('courseid' => $course->id)));
        }

        $subpages = array();
        // build child pages level 1
        for ($i = 0 ; $i < 10 ; $i++) {
            for ($j = 0 ; $j < 10 ; $j++) {
                $subpage = new StdClass;
                $subpage->parent = $pages[$i];
                $subpages[$i][$j] = $this->getDataGenerator()->create_page($course, $subpage);
            }
        }

        // 100 pages were created.
        $this->assertEquals(100, $DB->count_records('format_page', array('courseid' => $course->id)));
    }

}

