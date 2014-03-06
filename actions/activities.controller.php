<?php

if (!defined('MOODLE_INTERNAL')) die('Sorry, you cannot use this script this way');

if ($action == 'deletemod'){
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    
    $cmid = required_param('cmid', PARAM_INT);
	$confirm = optional_param('confirm', 0, PARAM_INT);

    $cm     = get_coursemodule_from_id('', $cmid, $COURSE->id, false);
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

        // print_simple_box_start('center', '60%', '#FFAAAA', 20, 'noticebox');
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

    $deleteinstancefunction = $cm->modname."_delete_instance";

    if (!$deleteinstancefunction($cm->instance)) {
        echo $OUTPUT->notification("Could not delete the $cm->modname (instance)");
    }

    // remove all module files in case modules forget to do that
    $fs = get_file_storage();
    $fs->delete_area_files($modcontext->id);

    if (!course_delete_module($cm->id)) {
        echo $OUTPUT->notification("Could not delete the $cm->modname (coursemodule)");
    }
    if (!delete_mod_from_section($cm->id, $cm->section)) {
        echo $OUTPUT->notification("Could not delete the $cm->modname from that section");
    }

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