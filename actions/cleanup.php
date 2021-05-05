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
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

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

$PAGE->set_url('/course/view.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxcommon.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/ext/dhtmlxtree_start.js');

echo $OUTPUT->header();

$confirm = optional_param('confirm', null, PARAM_INT);
echo $OUTPUT->heading(get_string('cleanuptitle', 'format_page'));

if (empty($confirm)) {
    $page = course_page::get($pageid);
    echo $OUTPUT->confirm(get_string('cleanupadvice', 'format_page'), $page->url_build('action', 'cleanup', 'confirm', 1), $page->url_build('action', 'activities'));
    echo $OUTPUT->footer();
    die;
} else {
    echo $OUTPUT->box_start('', 'region-main');

    echo $OUTPUT->heading(get_string('cleanuptitle', 'format_page'));

    // Delete unused modules.
    $deleted = array();
    if ($unuseds = page_get_unused_course_modules($COURSE->id)) {
        foreach ($unuseds as $unused) {
            // Check if not used by a direct page embedding.
            if ($DB->record_exists('format_page', array('courseid' => $COURSE->id, 'cmid' => $unused->id))) {
                continue; // Do not delete, they are used.
            }
            @$deleted[$unused->name]++;
            $modcontext = context_module::instance($unused->id);
            if ($DB->record_exists('course_modules', array('id' => $unused->id))) {
                try {
                    /*
                     * First remove from all sections. this will make standard code fail
                     * but ensures we have no course module in the way somewhere
                     */
                    if ($sections = $DB->get_records('course_sections', array('course' => $COURSE->id))) {
                        foreach ($sections as $s) {
                            // Delete wherever it may be.
                            delete_mod_from_section($unused->id, $s->id);
                        }
                    }

                    // Do all other deletion tasks.
                    course_delete_module($unused->id);
                } catch (Exception $e) {
                    // We need finish the job after failing to delete from section.

                    // Trigger event for course module delete action.
                    $event = \core\event\course_module_deleted::create(array(
                        'courseid' => $unused->course,
                        'context'  => $modcontext,
                        'objectid' => $unused->id,
                        'other'    => array(
                            'modulename' => $unused->name,
                            'instanceid'   => $unused->instance,
                        )
                    ));
                    unset($unused->name);
                    $event->add_record_snapshot('course_modules', $unused);
                    $event->trigger();
                    rebuild_course_cache($unused->course, true);
                }
            }
        }
    }

    echo $OUTPUT->box_start('error');
    if (!empty($deleted)) {
        foreach (array_keys($deleted) as $modulename) {
            if (!empty($deleted[$modulename])) {
                $a = new StdClass();
                $a->name = get_string('modulename', $modulename);
                $a->value = $deleted[$modulename];
                mtrace(get_string('cleanupreport', 'format_page', $a)."<br/>\n");
            }
        }
    }
    echo $OUTPUT->box_end();

    echo '<p>';
    $params = array('page' => $pageid, 'id' => $COURSE->id);
    echo $OUTPUT->continue_button(new moodle_url('/course/format/page/actions/activities.php', $params));
    echo '</p>';
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();