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
 * Search and replace strings throughout all texts in the whole database.
 *
 * @package    tool_replace
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

$help =
    "Cleanup courses from non published activities.

Options:
    -c, --courses      Course id list.
    -H, --host         Host to play on.
    -h, --help     Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php course/format/page/cli/clean_courses.php [ --courses=3,4,5,6 ] [ --host=<vmoodlehost> ]
";

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
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

$courses = array();
if (!empty($options['courses'])) {
    $courseids = explode(',', $options['courses']);
    if (!empty($courseids)) {
        foreach ($courseids as $cid) {
            if ($course = $DB->get_record('course', array('id' => $cid))) {
                $courses[$cid] = $course;
            } else {
                mtrace("Course $cid unkown. Skipping");
            }
        }
    }
} else {
    mtrace('Cleaning all courses');
    $courses = $DB->get_records('course', array('format' => 'page'));
}

foreach ($courses as $cid => $course) {

    // Skip non paged formats if any in the list.
    if ($course->format != 'page') {
        mtrace("Course $cid not paged. Skipping");
        continue;
    }

    mtrace('Cleaning course '.$course->shortname);

    // Delete unused modules.
    $deleted = array();
    if ($unuseds = page_get_unused_course_modules($cid)) {
        foreach ($unuseds as $unused) {
            // Check if not used by a direct page embedding.
            if ($DB->record_exists('format_page', array('courseid' => $COURSE->id, 'cmid' => $unused->id))) {
                continue; // Do not delete, they are used.
            }
            @$deleted[$unused->name]++;
            $modcontext = context_module::instance($unused->id);
            if ($DB->record_exists('course_modules', array('id' => $unused->id))) {
                try {
                    /*
                     * First remove from all sections. this will make standard code fail
                     * but ensures we have no course module in the way somewhere
                     */
                    if ($sections = $DB->get_records('course_sections', array('course' => $course->id))) {
                        foreach ($sections as $s) {
                            // Delete wherever it may be.
                            delete_mod_from_section($unused->id, $s->id);
                        }
                    }

                    // Do all other deletion tasks.
                    course_delete_module($unused->id);
                } catch (Exception $e) {
                    // We need finish the job after failing to delete from section.

                    // Trigger event for course module delete action.
                    $event = \core\event\course_module_deleted::create(array(
                        'courseid' => $unused->course,
                        'context'  => $modcontext,
                        'objectid' => $unused->id,
                        'other'    => array(
                            'modulename' => $unused->name,
                            'instanceid'   => $unused->instance,
                        )
                    ));
                    unset($unused->name);
                    $event->add_record_snapshot('course_modules', $unused);
                    $event->trigger();
                }
            }
        }
    }

    rebuild_course_cache($course->id, true);

    if (!empty($deleted)) {
        foreach (array_keys($deleted) as $modulename) {
            if (!empty($deleted[$modulename])) {
                $a = new StdClass();
                $a->name = get_string('modulename', $modulename);
                $a->value = $deleted[$modulename];
                mtrace(get_string('cleanupreport', 'format_page', $a));
            }
        }
    }
}

cli_heading(get_string('success'));
exit(0);
