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
 * Page internal check service
 *
 * @package format_page
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */

function page_audit_check_sections($course) {
    global $DB;

    $sections = $DB->get_records('course_sections', array('course' => $course->id));

    // Get all modules registered in sequences for all the course
    $allseqmodlist = '';
    $sequences = array();
    foreach($sections as $sec) {
        if ($sec->sequence) {
            $sequences[$sec->id] = explode(',', $sec->sequence);
            $allseqmodlist .= ','.$sec->sequence;
        }
    }

    $good = array();
    $bad = array();
    $outofcourse = array();
    if (!empty($allseqmodlist)) {
        $allseqmodlist = preg_replace('/^,/', '', $allseqmodlist);
        $good = $DB->get_records_select('course_modules', " id IN ($allseqmodlist) AND course = {$course->id} ");
        $bad = $DB->get_records_select('course_modules', " id NOT IN ($allseqmodlist) AND course = {$course->id} ");
        $outofcourse = $DB->get_records_select('course_modules', " id IN ($allseqmodlist) AND course != {$course->id} ");
    }

    return array($good, $bad, $outofcourse);
}