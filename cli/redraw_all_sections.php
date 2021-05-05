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
global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.
echo "
#
# Starting redraw_all_sections tool
# Component : format_page
#
#
";
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

list($options, $unrecognized) = cli_get_params(
    array(
        'host' => false,
        'courses' => false,
        'help'    => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
        'c' => 'courses',
    )
);

if ($options['help']) {
    $help =
        "Cleanup courses from non published activities.

    Options:
        -c, --courses      Course id list.
        -H, --host         Host to play on.
        -h, --help     Print out this help.

    Example:
    \$ sudo -u www-data /usr/bin/php course/format/page/cli/clean_courses.php [ --courses=3,4,5,6 ] [ --host=<vmoodlehost> ]
    ";

    echo $help;
    exit(0);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");
require_once($CFG->dirroot.'/course/format/page/cli/fixlib.php');

echo("Start processing... \n");

if (!empty($options['courses'])) {
    $courselist = explode(',', $options['courses']);
    list($insql, $inparams) = $DB->get_in_or_equal($courselist);
    $select = " format = 'page' AND id $insql ";
    $pageformatedcourses = $DB->get_records_select('course', $select, $inparams);
} else {
    $pageformatedcourses = $DB->get_records('course', array('format' => 'page'));
}

if ($pageformatedcourses) {
    foreach ($pageformatedcourses as $course) {
        echo("Processing course $course->id / $course->fullname \n");
        page_format_redraw_sections($course, true);
        echo "\n#\n#\n#\n";
    }
} else {
    echo "No page formatted courses found in id list {$options['courses']}\n";
}

echo "done.\n";
