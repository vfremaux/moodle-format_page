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
 * @author Valery Fremaux (valery.fremaux@gmail.com) for moodle 2
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/forms/editpage_form.php');
require_once($CFG->dirroot.'/course/format/page/classes/event/course_page_created.php');

use \format\page\course_page;

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

// Security.

require_login($course);
require_capability('format/page:editpages', $context);

$params = array('id' => $course->id, 'pageid' => $pageid);
$PAGE->set_url('/course/format/page/actions/editpage.php', $params); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxcommon.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/ext/dhtmlxtree_start.js');

$returnaction = optional_param('returnaction', '', PARAM_ALPHA);
$page = null;

// Defaultpage is used as default context for building URLs.
if ($pageid) {
    if ($returnaction) {
        $currenttab = $returnaction;
    } else {
        $currenttab = 'settings';
    }
    $defaultpage = course_page::load($pageid);
    $page = $defaultpage;

    // Security : check page is not protected.
    if ($page->protected && !has_capability('format/page:editprotectedpages', $context)) {
        print_error('erroreditnotallowed', 'format_page');
    }
} else {
    require_capability('format/page:addpages', $context);
    $currenttab = 'addpage';
    $defaultpage = course_page::get_default_page($course->id);
}

// Find possible parents for the edited page.
if ($defaultpage && $parents = $defaultpage->get_possible_parents($course->id, $pageid == 0)) {
    $possibleparents = array(0 => get_string('none'));
    foreach ($parents as $parent) {
        $possibleparents[$parent->id] = $parent->get_name();
    }
} else {
    $possibleparents = array();
}

// Get global templates.
$templates = course_page::get_global_templates();

$params = array('pageid' => $pageid, 'parents' => $possibleparents, 'globaltemplates' => $templates);
$mform = new page_editpage_form(new moodle_url('/course/format/page/actions/editpage.php'), $params);

// Form controller.
if ($mform->is_cancelled()) {
    if ($returnaction) {
        // Return back to a specific action.
        redirect($defaultpage->url_build('action', $returnaction));
    } else {
        if (empty($defaultpage)) {
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        }
        redirect($defaultpage->url_build());
    }
} else if ($data = $mform->get_data()) {
    $page = page_edit_page($data, $pageid, $defaultpage, $page);

    $event = format_page\event\course_page_created::create_from_page($page);
    $event->trigger();

    // Prepare a minimaly loaded moodle page object representing the new course page context.
    $moodlepage = new moodle_page();
    $moodlepage->set_course($COURSE);
    $moodlepage->set_context($context);
    $moodlepage->set_pagelayout('format_page');

    // Prepare a block manager instance for operating blocks.
    $blockmanager = new page_enabled_block_manager($moodlepage);
    $blockmanager->add_region('side-pre');
    $blockmanager->add_region('main');
    $blockmanager->add_region('side-post');

    // Feed page with page tracker block and administration.
    $params = array('blockname' => 'page_tracker', 'subpagepattern' => null, 'parentcontextid' => $context->id);
    if (!$DB->record_exists('block_instances', $params)) {
        // Checks has no "display on all course pages" instance.
        if ($DB->record_exists('block', array('name' => 'page_tracker', 'visible' => 1))) {
            $params = array('blockname' => 'page_tracker', 'subpagepattern' => 'page-'.$page->id, 'parentcontextid' => $context->id);
            if (!$DB->record_exists('block_instances', $params)) {
                $blockmanager->add_block('page_tracker', 'side-pre', 0, true, 'course-view-*', 'page-'.$page->id);
            }
        }
    }

    if ($returnaction) {
        // Return back to a specific action.
        redirect($page->url_build('page', $page->id, 'action', $returnaction));
    } else {
        // Default, view the page.
        redirect($page->url_build('page', $page->id));
    }
}

// No controller action.

/*
 * Set up data to be sent to the form
 * Might come from a page or page template record
 */
$toform = new stdClass;
$fields = 'bsprefleftwidth, bsprefcenterwidth, bsprefrightwidth, prefleftwidth, prefcenterwidth,';
$fields .= ' prefrightwidth, showbuttons, display, courseid, cmid';
if ($pageid) {
    $toform = $page->get_formatpage();
    $toform->page = $page->id;
} else if ($template = $DB->get_record('format_page', array('template' => 1, 'courseid' => $course->id), $fields)) {
    $template->cmid = 0;
    $toform = $template;
    $page = new course_page($template);
    $toform->page = 0;
    $toform->nameone = ''; // Do not copy template page names.
    $toform->nametwo = ''; // Do not copy template page names.
} else {
    $page = new course_page(null);
    $toform->page = 0;
}

// Done here on purpose.
$toform->id = $course->id;
$toform->returnaction = $returnaction;

// Cleanup disappeared course modules.
if (@$toform->cmid && !$DB->record_exists('course_modules', array('id' => $toform->cmid))) {
    $toform->cmid = 0;
}
if (@$toform->lockingcmid && !$DB->record_exists('course_modules', array('id' => $toform->lockingcmid))) {
    $toform->lockingcmid = 0;
    $toform->lockingscore = 0;
}

if (format_page_is_bootstrapped()) {
    // Transfer width values from bootstrap to standard for the form.
    $toform->prefleftwidth = (isset($toform->bsprefleftwidth)) ? $toform->bsprefleftwidth : 3;
    $toform->prefcenterwidth = (isset($toform->bsprefcenterwidth)) ? $toform->bsprefcenterwidth : 6;
    $toform->prefrightwidth = (isset($toform->bsprefrightwidth)) ? $toform->bsprefrightwidth : 3;
}
$mform->set_data($toform);

$page->id = $toform->page ;

// Start producing page.
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs($currenttab, true);
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('', 'page-actionform');
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

