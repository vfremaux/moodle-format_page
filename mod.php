<?php

/**
* @package format_page
* @author Valery Fremaux (valery.fremaux@gmail.com)
*
* This script is a straight redirector to /course/mod.php
* We just need it to eventually store in session the mod_create activator 
* for direct insertion in current course page.
*/

include '../../../config.php';

require_login();

$courseid = required_param('id', PARAM_INT);
$section = required_param('section', PARAM_INT); // contains pageid
$sesskey = required_param('sesskey', PARAM_RAW);
$add = required_param('add', PARAM_TEXT);

if ($insertinpage = required_param('insertinpage', PARAM_TEXT)){
	$SESSION->format_page_cm_insertion_page = $section;
}

$urlbase = $CFG->wwwroot."/course/mod.php?id={$courseid}&section={$section}&sesskey={$sesskey}&add={$add}";
redirect($urlbase);

?>