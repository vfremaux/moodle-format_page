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
 * @package     format_page
 * @category    format
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 *
 * This script is a straight redirector to /course/mod.php
 * We just need it to eventually store in session the mod_create activator 
 * for direct insertion in current course page.
 */

require('../../../config.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

$courseid = required_param('id', PARAM_INT);
$pageid = required_param('section', PARAM_INT); // Contains section id associated to page.
$type = optional_param('type', '', PARAM_TEXT);
$add = optional_param('add', '', PARAM_TEXT);
$duplicate = optional_param('duplicate', '', PARAM_TEXT);

// Security.

require_login();
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);

require_sesskey();
rebuild_course_cache($courseid, true);

if ($insertinpage = required_param('insertinpage', PARAM_BOOL)) {
    $SESSION->format_page_cm_insertion_page = $pageid;
}

$page = course_page::get($pageid);

$params = array('id' => $courseid, 'section' => $page->section, 'sesskey' => sesskey(), 'add' => $add, 'duplicate' => $duplicate, 'type' => $type);
$urlbase = new moodle_url('/course/mod.php', $params);
redirect($urlbase);

