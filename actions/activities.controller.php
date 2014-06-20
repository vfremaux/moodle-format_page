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

if (!defined('MOODLE_INTERNAL')) {
    die('Sorry, you cannot use this script this way');
}

if ($action == 'deletemod') {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $cmid = required_param('cmid', PARAM_INT);
    $confirm = optional_param('confirm', 0, PARAM_INT);

    $cm = get_coursemodule_from_id('', $cmid, $COURSE->id, false);

    if (!$cm) {
        return;
    }

    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    $coursecontext = context_course::instance($cm->course);
    $modcontext = context_module::instance($cm->id);

    $return = $CFG->wwwroot.'/course/format/page/actions/activities.php';

    $optionsdefault = array('id' => $cm->course, 'page' => $pageid, 'sesskey' => sesskey());

    if (!$confirm or !confirm_sesskey()) {
        $fullmodulename = get_string('modulename', $cm->modname);

        $optionsyes = array('id' => $cm->course, 'page' => $pageid, 'what' => 'deletemod', 'confirm' => 1, 'cmid' => $cm->id, 'sesskey' => sesskey());

        $strdeletecheck = get_string('deletecheck', '', $fullmodulename);
        $strdeletecheckfull = get_string('deletecheckfull', '', "$fullmodulename '$cm->name'");

        $PAGE->set_url($return);
        $PAGE->set_context($coursecontext);
        $PAGE->set_pagetype('mod-' . $cm->modname . '-delete');
        $PAGE->set_title($strdeletecheck);
        $PAGE->set_heading($course->fullname);
        $PAGE->navbar->add($strdeletecheck);
        echo $OUTPUT->header();

        echo $OUTPUT->box_start('noticebox');
        $formcontinue = new single_button(new moodle_url($return, $optionsyes), get_string('yes'));
        $formcancel = new single_button(new moodle_url($return, $optionsdefault), get_string('no'), 'get');
        echo $OUTPUT->confirm($strdeletecheckfull, $formcontinue, $formcancel);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();

        exit;
    }

    $modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";

    if (file_exists($modlib)) {
        require_once($modlib);
    } else {
        print_error('modulemissingcode', '', '', $modlib);
    }

	/* Note : comment out for 2.5 */
	// all this is done by course_delete_instance();
    $deleteinstancefunction = $cm->modname."_delete_instance";

    if (!$deleteinstancefunction($cm->instance)) {
        echo $OUTPUT->notification("Could not delete the $cm->modname (instance)");
    }

    // remove all module files in case modules forget to do that
    $fs = get_file_storage();
    $fs->delete_area_files($modcontext->id);

	if ($cm = $DB->get_record('course_modules', array('id' => $cm->id))){
		// Care !! delete_course_module is 2.4 primitive/ > 2.5 is course_delete_module
	    delete_course_module($cm->id);
	    if (!delete_mod_from_section($cm->id, $cm->section)) {
	        // echo $OUTPUT->notification("Could not delete the $cm->modname from that section");
	    }
	}

	// delete all relevant page items in course	
	course_page::delete_cm_blocks($cm->id);

    // Trigger a mod_deleted event with information about this module.
    $eventdata = new stdClass();
    $eventdata->modulename = $cm->modname;
    $eventdata->cmid       = $cm->id;
    $eventdata->courseid   = $course->id;
    $eventdata->userid     = $USER->id;
    events_trigger('mod_deleted', $eventdata);

    add_to_log($course->id, 'course', "delete mod",
               "view.php?id=$cm->course",
               "$cm->modname $cm->instance", $cm->id);
	
	$returnurl = new moodle_url($return, $optionsdefault);
    redirect($returnurl->out());
}