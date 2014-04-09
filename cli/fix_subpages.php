<?php

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

if (!empty($argv[1])){
	
	$CLI_VMOODLE_PRECHECK = true;
	include '../../../../config.php'; // do config untill setup start.

	if (empty($CFG->dirroot)){
		echo("dirroot not defined in config");
	}
	
	if (!is_dir($CFG->dirroot.'/blocks/vmoodle')){
		echo("VMoodle not installed");
	}
	
	if (isset($argv[1])){
		echo('Placing argument '.$argv[1]."\n");
		define('CLI_VMOODLE_OVERRIDE', $argv[1]);
	}
}

include '../../../../config.php';
require_once $CFG->dirroot.'/lib/clilib.php';
require_once 'fixlib.php';

page_format_remap_subpages();

