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
 * Main hook from moodle into the course format
 *
 * @package format_page
 * @category mod
 * @author Valery Fremaux
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/pageitem.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

use \format\page\course_page;

$CFG->blockmanagerclass = 'page_enabled_block_manager';
$CFG->blockmanagerclassfile = $CFG->dirroot.'/course/format/page/blocklib.php';

$id = required_param('id', PARAM_INT);
$pageid = optional_param('pageid', '', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

// Remove any switched roles before checking login

// PATCH+ : Page format.
// full public pages can be viewed without any login.
// some restrictions will apply to navigability
if (!course_page::check_page_public_accessibility($course)) {
    if (is_dir($CFG->dirroot.'/local/courseindex')) {
        if (!is_enrolled($context)) {
            $redirecturl = new moodle_url('/local/courseindex/pro/ajax/viewcourse.php', array('id' => $id));
            redirect($redirecturl);
        }
    }
    require_login($course);
} else {
    // we must anyway push this definition or the current course context is not established
    $COURSE = $course;
    $PAGE->set_course($COURSE);
}

$params = ['id' => $id, 'pageid' => $pageid];
$url = new moodle_url('/course/format/page/viewpage.php', $params);
$PAGE->set_url($url);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_pagelayout('format_page_single');
$page = course_page::get_current_page($COURSE->id);
if ($page){
    // course could be empty.
    $PAGE->navbar->add($page->get_name());
}

$USER->editing = 0;

/*
 * NOTE : We DO NOT resolve the page any more in format. Pagez resolution, prefork and
 * access checks should be perfomed in course/view.php additions. @see customscripts location
 */

// There are a couple processes that need some help via the session... take care of those.

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Make sure we can see this page.

if (!$page->is_visible() && !$editing) {
    if ($CFG->forcelogin && ($page->display == FORMAT_PAGE_DISP_PUBLIC)) {
        echo $OUTPUT->notification(get_string('thispageisblockedforcelogin', 'format_page'));
    } else {
        switch ($page->display) {
            case FORMAT_PAGE_DISP_HIDDEN : {
                echo $OUTPUT->notification(get_string('thispageisnotpublished', 'format_page'));
                break;
            }
            case FORMAT_PAGE_DISP_PROTECTED : {
                echo $OUTPUT->notification(get_string('thispageisprotected', 'format_page'));
                break;
            }
            case FORMAT_PAGE_DISP_DEEPHIDDEN : {
                echo $OUTPUT->notification(get_string('thispageisdeephidden', 'format_page'));
                break;
            }

        }
    }
    echo $OUTPUT->footer();
    die;
}

// Log something more precise than course.
// Event will take current course context.
$event = format_page\event\course_page_viewed::create_from_page($page);
$event->trigger();

// Start of page ouptut.

$publishsignals = '';

if (($page->display != FORMAT_PAGE_DISP_PUBLISHED) && ($page->display != FORMAT_PAGE_DISP_PUBLIC)) {
    $publishsignals .= get_string('thispageisnotpublished', 'format_page');
}
if ($page->get_user_rules() && has_capability('format/page:editpages', $context)) {
    $publishsignals .= ' '.get_string('thispagehasuserrestrictions', 'format_page');
}
if (has_capability('format/page:editprotectedpages', $context) && $page->protected) {
    $publishsignals .= ' '.get_string('thispagehaseditprotection', 'format_page');
}

$modinfo = get_fast_modinfo($course);
// Can we view the section in question ?
$pagesection = $DB->get_record('course_sections', array('id' => $page->get_section()));
$sectioninfo = $modinfo->get_section_info($pagesection->section);
if ($sectioninfo) {
    $publishsignals .= $renderer->section_availability_message($sectioninfo, true);
}

$prewidthstyle = '';
$postwidthstyle = '';
$mainwidthstyle = '';
$mainwidthspan = 12;

// Fix editing columns with size 0.
echo $OUTPUT->header();

echo '<div id="region-page-box" class="row">';

$classes = 'page-block-region bootstrap block-region span'.$mainwidthspan.' col-'.$mainwidthspan;
$classes .= ' '.@$classes['mainwidthspan'];
echo '<div id="page-region-main" '.$mainwidthstyle.' class="'.$classes.'">';
echo '<div class="region-content">';
echo $OUTPUT->blocks_for_region('main');
echo '</div>';
echo '</div>';

echo '</div>';
echo $OUTPUT->footer();

