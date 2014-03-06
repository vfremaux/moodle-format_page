<?php
/**
 * Activity management
 *
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

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
	include_once $CFG->dirroot.'/course/format/page/page.class.php';
	include_once $CFG->dirroot.'/course/format/page/locallib.php';
	include_once $CFG->dirroot.'/course/format/page/renderers.php';

    $id = required_param('id', PARAM_INT);
    $pageid = optional_param('page', 0, PARAM_INT);
    $action = optional_param('what', '', PARAM_TEXT);

    if (!$course = $DB->get_record('course', array('id' => $id))){
    	print_error('invalidcourseid');
    }
    
	require_login($course);
    $context = context_course::instance($course->id);
	require_capability('format/page:managepages', $context);    
	require_capability('moodle/course:manageactivities', $context);

/// activate controller after security

	if (!empty($action)){
		include $CFG->dirroot.'/course/format/page/actions/activities.controller.php';
	}

/// Set course display
    if ($pageid > 0) {
    	// changing page depending on context
        $pageid = course_page::set_current_page($course->id, $pageid);
        $page = course_page::get($pageid);
    } else {
        if (!$page = course_page::get_current_page($course->id)) {
    		print_error('errornopage', 'format_page');
        }
    }

	$url = $CFG->wwwroot.'/course/format/page/actions/activities.php?id='.$course->id;

    $PAGE->set_url($url); // Defined here to avoid notices on errors etc
    $PAGE->set_pagelayout('format_page_action');
    $PAGE->set_context($context);
    $PAGE->set_pagetype('course-view-' . $course->format);

	$renderer = new format_page_renderer($page);

// Start page content

	echo $OUTPUT->header();

	// Right now storing modules in a section corresponding to the current
	// page - probably should all be section 0 though
	if ($course->id == SITEID) {
	    $section = 1; // Front page only has section 1 - so use 1 as default
	} else if (isset($page->id)) {
	    $section = $page->id;
	} else {
	    $section = 0;
	}
	
	echo $OUTPUT->box_start('', 'page-actionform');
	echo $renderer->print_tabs('activities', true);
	echo $OUTPUT->box_start('boxwidthwide boxaligncenter pageeditingtable', 'editing-table');

	$modnames = get_module_types_names();
	// echo $renderer->course_section_add_cm_control($COURSE, 0, 0); // from moodle 2.5 only
	print_section_add_menus($course, $section, $modnames);	
	$modinfo = get_fast_modinfo($course);
	
	$mods = $modinfo->get_cms();
	
	if (!empty($mods)) {
	    $str         = new stdClass;
	    $str->delete = get_string('delete');
	    $str->update = get_string('update');
	    $str->locate = get_string('locate', 'format_page');
	    $path        = $CFG->wwwroot.'/course';
	    $sortedmods  = array();
	    
	    global $DB;
	    
	    foreach($mods as $mod) {
	        $modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance));
	        $modinstance->modname = $mod->modname;
	        $modinstance->cmid = $mod->id;
	
	        if (isset($modinstance->type)) {
	            if (empty($sortedmods[$mod->modname.':'.$modinstance->type])) {
	                $sortedmods[$mod->modname.':'.$modinstance->type] = array();
	            }
	            $sortedmods[$mod->modname.':'.$modinstance->type][$mod->id] = $mod;
	        } else {
	            if (empty($sortedmods[$mod->modname])) {
	                $sortedmods[$mod->modname] = array();
	            }
	            $sortedmods[$mod->modname][$mod->id] = $mod;
	        }
	    }
	
	    ksort($sortedmods);
	    $last    = '';      // keeps track of modules
	    $lastsub = '';      // keeps track of module sub-types
	
	    // Create an object sorting function
	    $function = create_function('$a, $b', 'return strnatcmp(get_string(\'modulename\', $a->modname), get_string(\'modulename\', $b->modname));');
	
	    foreach ($sortedmods as $modname => $mods) {
	    	
	        uasort($mods, $function);
	
	        if (strpos($modname, ':')) {
	            $parts = explode(':', $modname);
	            $modname = $parts[0];
	            $subname = $parts[1];
	        } else {
	            $subname = '';
	        }
	
	        if ($last != $modname) {
	            print "<h2><a href=\"$CFG->wwwroot/mod/$modname/index.php?id=$course->id\">".get_string('modulename', $modname).'</a></h1>';
	            $last    = $modname;
	            $lastsub = '';
	        }
	
	        if ($lastsub != $subname) {
	            $strtype = get_string($subname, $modname);
	            if (strpos($strtype, '[') !== false) {
	                $strtype = get_string($modname.':'.$subname, 'format_page');
	            }
	            print '&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.$strtype.'</strong><br />';
	            $lastsub = $subname;
	        }
	
	        print '<p align="right">';
	        $modulestr = get_string('module', 'format_page');
	        $usesstr = get_string('occurrences', 'format_page');
	        $commandstr = get_string('commands', 'format_page');
	        $table = new html_table();
	        $table->head = array("<b>$modulestr</b>","<b>$usesstr</b>","<b>$commandstr</b>");
	        $table->align = array('left', 'center', 'right');
	        $table->size = array('50%', '10%', '40%');
	        $table->width = '95%';
	        foreach($mods as $mod) {
	            if (!empty($modinfo)) {
	                if (empty($mod->visible)) {
	                    $linkclass = ' class="dimmed"';
	                } else {
	                    $linkclass = '';
	                }
	
	                // print '<li>';
	                $iconurl = $mod->get_icon_url();
	                $module = '<img src="'.$iconurl.'" class="icon" />';
	                // old : 
	                // print '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->cmid.'">'.format_string(strip_tags($mod->name), true, $course->id).'</a>&nbsp;';
	                $idnumberstring = '';
	                if ($idnumber = $DB->get_field('course_modules', 'idnumber', array('id' => $mod->id))){
	                	$idnumberstring = "[$idnumber] ";
	                }
	                if ($mod->modname == 'customlabel'){
	                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags(urldecode($mod->extra)), true, $course->id).'</a>&nbsp;';
	                } else if (isset($mod->name)) {
	                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags($mod->name), true, $course->id).'</a>&nbsp;';
	                } else {
	                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags($mod->modname), true, $course->id).'</a>&nbsp;';
	                }
	                $commands = '<span class="commands">';
	                // we need pageids of all locations of the module
	                $pageitems = $DB->get_records('format_page_items', array('cmid' => $mod->id));
	
	                if ($pageitems){
	                    foreach($pageitems as $pageitem){
	                        $commands .= '<a title="'.$str->locate.'" href="'.$path.'/view.php?id='.$course->id."&amp;page={$pageitem->pageid}\"><img".
	                           ' src="'.$OUTPUT->pix_url('/i/search') . '" class="icon-locate" '.
	                           ' alt="'.$str->locate.'" /></a>&nbsp;';
	                    }
	                }
	
	                $commands .= '<a title="'.$str->update.'" href="'.$path.'/mod.php?update='.$mod->id.'&sesskey='.sesskey().'"><img'.
	                   ' src="'.$OUTPUT->pix_url('/t/edit') . '" class="icon-edit" '.
	                   ' alt="'.$str->update.'" /></a>&nbsp;';
	                $commands .= '<a title="'.$str->delete.'" href="'.$CFG->wwwroot.'/course/format/page/actions/activities.php?id='.$course->id.'&amp;page='.$pageid.'&amp;what=deletemod&amp;sesskey='.sesskey().'&amp;cmid='.$mod->id.'"><img'.
	                   ' src="'.$OUTPUT->pix_url('/t/delete') . '" class="icon-edit" '.
	                   ' alt="'.$str->delete.'" /></a></span>';
	                // print '</li>';
	                $uses = $DB->count_records('format_page_items', array('cmid' => $mod->id)) + $DB->count_records('format_page', array('courseid' => $course->id, 'cmid' => $mod->id));
	                $table->data[] = array($module, $uses, $commands);
	            } else {
	                if ($mod->modname == 'customlabel'){
	                    print '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.'</li>';
	                } else {
	                    print '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.': '.$mod->instance.'</li>';
	                }
	            }
	        }
	        if (!empty($table->data)){
	            echo html_writer::table($table);
	        }
	        print '</p>';
	    }
	} else {
	    echo $OUTPUT->box(get_string('noactivitiesfound', 'format_page'));
	    echo '<br/>';
	}

	echo $OUTPUT->box_end(); // closes page action form
	echo $OUTPUT->box_end(); // closes editing table

	echo $OUTPUT->footer();
