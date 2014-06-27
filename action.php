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
 * Loads all course context and performs a controller action.
 *
 * this page MUST NOT output anything before passing hand to $page controller @see format_page::execute_url_action
 * $page controller
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/renderers.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT); // Format Page record ID.
$action = optional_param('action', '', PARAM_ALPHA); // What the user is doing.

if (! ($course = $DB->get_record('course', array('id' => $id)))) {
    print_error('invalidcourseid', 'error');
}

$PAGE->set_url('/course/format/page/action.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc.

context_helper::preload_course($course->id);
if (!$context = context_course::instance($course->id)) {
    print_error('nocontext');
}

require_login($course);

// Reset course page state - this prevents some weird problems ;-).
$USER->activitycopy = false;
$USER->activitycopycourse = null;
unset($USER->activitycopyname);
unset($SESSION->modform);

// Note : Page is set but not used as no output is done during controller action (unless error messages).
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

if (!isset($USER->editing)) {
    $USER->editing = 0;
    redirect($CFG->wwwroot .'/course/view.php?id='. $course->id);
}

$SESSION->fromdiscussion = $CFG->wwwroot .'/course/view.php?id='. $course->id;

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

/*
 * We are currently keeping the button here from 1.x to help new teachers figure out
 * what to do, even though the link also appears in the course admin block.  It also
 * means you can back out of a situation where you removed the admin block. :)
 */
if ($PAGE->user_allowed_editing()) {
    $buttons = $OUTPUT->edit_button(new moodle_url('/course/view.php', array('id' => $course->id)));
    $PAGE->set_button($buttons);
}

$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

// Insert at least one section if none.

if (! $section = $DB->get_record('course_sections', array('course' => $course->id, 'section' => 0))) {
    $section = new StdClass();
    $section->course = $course->id;   // Create a default section.
    $section->section = 0;
    $section->visible = 1;
    $section->summaryformat = FORMAT_HTML;
    $section->id = $DB->insert_record('course_sections', $section);
}

rebuild_course_cache($course->id);

$pageid = optional_param('page', 0, PARAM_INT);       // Format_page record ID.
$action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing.

// Set course display.

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

// Check out the $pageid - set? valid? belongs to this course ?

if (!empty($pageid)) {
    if (empty($page) or $page->id != $pageid) {
        // Didn't get the page above or we got the wrong one...
        if (!$page = course_page::get($pageid)) {
            print_error('errorpageid', 'format_page');
        }
        $page->formatpage->id = $pageid;
    }

    // Ensure this page is in this course.

    if ($page->courseid != $course->id) {
        print_error('invalidpageid', 'format_page', '', $pageid);
    }
} else {
    // We don't have a page ID to work with (probably no pages yet in course).
    if (has_capability('format/page:editpages', $context)) {
        $action = 'editpage';
        $page = new course_page(null);
    } else {
        // Nothing this person can do about it, error out.
        $PAGE->set_title($SITE->name);
        $PAGE->set_heading($SITE->name);
        echo $OUTPUT->header();
        print_error('nopageswithcontent', 'format_page');
    }
}

// There are a couple processes that need some help via the session... take care of those.

$action = page_handle_session_hacks($page, $course->id, $action);

$renderer = new format_page_renderer($page);

// Handle format actions, all actions should redirect.

$page->execute_url_action($action, $renderer);
