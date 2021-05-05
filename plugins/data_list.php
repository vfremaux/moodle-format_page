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
 * Page Item Definition
 *
 * @author Valery Fremaux
 * @package format_page
 */
defined('MOODLE_INTERNAL') or die();

use \format\page\course_page;

/**
 * Add content to a block instance. This
 * method should fail gracefully.  Do not
 * call something like error()
 *
 * @param object $block Passed by reference: this is the block instance object
 *                      Course Module Record is $block->cm
 *                      Module Record is $block->module
 *                      Module Instance Record is $block->moduleinstance
 *                      Course Record is $block->course
 *
 * @return boolean If an error occurs, just return false and
 *                 optionally set error message to $block->content->text
 *                 Otherwise keep $block->content->text empty on errors
 **/
function data_list_set_instance($block) {
    global $CFG, $DB, $OUTPUT, $PAGE, $COURSE, $USER;

    include_once($CFG->dirroot.'/mod/data/lib.php');
    include_once($CFG->dirroot.'/mod/data/locallib.php');

    // Get an eventual behaviour manager.
    $manager = null;
    if (file_exists($CFG->dirroot.'/blocks/data_behaviour/xlib.php')) {
        include_once($CFG->dirroot.'/blocks/data_behaviour/xlib.php');
        $manager = get_block_data_behaviour_manager();
    }

    $cm = $block->cm;
    $data = $DB->get_record('data', array('id' => $cm->instance));
    $context = context_module::instance($cm->id);

    // Mark as viewed
    $completion = new completion_info($COURSE);
    $completion->set_module_viewed($cm);

    if ($data->csstemplate) {
        $PAGE->requires->css('/mod/data/css.php?d='.$data->id);
    }
    if ($data->jstemplate) {
        $PAGE->requires->js('/mod/data/js.php?d='.$data->id, true);
    }

    // Determine active user.
    $userid = $USER->id;
    $context = context_module::instance($cm->id);
    if ($manager && $manager->has_behaviour($data->id, 'seeonlymydata')) {
        if (has_capability('mod/data:approve', $context)) {
            $userid = optional_param('db_userid'.$data->id, $USER->id, PARAM_INT);
        }
    }

    ob_start();
    $canmanageentries = has_capability('mod/data:manageentries', $context);

    if (!$canmanageentries) {
        $timenow = time();
        if (!empty($data->timeavailablefrom) && $data->timeavailablefrom > $timenow) {
            echo $OUTPUT->notification(get_string('notopenyet', 'data', userdate($data->timeavailablefrom)));
            $showactivity = false;
        } else if (!empty($data->timeavailableto) && $timenow > $data->timeavailableto) {
            echo $OUTPUT->notification(get_string('expired', 'data', userdate($data->timeavailableto)));
            $showactivity = false;
        }
    }

    $emptyclass = (!preg_match('/[a-zA-Z0-9]/', strip_tags($data->intro))) ? 'empty' : '';
    echo $OUTPUT->box(format_module_intro('data', $data, $cm->id), "generalbox $emptyclass", 'intro');

    if ($manager && $manager->has_behaviour($data->id, 'seeonlymydata')) {
        $renderer = $PAGE->get_renderer('block_data_behaviour');
        if ($canmanageentries) {
            $baseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'dataid' => $data->id));
            echo $renderer->user_selector($data, $baseurl);
        }
    }

    $currentpage = course_page::get_current_page($COURSE->id);
    $fpage = optional_param('page', $currentpage->id, PARAM_INT); // Format page page.
    $sort = optional_param('sort', '', PARAM_TEXT);
    $order = optional_param('order', 'ASC', PARAM_TEXT);
    $page = optional_param('datapage', 0, PARAM_INT);
    $paging = optional_param('paging', null, PARAM_BOOL);
    if ($page == 0 && !isset($paging)) {
        $paging = false;
    } else {
        $paging = true;
    }

    $baseurl = $CFG->wwwroot.'/course/view.php';
    // Pass variable to allow determining whether or not we are paging through results.
    $baseurl .= 'paging='.$paging.'&page='.$fpage;
    $nowperpage = 15;

     $numentries = data_numentries($data);

    // Check the number of entries required against the number of entries already made (doesn't apply to teachers).
    /*
    if ($data->requiredentries > 0 && $numentries < $data->requiredentries && !$canmanageentries) {
        $data->entriesleft = $data->requiredentries - $numentries;
        $strentrieslefttoadd = get_string('entrieslefttoadd', 'data', $data);
        echo $OUTPUT->notification($strentrieslefttoadd);
        $requiredentries_allowed = false;
    }
    */

    $requiredentries_allowed = true;
    if ($data->entrieslefttoview = data_get_entries_left_to_view($data, $numentries, $canmanageentries)) {
        $strentrieslefttoaddtoview = get_string('entrieslefttoaddtoview', 'data', $data);
        echo $OUTPUT->notification($strentrieslefttoaddtoview);
        $requiredentries_allowed = false;
    }

    if ($manager && $manager->has_behaviour($data->id, 'seeonlymydata')) {
        // If never allowed to see entries from other people.
        $requiredentries_allowed = false;
    }

    $totalcount = $numentries; // Needs change.

    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    if (data_user_can_add_entry($data, $currentgroup, $groupmode, $context) && $USER->id == $userid) { // took out participation list here!
        $addstring = empty($editentry) ? get_string('add', 'data') : get_string('editentry', 'data');
        $buttonurl = new moodle_url('/mod/data/edit.php', array('d' => $data->id, 'return' => 'coursepage', 'page' => $fpage, 'frompage' => 1));

        echo $OUTPUT->box_start('data-add-entry');
        echo $OUTPUT->single_button($buttonurl, $addstring);
        echo $OUTPUT->box_end();
    }

    echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl, 'datapage');

    if (empty($data->listtemplate)) {
        echo $OUTPUT->notification(get_string('nolisttemplate', 'data'));
        data_generate_default_template($data, 'listtemplate', 0, false, false);
    }

    $options = array('requiredentries_allowed' => $requiredentries_allowed);
    $records = data_list_get_records($data, $cm, $sort, $order, $page, $options, $manager, $userid);

    $search = false; // No search in embeded view.

    echo $data->listtemplateheader;

    ob_start();
    data_print_template('listtemplate', $records, $data, $search, $page);
    $output = ob_get_contents();
    ob_end_clean();

    // Add the return attribute on all links.
    $output = str_replace('mod/data/view.php?', 'mod/data/view.php?return=coursepage&page='.$fpage.'&', $output);
    $output = str_replace('mod/data/edit.php?', 'mod/data/edit.php?return=coursepage&page='.$fpage.'&', $output);
    echo $output;

    echo $data->listtemplatefooter;

    echo $OUTPUT->paging_bar($totalcount, $page, $nowperpage, $baseurl, 'datapage');

    $block->content->text = ob_get_contents();
    ob_end_clean();

    return true;
}

