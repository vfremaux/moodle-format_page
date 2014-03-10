<?php
/**
 * Page management
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

    if (!$course = $DB->get_record('course', array('id' => $id))){
    	print_error('invalidcourseid');
    }
    
	require_login($course);
    $context = context_course::instance($course->id);
	require_capability('format/page:managepages', $context);    
    require_capability('moodle/course:manageactivities', $context);

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

	$url = $CFG->wwwroot.'/course/format/page/actions/individualize.php?id='.$course->id;

    $PAGE->set_url($url); // Defined here to avoid notices on errors etc
    $PAGE->set_pagelayout('format_page_action');
    $PAGE->set_context($context);
    $PAGE->set_pagetype('course-view-' . $course->format);
	$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');
    $PAGE->requires->js('/course/format/page/js/individualization.js');

	$renderer = new format_page_renderer($page);

// Start page content

	echo $OUTPUT->header();

    $pagesize = (@$CFG->individualizewithtimes) ? 3 : 5 ;
    $from = optional_param('from', 0, PARAM_INT);
    $modtype = optional_param('modtype', '', PARAM_INT);
    $usersearch = optional_param('usersearch', '', PARAM_TEXT);
    $what = optional_param('what', '', PARAM_TEXT);
    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
    // Right now storing modules in a section corresponding to the current
    // page - probably should all be section 0 though
    if ($course->id == SITEID) {
        $section = 1; // Front page only has section 1 - so use 1 as default
    } else if (isset($page->id)) {
        $section = $page->id;
    } else {
        $section = 0;
    }

	// select active users to process
    if (empty($usersearch)){
        // search by capability
        $groupid = groups_get_course_group($course, true);
        
        $users = get_enrolled_users($context, '', $groupid, 'u.id,u.username,u.firstname,u.lastname,u.picture,u.email,u.emailstop', $orderby = '', $from, $pagesize);
        $allusers = get_enrolled_users($context, '', 0, 'u.id,u.username', '', 0, 0);
    } else {
        // first search by name
        if ($users = $DB->get_records_select('user', " firstname LIKE ? OR lastname LIKE ? ", array("%$usersearch%", "%$usersearch%", 'u.id,u.username,u.firstname,u.lastname,u.picture,u.email,u.emailstop'))){
            // need search users having the capability AND matching firstname or last name pattern
            foreach($users as $key => $user){
                if (!has_capability('moodle/course:view', $context, $key)){
                    unset($users[$key]);
                }
            }
        }
    }

    // MVC Implementation // all modules are already known and processd users too
    if (!empty($what)){
        include 'individualize.controller.php';
    }

    if (!empty($errors)){
        echo $OUTPUT->box_start('errorbox');
        echo implode('<br/>', $errors);
        echo $OUTPUT->box_end();
    }
    //
	echo $renderer->print_tabs('individualize', true);

    echo $OUTPUT->box_start();
    page_print_moduletype_filter($modtype, $mods, $url."&usersearch={$usersearch}");
    page_print_user_filter($url."&modtype={$modtype}");
    echo $OUTPUT->box_end();
    if (empty($usersearch)){
        page_print_pager($from, $pagesize, count($allusers), $url);
    }
    echo $OUTPUT->box_start();
    if (!empty($mods)) {
        echo "<form name=\"individualize_form\" method=\"post\" action=\"\" >";
        echo "<input type=\"hidden\" name=\"id\" value=\"{$course->id}\" >";
        echo "<input type=\"hidden\" name=\"what\" value=\"update\" >";
        echo "<table width=\"100%\" class=\"individualize\" class=\"generaltable\">";
        echo '<tr>';
        echo '<td width="20%"></td>';
        $removeallstr = get_string('removeall', 'format_page');
        $addallstr = get_string('addall', 'format_page');
        $removeforallstr = get_string('removeforall', 'format_page');
        $addtoallstr = get_string('addtoall', 'format_page');
        foreach($users as $user){
            echo '<td class="individualize col1">';
            echo fullname($user);
            echo '<br/>';
            echo "<a href=\"{$url}&what=removeall&userid={$user->id}&modtype={$modtype}\">$removeallstr</a> / <a href=\"{$url}&what=addall&userid={$user->id}&modtype={$modtype}\">$addallstr</a>";
            echo '</td>';
        }
        echo '</tr>';
        $span = 1;
        foreach ($mods as $mod) {
            if (!empty($modtype) && $mod->module != $modtype) continue;
            if (!$DB->get_records_select('format_page_items', " cmid = $mod->id")) continue;// forget modules who are not viewed in page
            $modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance));
            $modinstance->modname = $mod->modname;
            $modinstance->cmid = $mod->id;
            if (preg_match('/label$/', $mod->modname)){
                $modinstance->name = get_string('modulename', $mod->modname) . ' ' . $modinstance->id;
            }
            echo '<tr valign="top">';
            echo '<td>';
            echo '<span class="modtype">'.$mod->modfullname.':</span><br/>';
            echo "$modinstance->name";
            echo '<br/>';
            echo "<a href=\"{$url}&what=removeforall&cmid={$mod->id}&modtype={$modtype}\">$removeforallstr</a> / <a href=\"{$url}&what=addtoall&cmid={$mod->id}&modtype={$modtype}\">$addtoallstr</a>";
            echo '</td>';
            $span = 1;
            // calculate absolute max time for all bars
            $maxabsolutetime = page_get_max_access_event_time($course);
            foreach($users as $user){
                echo '<td>';
                if(!$record = $DB->get_record('page_module_access', array('userid' => $user->id, 'pageitemid' => $mod->id))){
                    $record->hidden = 0;
                    $record->revealtime = 0;
                    $record->hidetime = 0;
                } 
                $checkedstr = (!$record->hidden) ? 'checked="checked"' : '' ;
                $oncheckedstr = ($record->revealtime) ? 'checked="checked"' : '' ;
                $offcheckedstr = ($record->hidetime) ? 'checked="checked"' : '' ;
                echo "<input type=\"checkbox\" name=\"visible_cm_{$mod->id}_{$user->id}\" value= \"1\" $checkedstr />";
                echo "<input type=\"hidden\" name=\"cm[]\" value= \"{$mod->id}_{$user->id}\" /><br/>"; // for negative logic GUI 
                if (@$CFG->individualizewithtimes){
                    page_print_timebar($course, $record, $maxabsolutetime);
                    echo '<div class="onoffselectors">';
                    echo '<img src="'.$OUTPUT->pix_url('/t/hide').'" /> ';
                    echo "<input type=\"checkbox\" name=\"on_enable_{$mod->id}_{$user->id}\" value= \"1\" $oncheckedstr onclick=\"change_selector_state(this, '{$mod->id}_{$user->id}', 'on');\" />";
                    print_date_selector("on_day_{$mod->id}_{$user->id}", "on_month_{$mod->id}_{$user->id}", "on_year_{$mod->id}_{$user->id}", $record->revealtime);
                    print_time_selector("on_hour_{$mod->id}_{$user->id}", "on_min_{$mod->id}_{$user->id}", $record->revealtime, 10);
                    if (!$record->revealtime){
                        echo '<script type="text/javascript" />';
                        echo "set_disabled('{$mod->id}_{$user->id}', 'on');";
                        echo '</script>';
                    }
                    echo '<br/>';
                    echo '<img src="'.$OUTPUT->pix_url('/t/show').'" /> ';
                    echo "<input type=\"checkbox\" name=\"off_enable_{$mod->id}_{$user->id}\" value= \"1\" $offcheckedstr onclick=\"change_selector_state(this, '{$mod->id}_{$user->id}', 'off');\" />";
                    print_date_selector("off_day_{$mod->id}_{$user->id}", "off_month_{$mod->id}_{$user->id}", "off_year_{$mod->id}_{$user->id}", $record->hidetime);
                    print_time_selector("off_hour_{$mod->id}_{$user->id}", "off_min_{$mod->id}_{$user->id}", $record->hidetime, 10);
                    if (!$record->hidetime){
                        echo '<script type="text/javascript" />';
                        echo "set_disabled('{$mod->id}_{$user->id}', 'off');";
                        echo '</script>';
                    }
                }
                echo '</div>';
                echo '</td>';
                $span++;
            }
            echo '</tr>';
        }
        echo '<tr>';
        echo "<td colspan=\"$span\" align=\"center\" >";
        $savestr = get_string('update');
        echo "<p><input type=\"submit\" name=\"go_btn\" value=\"$savestr\" /></p>";
        echo '</td>';
        echo '</tr>';
        echo "</table>";
        echo "</form>";
    } else {
        echo $OUTPUT->box(get_string('noactivitiesfound', 'format_page'));
    }
    echo $OUTPUT->box_end();
    
    echo $OUTPUT->footer();

/// Local utility functions

/**
* prints a modtype selector for individualization checkboard
*
*/
function page_print_moduletype_filter($modtype, $mods, $url){
	global $DB;

    if (empty($mods)) return;
    // start counting how many instances in which type
    $modcount = array();
    $modnames = array();
    foreach($mods as $mod){
        if (!$DB->get_records_select('format_page_items', " cmid = $mod->id")) continue;// forget modules who are not viewed in page
        isset($modcount[$mod->module]) ? $modcount[$mod->module]++ : $modcount[$mod->module] = 1 ;
        $modnames[$mod->module] = $mod->modfullname;
    }
    foreach(array_keys($modcount) as $modid){
        $modtypes[$modid] = $modnames[$modid]. ' ('.$modcount[$modid].')';
    }
    echo "<form name=\"moduletypechooser\" action=\"$url\" method=\"post\" style=\"display:inline\">";
    echo get_string('filterbytype', 'format_page');
    echo html_writer::select($modtypes, 'modtype', $modtype, array('' => get_string('seealltypes', 'format_page')), array('onchange' => 'document.forms[\'moduletypechooser\'].submit();'));
    echo "</form>";
}

