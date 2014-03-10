<?php

	include '../../../../config.php';
	include_once $CFG->dirroot.'/course/format/page/lib.php';
	include_once $CFG->dirroot.'/course/format/page/locallib.php';
	include_once $CFG->dirroot.'/course/format/page/renderers.php';
	include_once $CFG->dirroot.'/course/format/page/page.class.php';

    $id = required_param('id', PARAM_INT);
    $pageid = optional_param('page', 0, PARAM_INT);
    
    if (!$course = $DB->get_record('course', array('id' => $id))){
    	print_error('invalidcourseid');
    }
    
    $context = context_course::instance($course->id);

	require_login($course);
    require_capability('format/page:editpages', $context);

    $PAGE->set_url('/course/view.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc
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

    if (empty($confirm)){
    	$page = course_page::get($pageid);
        echo $OUTPUT->confirm(get_string('cleanupadvice', 'format_page'), $page->url_build('action', 'cleanup', 'confirm', 1), $page->url_build('action', 'activities'));
        return;
    } else {

        // Get the list of all course modules that HAVE NOT any insertion 
        // in the course.
        $sql = "
            SELECT 
                cm.id, m.name
            FROM
                {course_modules} cm,
                {modules} m
            WHERE
                cm.module = m.id AND
                cm.course = {$COURSE->id} AND
                cm.id NOT IN (
                SELECT DISTINCT
                    fpi.cmid
                FROM
                    {format_page_items} fpi,
                    {format_page} fp
                WHERE
                    fp.id = fpi.pageid AND
                    fp.courseid = {$COURSE->id} AND
                    fpi.cmid != 0
            )             
        ";
        // delete unused modules
        $deleted = array();
        if ($unuseds = $DB->get_records_sql($sql)){
            foreach($unuseds as $unused){
            	// check if not used by a direct page embedding
            	if ($DB->record_exists('format_page', array('courseid' => $COURSE->id, 'cmid' => $unused->id))) continue; // do not delete, they are used
                @$deleted[$unused->name]++;
                delete_course_module($unused->id);
            }
        }
        rebuild_course_cache($COURSE->id);

        echo $OUTPUT->box_start('error');
        if (!empty($deleted)){
            foreach(array_keys($deleted) as $modulename){
                if (!empty($deleted[$modulename])){
                	$a = new StdClass();
                    $a->name = get_string('modulename', $modulename);                
                    $a->value = $deleted[$modulename];                
                    mtrace(get_string('cleanupreport', 'format_page', $a)."<br/>\n");
                }
            }
        }
        echo $OUTPUT->box_end();

        echo '<p>';
        echo $OUTPUT->continue_button($CFG->wwwroot."/course/format/page/actions/activities.php?page=$pageid&id={$COURSE->id}");
        echo '</p>';
	}

	echo $OUTPUT->footer();