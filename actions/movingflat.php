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
require_once($CFG->dirroot.'/course/format/page/actions/movingflat.controller.php');

use \format\page\course_page;
use \format\page\tree;
use \format\page\movingflat_controller;

$id = required_param('id', PARAM_INT); // Course id
$action = optional_param('what', '', PARAM_ALPHA);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

// Security.

require_login($course);
require_capability('format/page:managepages', $context);

// Set course display.

// Set course display.
// tree::fix();

$url = new moodle_url('/course/format/page/actions/movingflat.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-'.$course->format);
$PAGE->requires->jquery_plugin('ui');

$page = course_page::get_current_page();
$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

if ($action) {
    $controller = new movingflat_controller();
    $controller->receive($action);
    $controller->process($action);
    redirect($url);
}

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs('reorganize', true);
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('format-page-moving-content');
echo $OUTPUT->heading(get_string('reorganize', 'format_page'));

// Starts page content here.

$template = new StdClass;
$template->wwwroot = $CFG->wwwroot;
$template->serviceurl = $url;
$template->indent = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

$pages = course_page::get_all_pages($course->id);
$pagetpls = course_page::templatize($pages, $url);

$template->pages = $pagetpls;
echo $OUTPUT->render_from_template('format_page/movingflat', $template);

echo '<br/>';
echo '<center>';
$buttonurl = new moodle_url('/course/view.php', array('id' => $course->id));
$backbutton = $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'format_page'), 'get');
echo $backbutton;
echo '</center>';

echo $OUTPUT->box_end();

echo $OUTPUT->footer();