/**
* prints a small user search engine form
*
*/
function page_print_user_filter($url){

    // start counting how many instances in which type
    $usersearch = optional_param('usersearch', '', PARAM_TEXT);
    echo "<form name=\"usersearchform\" action=\"$url\" method=\"post\" style=\"display:inline\">";
    $usersearchstr = get_string('searchauser', 'format_page');
    echo "<input type=\"text\" name=\"usersearch\" value=\"{$usersearch}\" />";
    echo "<input type=\"submit\" name=\"go_btn\" value=\"{$usersearchstr}\" />";
    echo "</form>";
}

/**
*
*
*/
function page_get_pageitem_changetime($direction, $userid, $cmid){
    global $CFG;

    if (empty($CFG->individualizewithtimes)) return 0;

    $yearkey = $direction."_year_{$cmid}_{$userid}";
    $monthkey = $direction."_month_{$cmid}_{$userid}";
    $daykey = $direction."_day_{$cmid}_{$userid}";
    $hourkey = $direction."_hour_{$cmid}_{$userid}";
    $minkey = $direction."_min_{$cmid}_{$userid}";
    $enablekey = $direction."_enable_{$cmid}_{$userid}";
    $enabling = optional_param($enablekey, false, PARAM_INT);
    if (empty($enabling)){
        return 0;
    } else {
        $year = optional_param($yearkey, 0, PARAM_INT);
        $month = optional_param($monthkey, 0, PARAM_INT);
        $day = optional_param($daykey, 0, PARAM_INT);
        $hour = optional_param($hourkey, 0, PARAM_INT);
        $min = optional_param($minkey, 0, PARAM_INT);
        $time = mktime($hour, $min , 0, $month, $day , $year);
        return $time;
    }
}