function data_list_get_records($data, $cm, $sort, $order, $page, $options, $manager, $userid) {
    global $USER, $DB;

    // Init some variables to be used by advanced search
    $advsearchselect = '';
    $advwhere        = '';
    $advtables       = '';
    $advparams       = array();
    // This is used for the initial reduction of advanced search results with required entries.
    $entrysql        = '';
    $namefields = get_all_user_name_fields(true, 'u');

    // Initialise the first group of params for advanced searches.
    $initialparams   = array();

    $context = context_module::instance($cm->id);
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    $canmanageentries = has_capability('mod/data:manageentries', $context);
    if ($currentgroup == 0 && $groupmode == 1 && !$canmanageentries) {
        $canviewallrecords = false;
    } else {
        $canviewallrecords = true;
    }

    // check the group conditions.
    if ($currentgroup) {
        $groupselect = " AND (r.groupid = :currentgroup OR r.groupid = 0)";
        $params['currentgroup'] = $currentgroup;
        $initialparams['currentgroup'] = $params['currentgroup'];
    } else {
        if ($canviewallrecords) {
            $groupselect = ' ';
        } else {
            // If separate groups are enabled and the user isn't in a group or
            // a teacher, manager, admin etc, then just show them entries for 'All participants'.
            $groupselect = " AND r.groupid = 0";
        }
    }

    // Check data approval.
    $approveselect = '';
    if ($data->approval) {
        if (isloggedin()) {

            if ($manager && $manager->has_behaviour($data->id, 'seeonlymydata')) {
                $approveselect = ' AND r.userid = :myid1 ';
            } else {
                $approveselect = ' AND (r.approved = 1 OR r.userid = :myid1) ';
            }

            $params['myid1'] = $userid;

            $initialparams['myid1'] = $params['myid1'];
        } else {
            $approveselect = ' AND r.approved = 1 ';
        }
    } else {
        // Approval is off. But single user view is required.
        if ($manager && $manager->has_behaviour($data->id, 'seeonlymydata')) {
            $approveselect = ' AND r.userid = :myid1 ';
            $params['myid1'] = $userid;
            $initialparams['myid1'] = $params['myid1'];
        }
    }

    // Find the field we are sorting on.
    if ($sort <= 0 or !$sortfield = data_get_field_from_id($sort, $data)) {

        switch ($sort) {
            case DATA_LASTNAME:
                $ordering = "u.lastname $order, u.firstname $order";
                break;
            case DATA_FIRSTNAME:
                $ordering = "u.firstname $order, u.lastname $order";
                break;
            case DATA_APPROVED:
                $ordering = "r.approved $order, r.timecreated $order";
                break;
            case DATA_TIMEMODIFIED:
                $ordering = "r.timemodified $order";
                break;
            case DATA_TIMEADDED:
            default:
                $sort     = 0;
                $ordering = "r.timecreated $order";
        }

        $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields;
        $count = ' COUNT(DISTINCT c.recordid) ';
        $tables = '{data_content} c,{data_records} r, {user} u ';
        $where = 'WHERE c.recordid = r.id
                     AND r.dataid = :dataid
                     AND r.userid = u.id ';
        $params['dataid'] = $data->id;
        $sortorder = ' ORDER BY '.$ordering.', r.id ASC ';
        $searchselect = '';

        // If requiredentries is not reached, only show current user's entries.
        if (empty($options['requiredentries_allowed'])) {
            $where .= ' AND u.id = :myid2 ';
            $entrysql = ' AND r.userid = :myid3 ';
            $params['myid2'] = $USER->id;
            $initialparams['myid3'] = $params['myid2'];
        }

        $searchselect = ' ';

    } else {

        $sortcontent = $DB->sql_compare_text('c.' . $sortfield->get_sort_field());
        $sortcontentfull = $sortfield->get_sort_sql($sortcontent);

        $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ',
                ' . $sortcontentfull . ' AS sortorder ';
        $count = ' COUNT(DISTINCT c.recordid) ';
        $tables = '{data_content} c, {data_records} r, {user} u ';
        $where = 'WHERE c.recordid = r.id
                     AND r.dataid = :dataid
                     AND r.userid = u.id ';
        $where .= 'AND c.fieldid = :sort';

        $params['dataid'] = $data->id;
        $params['sort'] = $sort;
        $sortorder = ' ORDER BY sortorder '.$order.' , r.id ASC ';
        $searchselect = '';

        // If requiredentries is not reached, only show current user's entries
        if (empty($options['requiredentries_allowed'])) {
            $where .= ' AND u.id = :myid2';
            $entrysql = ' AND r.userid = :myid3';
            $params['myid2'] = $USER->id;
            $initialparams['myid3'] = $params['myid2'];
        }
        $i = 0;
        $searchselect = ' ';
    }

    // To actually fetch the records.

    $fromsql    = "FROM $tables $advtables $where $advwhere $groupselect $approveselect $searchselect $advsearchselect";
    $allparams  = array_merge($params, $advparams);

    // Provide initial sql statements and parameters to reduce the number of total records.
    $initialselect = $groupselect . $approveselect . $entrysql;

    $recordids = data_get_all_recordids($data->id, $initialselect, $initialparams);
    // $newrecordids = data_get_advance_search_ids($recordids, $search_array, $data->id);
    $totalcount = count($recordids);
    $selectdata = $where . $groupselect . $approveselect;

    $sqlselect  = "SELECT $what $fromsql $sortorder";

    // Work out the paging numbers and counts.
    $maxcount = $totalcount;
    $nowperpage = 15;

    // Get the actual records.
    $records = $DB->get_records_sql($sqlselect, $allparams, $page * $nowperpage, $nowperpage);

    return $records;
}

