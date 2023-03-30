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
 * Page management service
 *
 * @package format_page
 * @category format
 * @author Jeff Graham, Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/tree.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

use \format\page\course_page;
use \format\page\tree;

$id = required_param('id', PARAM_INT); // This is the course id.
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);

// If no pages available, jump back to "edit first page".

// Set course display.
tree::fix();

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    if (!$page = course_page::get($pageid)) {
        // Happens when deleting a page. We need find another where to start from safely.
        $page = course_page::get_default_page($course->id);
    }
} else {
    $page = course_page::get_current_page($course->id);
}

if (!$page) {
    // Course is empty maybe ?
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
} else {
    $pageid = $page->id;
}

if ($page->courseid != $course->id) {
    print_error('pageerror', 'format_page');
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

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs('manage', true);
echo $OUTPUT->box_end();

echo $OUTPUT->heading(get_string('manage', 'format_page'));

echo $OUTPUT->box_start('', 'page-actionform');
if ($pages = course_page::get_all_pages($course->id, 'nested')) {

    $table = new html_table();
    $table->head = array(get_string('pagename', 'format_page'),
                         get_string('pageoptions', 'format_page'),
                         get_string('displaymenu', 'format_page'),
                         get_string('templating', 'format_page'),
                         get_string('publish', 'format_page'));
    $table->align       = array('left', 'center', 'center', 'center');
    $table->width       = '95%';
    $table->cellspacing = '0';
    $table->id          = 'editing-table';
    $table->class       = 'generaltable pageeditingtable';
    $table->data        = array();

    foreach ($pages as $page) {
        page_print_page_row($table, $page, $renderer);
    }

    echo '<center>';
    echo html_writer::table($table);
    echo '</center>';
} else {
    print_error('nopages', 'format_page');
}

echo '<br/><center>';
// $buttonurl = new moodle_url('/course/format/page/actions/moving.php', array('id' => $course->id));
// echo $OUTPUT->single_button($buttonurl, get_string('reorganize', 'format_page'), 'get');
$buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'format_page'), 'get');
echo '<br/></center><br/>';

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