/**
*
*
*/
function page_print_timebar($course, $itemaccess, $absolutemaxtime){
    global $CFG;

    $now = time();
    $trackwidth = 200;    
    $track[$course->startdate] = 'undefined';
    $track[$now] = ($itemaccess->hidden) ? 'hidden' : 'visible';
    if ($itemaccess->revealtime)
        $track[$itemaccess->revealtime] = 'visible';
    if ($itemaccess->hidetime){
        $track[$itemaccess->hidetime] = 'hidden';
    }
    $track[$absolutemaxtime] = 'undefined'; // value is not really usefull on last record
    $grratio = $trackwidth / ($absolutemaxtime - $course->startdate);
    ksort($track);
    $lastdate = 0;
    $currenttime = 0;
    foreach($track as $tracktime => $trackstate){
        if (empty($lastdate)){
            $laststate = $trackstate;
            $lastdate = $tracktime;
        } else {
            $trackqsegmentwidth = $grratio * ($tracktime - $lastdate);
            $img = $CFG->wwwroot.'/course/format/page/pix/individualization/'.$laststate.'.gif';
            if ($lastdate == $now){
                $eventimg = $CFG->wwwroot.'/course/format/page/pix/individualization/now.gif';
            } else {
                $eventimg = $CFG->wwwroot.'/course/format/page/pix/individualization/event.gif';
            }
            $eventlabel = userdate($lastdate);
            echo "<img src=\"$eventimg\" title=\"$eventlabel\" height=\"16\" />";
            echo "<img src=\"$img\" width=\"$trackqsegmentwidth\" height=\"16\" />";
            $lastdate = $tracktime;
            $laststate = $trackstate;
        }
    }
    $trackqsegmentwidth = $grratio * ($tracktime - $lastdate);
    $img = $CFG->wwwroot.'/course/format/page/pix/individualization/'.$laststate.'.gif';
    if ($lastdate == $now){
        $eventimg = $CFG->wwwroot.'/course/format/page/pix/individualization/now.gif';
    } else {
        $eventimg = $CFG->wwwroot.'/course/format/page/pix/individualization/event.gif';
    }
    echo "<img src=\"$img\" width=\"$trackqsegmentwidth\" height=\"16\" />";
    $eventlabel = userdate($lastdate);
    echo "<img src=\"$eventimg\" title=\"$eventlabel\" height=\"16\" />";
}

function page_get_max_access_event_time($course){
	global $DB;
	
    $maxreveal = $DB->get_field_select('page_module_access', 'max(revealtime)', " course = $course->id ");
    $maxhide = $DB->get_field_select('page_module_access', 'max(hidetime)', " course = $course->id ");
    $maxtime = max(time(), $maxreveal, $maxhide);
    return $maxtime + 10 * DAYSECS;
}


?>