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
defined('MOODLE_INTERNAL') || die();

// Get all modules from the type we can add.
$allowedmods = array();
foreach ($mods as $mod) {
    if ($mod->module == $modtype || empty($modtype)) {
        $allowedmods[] = $mod->id;
    }
}
$modlist = implode("','", $allowedmods);

// Update individualization switches *************************************************.

if ($what == 'update') {
    $cms = required_param_array('cm', PARAM_RAW);
    $DB->delete_records('block_page_module_access', array('course' => $course->id));
    foreach ($cms as $cm) {
        list($cmid, $userid) = explode('_', $cm);
        if (!optional_param("visible_cm_{$cmid}_{$userid}", '', PARAM_INT)) {
            $cmrec = new StdClass;
            $cmrec->course = $course->id;
            $cmrec->pageitemid = $cmid;
            $cmrec->userid = $userid;
            $cmrec->hidden = 1;
            $cmrec->revealtime = page_get_pageitem_changetime('on', $userid, $cmid);
            if ($cmrec->revealtime != 0 && $cmrec->revealtime < time()) {
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
            if ($cmrec->revealtime != 0 && $cmrec->revealtime < time()) {
                $cmrec->revealtime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
            $cmrec->hidetime = page_get_pageitem_changetime('off', $userid, $cmid);
            if ($cmrec->hidetime != 0 && $cmrec->hidetime < time()) {
                $cmrec->hidetime = 0;
                $errors[] = get_string('eventsinthepast', 'format_page');
            }
        }
        $DB->insert_record('block_page_module_access', $cmrec);
    }
}

// Remove all ****************************************************************.

if ($what == 'removeall') {
    $userid = required_param('userid', PARAM_INT);
    $select = " course = ? AND userid = ? AND pageitemid IN ('$modlist') ";
    $DB->delete_records_select('block_page_module_access', $select, array($course->id, $userid));
    foreach ($mods as $mod) {
        if (!in_array($mod->id, $allowedmods)) {
            continue;
        }
        $cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $mod->id;
        $cmrec->userid = $userid;
        $cmrec->hidden = 1;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
}

// Add all *******************************************************************.
 
if ($what == 'addall') {
    $userid = required_param('userid', PARAM_INT);

    // Delete only hide switches from this module !
    $select = " course = ? AND userid = ? AND pageitemid IN ('$modlist') ";
    $DB->delete_records_select('block_page_module_access', $select, array($course->id, $userid));
}

// Remove course module to all **********************************************************.

if ($what == 'removeforall') {
    $cmid = required_param('cmid', PARAM_INT);
    $select = " course = ? AND pageitemid = ? ";
    $DB->delete_records_select('block_page_module_access', $select, array($course->id, $cmid));

    foreach ($users as $user) {
        $cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $cmid;
        $cmrec->userid = $user->id;
        $cmrec->hidden = 1;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
}

// Add coursemodule to all *****************************************************************.

if ($what == 'addtoall') {
    $cmid = required_param('cmid', PARAM_INT);
    $select = " course = ? AND pageitemid = ? ";
    $DB->delete_records_select('block_page_module_access', $select, array($course->id, $cmid));

    // Seems heavy to add all records back but there are not in the database necessarily.
    foreach ($users as $user) {
        $cmrec = new StdClass;
        $cmrec->course = $course->id;
        $cmrec->pageitemid = $cmid;
        $cmrec->userid = $user->id;
        $cmrec->hidden = 0;
        $DB->insert_record('block_page_module_access', $cmrec);
    }
}
