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

list($options, $unrecognized) = cli_get_params(
    array(
        'host' => false,
        'help'    => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
    )
);

$help =
    "Cleanup bad positions in course page format.

Options:
    -H, --host         Host to play on.
    -h, --help     Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php course/format/page/cli/fix_multiple_block_positions.php [ --host=<vmoodlehost> ]
";

if (!empty($options['help'])) {
    echo $help;
    die("\n");
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

$sql = "
    SELECT
        bp.blockinstanceid,
        count(*) as num
    FROM
        {block_positions} bp,
        {block_instances} bi
    WHERE
        bp.blockinstanceid = bi.id AND
        bp.subpage LIKE 'page-%' AND
        bp.pagetype = 'course-view-page' AND
        bi.blockname != 'navigation'
    GROUP BY
        bp.blockinstanceid
    HAVING
        num > 1
    ";

// Get all bad block positions
$badpageblockspos = $DB->get_records_sql($sql, []);

$deletions = 0;

if (!empty($badpageblockspos)) {
    foreach ($badpageblockspos as $pos) {
        // Check the effectif page_item that has this block.
        $fpi = $DB->get_record('format_page_items', ['blockinstance' => $pos->blockinstanceid]);
        if ($fpi) {
            $posinstances = $DB->get_records('block_positions', ['blockinstanceid' => $fpi->blockinstance]);
            if (!empty($posinstances)) {
                foreach ($posinstances as $posinstance) {
                    if ($posinstance->subpage != 'page-'.$fpi->pageid) {
                        $deletions++;
                        $DB->delete_records('block_positions', ['id' => $posinstance->id]);
                    }
                }
            }
        }
    }
}

echo 'Bad positionned blocks: '.count($badpageblockspos)."\n";
echo 'Deletions: '.$deletions."\n";
echo "Done.\n";