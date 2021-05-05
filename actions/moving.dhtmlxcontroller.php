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
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/tree.class.php');

use \format\page\course_page;
use \format\page\tree;

if ($service == 'load') {
    header("Content-Type:text/xml\n\n");
    echo page_xml_tree($course);
    die;
} else if ($service == 'dhtmlxprocess') {
    header("Content-Type:text/xml\n\n");
    $dhtmlx_status = optional_param('!nativeeditor_status', '', PARAM_TEXT);
    $dhtmlx_id = optional_param('tr_id', '', PARAM_INT); // Id of page moving.
    $dhtmlx_order = optional_param('tr_order', '', PARAM_INT); // Sort order in new location.
    $dhtmlx_pid = optional_param('tr_pid', '', PARAM_INT); // Parent ID in new location.

    if ($dhtmlx_status == 'updated') {
        $tr_page = course_page::get($dhtmlx_id);
        if ($tr_page->parent != $dhtmlx_pid) {
            /*
             * I page is NOT comming from the same level, you MUST NOT
             * impact the order of the original sequence.
             */
            $tr_page->sortorder = $dhtmlx_order;
            $oldparent = $tr_page->parent; // Store old parent for gapfix.
            $oldsortorder = $tr_page->sortorder; // Store old parent for gapfix.
            tree::insert_page_sortorder($COURSE->id, $dhtmlx_pid, $dhtmlx_order);
            $tr_page->parent = $dhtmlx_pid;
            $tr_page->sortorder = $dhtmlx_order;
            $tr_page->save();

            // fill the gap in origin parent.
            tree::discard_page_sortorder($courseid, $oldparent, $oldsortorder);
        } else {
            tree::move_page_sortorder($COURSE->id, $dhtmlx_pid, $tr_page, $dhtmlx_order);
            // $tr_page->sortorder = tree::get_next_page_sortorder($COURSE->id, $dhtmlx_pid);
        }

        echo page_send_dhtmlx_answer($dhtmlx_status, $dhtmlx_id, $tr_page->id);
        die;
    }

    echo page_xml_tree($course);
    die;
}