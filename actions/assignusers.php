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
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/actions/assignuserslib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

require_login($course);

$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);

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

$url = new moodle_url('/course/format/page/actions/assignusers.php', array('page' => $pageid, 'id' => $course->id));

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

echo $OUTPUT->box_start('page-block-region bootstrap block-region', 'region-main');
echo $OUTPUT->box_start('', 'page-actionform');

$params = array('pageid' => $pageid, 'courseid' => $course->id);
$pagemembersselector = new page_members_selector('removeselect', $params);
$potentialmembersselector = new page_non_members_selector('addselect', $params);

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoadd = $potentialmembersselector->get_selected_users();
    if (!empty($userstoadd)) {
         foreach ($userstoadd as $user) {
            if (!$page->add_member($user->id)) {
                print_error('erroraddremoveuser', 'format_page', $url);
            }
            $pagemembersselector->invalidate_selected_users();
            $potentialmembersselector->invalidate_selected_users();
        }
    }
}

if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $pagemembersselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $user) {
            if (!$page->remove_member($user->id)) {
                print_error('erroraddremoveuser', 'format_page', $url);
            }
            $pagemembersselector->invalidate_selected_users();
            $potentialmembersselector->invalidate_selected_users();
        }
    }
}

echo $renderer->add_members_form($pageid, $course, $potentialmembersselector, $pagemembersselector);

echo '<br/><center>';
$buttonurl = new moodle_url('/course/format/page/actions/manage.php', array('id' => $course->id));
echo $OUTPUT->single_button($buttonurl, get_string('manage', 'format_page'), 'get');
$buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'format_page'), 'get');
echo '<br/></center>';

echo $OUTPUT->box_end();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
