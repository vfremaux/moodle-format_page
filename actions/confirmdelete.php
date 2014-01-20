<?php
/**
 * Confirms page deletion
 * 
 * @package format_page
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

	include '../../../../config.php';
	include_once $CFG->dirroot.'/course/format/page/lib.php';
	include_once $CFG->dirroot.'/course/format/page/page.class.php';
	include_once $CFG->dirroot.'/course/format/page/locallib.php';
	include_once $CFG->dirroot.'/course/format/page/renderer.php';

    $id = required_param('id', PARAM_INT);
    $pageid = optional_param('page', 0, PARAM_INT);

    if (!$course = $DB->get_record('course', array('id' => $id))){
    	print_error('invalidcourseid');
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    
	require_login($course);
    $context = context_course::instance($course->id);
	require_capability('format/page:managepages', $context);    
    require_capability('format/page:editpages', $context);

/// Set course display
    if ($pageid > 0) {
    	// changing page depending on context
        $pageid = course_page::set_current_page($course->id, $pageid);
        $page = course_page::get($pageid);
    } else {
        if (!$page = course_page::get_current_page($course->id)) {
    		print_error('errornopage', 'format_page');
        }
        $pageid = $page->id;
    }

	$url = $CFG->wwwroot.'/course/format/page/actions/manage.php?id='.$course->id;

    $PAGE->set_url($url); // Defined here to avoid notices on errors etc
    $PAGE->set_pagelayout('format_page_action');
    $PAGE->set_context($context);
    $PAGE->set_pagetype('course-view-' . $course->format);

	$renderer = new format_page_renderer($page);

// Start page content
	
	echo $OUTPUT->header();

	echo $OUTPUT->box_start('', 'page-actionform');

    $message = get_string('confirmdelete', 'format_page', format_string($page->nameone));
    $linkyes = $CFG->wwwroot.'/course/format/page/action.php?id='.$course->id.'&page='.$page->id.'&action=deletepage&sesskey='.sesskey();
    $linkno  = $page->url_build('action', 'manage');
    echo $OUTPUT->confirm($message, $linkyes, $linkno);

	echo $OUTPUT->box_end();

	echo $OUTPUT->footer();

?>