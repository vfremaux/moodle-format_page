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
 * Page reorganisation service
 *
 * @package format_page
 * @category format
 * @author Jeff Graham, Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/tree.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

use \format\page\course_page;
use \format\page\tree;

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

// Security.

require_login($course);
require_capability('format/page:managepages', $context);

// Set course display.

// Set course display.
tree::fix();

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
} else {
    if ($page = course_page::get_current_page($course->id)) {
        $displayid = $page->id;
    } else {
        $displayid = 0;
    }
    $pageid = course_page::set_current_page($course->id, $displayid);
}

$url = new moodle_url('/course/format/page/actions/moving.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-'.$course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

if ($service = optional_param('service', '', PARAM_TEXT)) {
    include($CFG->dirroot.'/course/format/page/actions/moving.dhtmlxcontroller.php');
}

$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxcommon.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/ext/dhtmlxtree_start.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxDataProcessor/codebase/dhtmlxdataprocessor.js', true);

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs('reorganize', true);
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('format-page-moving-content');
echo $OUTPUT->heading(get_string('reorganize', 'format_page'));

// Starts page content here.

$template = new StdClass;
$buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
$template->backbutton = $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'format_page'), 'get');
$template->wwwroot = $CFG->wwwroot;
$template->serviceurl = $url;

echo $OUTPUT->render_from_template('format_page/moving', $template);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
