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
 * course_format data generator.
 *
 * @package    mod_feedback
 * @category   test
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

/**
 * course_format data generator class.
 *
 * @package    course_format
 * @category   test
 * @copyright  2015 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_page_generator extends component_generator_base {

    public function create_page($course, $record = null, array $options = null) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/course/format/page/lib.php');
        $record = (object)(array)$record;

        if (!isset($record->courseid)) {
            $record->courseid = $course->id;
        }

        if (!isset($record->nameone)) {
            $record->nameone = 'Page';
        }

        if (!isset($record->nametwo)) {
            $record->nametwo = 'Page';
        }

        if (!isset($record->prefleftwidth)) {
            $record->prefleftwidth = '3';
        }

        if (!isset($record->prefcenterwidth)) {
            $record->prefcenterwidth = '6';
        }

        if (!isset($record->prefrightwidth)) {
            $record->prefrightwidth = '3';
        }

        if (!isset($record->idnumber)) {
            $record->idnumber = '';
        }

        if (!isset($record->display)) {
            $record->display = FORMAT_PAGE_DISP_PUBLISHED;
        }

        if (!isset($record->displaymenu)) {
            $record->displaymenu = 1;
        }

        if (!isset($record->parent)) {
            $record->parent = 0;
        }

        if (!isset($record->template)) {
            $record->template = 0;
        }

        if (!isset($record->globaltemplate)) {
            $record->globaltemplate = 0;
        }

        $lastorder = $DB->get_field('format_page', 'MAX(sortorder)', array('courseid' => $course->id, 'parent' => $record->parent)); 
        $record->sortorder = $lastorder + 1;

        $pageid = $DB->insert_record('format_page', $record);

        return $DB->get_record('format_page', array('id' => $pageid));
    }

}

