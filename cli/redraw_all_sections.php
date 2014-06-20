<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a tecnhical tool for fing inconsistent information
 *
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

if (!empty($argv[1])) {

    $CLI_VMOODLE_PRECHECK = true;
    include '../../../../config.php'; // Do config untill setup start.

    if (empty($CFG->dirroot)) {
        echo("dirroot not defined in config");
    }

    if (!is_dir($CFG->dirroot.'/blocks/vmoodle')) {
        echo("VMoodle not installed");
    }

    if (isset($argv[1])) {
        echo('Placing argument '.$argv[1]."\n");
        define('CLI_VMOODLE_OVERRIDE', $argv[1]);
    }
}

include '../../../../config.php';
require_once $CFG->dirroot.'/lib/clilib.php';
require_once 'fixlib.php';

echo "Start processing... \n";

if ($pageformatedcourses = $DB->get_records('course', array('format' => 'page'))) {
    foreach ($pageformatedcourses as $course) {
        echo "Processing course $course->id / $course->fullname \n";
        page_format_redraw_sections($course);
    }
}

echo "done.\n";
