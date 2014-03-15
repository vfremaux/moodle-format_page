<?php

/**
 * Activity individualization management
 *
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * 
 * @usecase update
 * @usecase removeall
 * @usecase addall
 * @usecase removeforall
 * @usecase addtoall
 */

// get all modules from the type we can add
$allowedmods = array();
foreach($mods as $mod){
    if ($mod->module == $modtype || empty($modtype)){
        $allowedmods[] = $mod->id;
    } 
}
$modlist = implode("','", $allowedmods);

/********************** Update individualization switches ***********************/
if ($what == 'update'){
    $cms = required_param_array('cm', PARAM_RAW);
    $DB->delete_records('block_page_module_access', array('course' => $course->id));
    foreach($cms as $cm){
        list($cmid, $userid) = explode('_', $cm);
        if (!$visible_cm = optional_param("visible_cm_{$cmid}_{$userid}", '', PARAM_INT)){
        	$cmrec = new StdClass;
            $cmrec->course = $course->id;
            $cmrec->pageitemid = $cmid;
            $cmrec->userid = $userid;
            $cmrec->hidden = 1;
            $cmrec->revealtime = page_get_pageitem_changetime('on', $userid, $cmid);
            if ($cmrec->revealtime != 0 && $cmrec->revealtime < time()){
                $cmrec->revealtime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
            $cmrec->hidetime = page_get_pageitem_changetime('off', $userid, $cmid);
            if ($cmrec->hidetime != 0 && $cmrec->hidetime < time()) {
                $cmrec->hidetime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
        } else {
        	$cmrec = new StdClass();
            $cmrec->course = $course->id;
            $cmrec->pageitemid = $cmid;
            $cmrec->userid = $userid;
            $cmrec->hidden = 0;
            $cmrec->revealtime = page_get_pageitem_changetime('on', $userid, $cmid);
            if ($cmrec->revealtime != 0 && $cmrec->revealtime < time()){
                $cmrec->revealtime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
            $cmrec->hidetime = page_get_pageitem_changetime('off', $userid, $cmid);
            if ($cmrec->hidetime != 0 && $cmrec->hidetime < time()) {
                $cmrec->hidetime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
        }
        if (!$DB->insert_record('block_page_module_access', $cmrec)){
            print_error('errorinsertaccessrecord', 'format_page');
        }
    }
    // print_string('updated');    
}
/********************** Remove all ***********************/
if ($what == 'removeall'){
    $userid = required_param('userid', PARAM_INT);
    $DB->delete_records_select('block_page_module_access', " course = ? AND userid = ? AND pageitemid IN ('$modlist') ", array($course->id, $userid));
    foreach($mods as $mod){
        if (!in_array($mod->id, $allowedmods)) continue;
        $cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $mod->id;
        $cmrec->userid = $userid;
        $cmrec->hidden = 1;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
    // print_string('updated');    
}
/********************** Add all ***********************/
if ($what == 'addall'){
    $userid = required_param('userid', PARAM_INT);

    // delete only hide switches from this module !
    $DB->delete_records_select('block_page_module_access', " course = ? AND userid = ? AND pageitemid IN ('$modlist') ", array($course->id, $userid));
    // print_string('updated');    
}
/********************** Remove course module to all ***********************/
if ($what == 'removeforall'){
    $cmid = required_param('cmid', PARAM_INT);
    $DB->delete_records_select('block_page_module_access', " course = ? AND pageitemid = ? ", array($course->id, $cmid));

    foreach($users as $user){
    	$cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $cmid;
        $cmrec->userid = $user->id;
        $cmrec->hidden = 1;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
    // print_string('updated');    
}
/********************** add coursemodule to all ***********************/
if ($what == 'addtoall'){
    $cmid = required_param('cmid', PARAM_INT);
    $DB->delete_records_select('block_page_module_access', " course = ? AND pageitemid = ? ", array($course->id, $cmid));

	// seems heavy to add all records back but there are not in the database necessarily.
    foreach($users as $user){
    	$cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $cmid;
        $cmrec->userid = $user->id;
        $cmrec->hidden = 0;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
    // print_string('updated');    
}
