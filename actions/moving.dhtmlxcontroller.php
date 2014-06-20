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

if ($service == 'load') {
    header("Content-Type:text/xml\n\n");
    echo page_xml_tree($course);
    die;
} elseif ($service == 'dhtmlxprocess') {
    header("Content-Type:text/xml\n\n");
    $dhtmlx_status = optional_param('!nativeeditor_status', '', PARAM_TEXT);
    $dhtmlx_id = optional_param('tr_id', '', PARAM_INT);
    $dhtmlx_order = optional_param('tr_order', '', PARAM_INT);
    $dhtmlx_pid = optional_param('tr_pid', '', PARAM_INT);

    if ($dhtmlx_status == 'updated') {
        $tr_page = course_page::get($dhtmlx_id);
        $tr_page->parent = $dhtmlx_pid;
        $tr_page->sortorder = course_page::prepare_page_location($tr_page->parent, $tr_page->sortorder, $dhtmlx_order); // get the position we exchange with
        $tr_page->save(); // pre save

        course_page::fix_tree_level($tr_page->parent);

        $tr_page->delete_section();
        $tr_page->insert_in_sections();
        $tr_page->save(); // post save

        echo page_send_dhtmlx_answer($dhtmlx_status, $dhtmlx_id, $tr_page->id);
        die;
    }

    echo page_xml_tree($course);
    die;
}