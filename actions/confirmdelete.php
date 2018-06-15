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
 * Confirms page deletion
 * 
 * @package format_page
 * @category mod
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

// Security.

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);
require_capability('format/page:editpages', $context);

// Set course display.

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    $page = course_page::get($pageid);
} else {
    if (!$page = course_page::get_current_page($course->id)) {
        print_error('errornopage', 'format_page');
    }
    $pageid = $page->id;
}

$url = new moodle_url('/course/format/page/actions/manage.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'page-actionform');

$message = get_string('confirmdelete', 'format_page', format_string($page->nameone));
$params = array('id' => $course->id, 'page' => $page->id, 'action' => 'deletepage', 'sesskey' => sesskey());
$linkyes = new moodle_url('/course/format/page/action.php', $params);
$linkno  = $page->url_build('action', 'manage');
echo $OUTPUT->confirm($message, $linkyes, $linkno);

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
