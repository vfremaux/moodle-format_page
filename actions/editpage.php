<?php
/**
 * Page reorganisation service
 * 
 * @package format_page
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

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

	//default location of form
	$formfile = $CFG->dirroot.'/course/format/page/actions/editpage_form.php';
	require_once($formfile);

	$returnaction = optional_param('returnaction', '', PARAM_ALPHA);

	// defaultpage is used as default context for building URLs.
	if ($pageid){
	    if ($returnaction) {
	        $currenttab = $returnaction;
	    } else {
	        $currenttab = 'settings';
	    }
	    $defaultpage = course_page::load($pageid);
	    $page = $defaultpage;
	} else {
	    require_capability('format/page:addpages', $context);
	    $currenttab = 'addpage';
	    $defaultpage = course_page::get_default_page($course->id);
	}

	// Find possible parents for the edited page
	if ($defaultpage && $parents = $defaultpage->get_possible_parents($course->id, $pageid == 0)) {
	    $possibleparents = array(0 => get_string('none'));
	    foreach ($parents as $parent) {
	        $possibleparents[$parent->id] = $parent->get_name();
	    }
	} else {
	    $possibleparents = array();
	}

	$mform = new format_page_editpage_form($CFG->wwwroot.'/course/format/page/actions/editpage.php', $possibleparents);

	// form controller
	if ($mform->is_cancelled()) {
	    if ($returnaction) {
	        // Return back to a specific action
	        redirect($defaultpage->url_build('action', $returnaction));
	    } else {
	        redirect($defaultpage->url_build());
	    }
	
	} else if ($data = $mform->get_data()) {
	    // Save/update routine
	    $pagerec = new StdClass;
	    $pagerec->nameone         	= $data->nameone;
	    $pagerec->nametwo         	= $data->nametwo;
	    $pagerec->courseid        	= $COURSE->id;
	    $pagerec->display         	= 0 + @$data->display;
	    $pagerec->displaymenu     	= 0 + @$data->displaymenu;
	    $pagerec->prefleftwidth   	= (@$data->prefleftwidth == '*') ? '*' : 0 + @$data->prefleftwidth ;
	    $pagerec->prefcenterwidth 	= (@$data->prefcenterwidth == '*') ? '*' : 0 + @$data->prefcenterwidth ;
	    $pagerec->prefrightwidth  	= (@$data->prefrightwidth == '*') ? '*' : 0 + @$data->prefrightwidth ;
	    $pagerec->template        	= $data->template;
	    $pagerec->showbuttons     	= $data->showbuttons;
	    $pagerec->parent          	= $data->parent;
	    $pagerec->cmid          	= 0 + @$data->cmid; // there are no mdules in course
	    $pagerec->lockingcmid       = 0 + @$data->lockingcmid; // there are no mdules in course
	    $pagerec->lockingscore      = 0 + @$data->lockingscore; // there are no mdules in course
	    $pagerec->datefrom         	= 0 + @$data->datefrom; // there are no mdules in course
	    $pagerec->dateto         	= 0 + @$data->dateto; // there are no mdules in course
	    $pagerec->relativeweek      = 0 + @$data->relativeweek; // there are no mdules in course
	
	    // There can only be one!
	    if ($pagerec->template) {
	        // only one template page allowed
	        $DB->set_field('format_page', 'template', 0, array('courseid' => $pagerec->courseid));
	    }
	
	    if ($pageid) {
	        // Updating existing record
	        $pagerec->id = $data->page;
	
	        if ($pagerec->parent != $DB->get_field('format_page', 'parent', array('id' => $pagerec->id))) {
	            // Moving - re-assign sortorder
	            $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);
	
	            // Remove from old parent location
	            course_page::remove_from_ordering($pagerec->id);
	        }
			$page->set_formatpage($pagerec);
			$page->save();
	    } else {
	        // Creating new
	        $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);

	    	$page = new course_page($pagerec);
			$page->save();
	    }
    
	
	    if ($returnaction) {
	        // Return back to a specific action
	        redirect($page->url_build('page', $page->id, 'action', $returnaction));
	    } else {
	        // Default, view the page
	        redirect($page->url_build('page', $page->id));
	    }
	}

	// No controller action
	
    // Set up data to be sent to the form
    // Might come from a page or page template record
    $toform = new stdClass;

    if ($pageid) {
        $toform       = $page->get_formatpage();
        $toform->page = $page->id;
    } else if ($template = $DB->get_record('format_page', array('template' => 1, 'courseid' => $course->id), 'prefleftwidth, prefcenterwidth, prefrightwidth, showbuttons, display, courseid, cmid')) {
    	$template->cmid = 0;
        $toform = $template;
        $page = new course_page($template);
        $toform->page = 0;
	    $toform->nameone = ''; // do not copy template page names
	    $toform->nametwo = ''; // do not copy template page names
    } else {
        $page = new course_page(null);
        $toform->page = 0;
    }

    // Done here on purpose
    $toform->id = $course->id;
    $toform->returnaction = $returnaction;

    // cleanup disappeared course modules
    if (@$toform->cmid && !$DB->record_exists('course_modules', array('id' => $toform->cmid))){
    	$toform->cmid = 0;
    }
    if (@$toform->lockingcmid && !$DB->record_exists('course_modules', array('id' => $toform->lockingcmid))){
    	$toform->lockingcmid = 0;
    	$toform->lockingscore = 0;
    }

    $mform->set_data($toform);

// Start producing page

	echo $OUTPUT->header();    
	
    echo $OUTPUT->box_start('', 'page-actionform');
    $renderer = new format_page_renderer($page);
    echo $renderer->print_tabs($currenttab, true);
    $mform->display();
    echo $OUTPUT->box_end();

	echo $OUTPUT->footer();    

