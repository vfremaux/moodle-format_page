<?php

/**
* prints the current "page" related navigation in foreign
* situations. (modules or blocks customisation)
* The module must be customized to print this navigation, and
* also store the current pageid (coming by an "aspage" parameter)
* in session. 
*
*/
function page_print_page_format_navigation($cm = null){
	global $CFG, $COURSE, $USER, $SESSION, $OUTPUT;

	require_once($CFG->dirroot.'/course/format/page/lib.php');
	require_once($CFG->dirroot.'/course/format/page/page.class.php');
	require_once($CFG->dirroot.'/course/format/page/renderers.php');

	$pageid = @$SESSION->formatpageid[$COURSE->id];

	if (!$pageid){
		$pageid = optional_param('aspage', 0, PARAM_INT);
	}
	
	if (!$pageid){
		$defaultpage = course_page::get_default_page($COURSE->id);
		$pageid = $defaultpage->id;
	}
	
	$page = course_page::get($pageid);
	$renderer = new format_page_renderer($page);

	$navbuttons = "
        <div id=\"page-region-bottom\" class=\"page-region\">
            <div class=\"region-content\">
                <div class=\"page-nav-prev\">
                ".$renderer->previous_button()."
            	</div>
                <div class=\"page-nav-next\">
                ".$renderer->next_button()."
                </div>
            </div>
        </div>
    ";
    
    echo $navbuttons;

}

/**
*
* @return true if embedded activity as page
*/
function page_save_in_session(){
	global $SESSION, $COURSE;
    $aspage = optional_param('aspage', 0, PARAM_INT);
    if ($aspage){
	    // store page id to be able to go back to following flexipage at the end of the activity.
	    $SESSION->formatpageid[$COURSE->id] = $aspage;
	    return true;	    
    } else {
    	if($currentpage = optional_param('page', 0, PARAM_INT)){
		    $SESSION->formatpageid[$COURSE->id] = $currentpage;
		}	    
		return false;
    }
}

/**
* Get all course modules from that page
*
*/
function page_get_page_coursemodules($pageid){
	$pageitems = $DB->get_records_select_menu('format_page_items', " pageid = $pageid && blockinstance = 0 AND visible = 1 ", 'sortorder', 'id, cmid');
	$cms = array();
	if ($pageitems){
		foreach($pageitems as $piid => $cmid){
			$cm = $DB->get_record('course_modules', array('id' => $cmid));
			if (!$cm->visible) continue;
			$cms[$cmid] = $cm;
		}
	}
	return $cms;
}
?>