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
* prints the current "page" related navigation in foreign
* situations. (modules or blocks customisation)
* The module must be customized to print this navigation, and
* also store the current pageid (coming by an "aspage" parameter)
* in session. 
*
*/
function page_print_page_format_navigation($cm = null, $backtocourse = false) {
    global $CFG, $COURSE, $USER, $SESSION, $OUTPUT;

    if ($COURSE->format != 'page') return;

    require_once($CFG->dirroot.'/course/format/page/lib.php');
    require_once($CFG->dirroot.'/course/format/page/page.class.php');
    require_once($CFG->dirroot.'/course/format/page/renderers.php');

    $pageid = @$SESSION->formatpageid[$COURSE->id];

    $aspageid = optional_param('aspage', 0, PARAM_INT);

    if ($aspageid) {
        $pageid = $aspageid;
        // As we are in a page override, we are already in course sequence.
        $backtocourse = false;
    }

    if (!$pageid) {
        $defaultpage = course_page::get_default_page($COURSE->id);
        $pageid = $defaultpage->id;
    }

    $page = course_page::get($pageid);
    $renderer = new format_page_renderer($page);

    $navbuttons = '
        <div id="page-region-bottom" class="page-region">
            <div class="region-content">
                <div class="page-nav-prev">
                '.$renderer->previous_button().'
                </div>
                <div class="page-nav-back">';
    if ($backtocourse) {
        $navbuttons .= $OUTPUT->single_button(new moodle_url('/course/view.php', array('id' => $COURSE->id, 'page' => $pageid)), get_string('backtocourse', 'format_page'));
    }
    $navbuttons .= '</div>
                <div class="page-nav-next">
                '.$renderer->next_button().'
                </div>
            </div>
        </div>
    ';

    echo $navbuttons;
}

/**
*
* @return true if embedded activity as page
*/
function page_save_in_session() {
    global $SESSION, $COURSE;

    $aspage = optional_param('aspage', 0, PARAM_INT);
    if ($aspage) {
        // Store page id to be able to go back to following flexipage at the end of the activity.
        $SESSION->formatpageid[$COURSE->id] = $aspage;
        return true;
    } else {
        if ($currentpage = optional_param('page', 0, PARAM_INT)) {
            $SESSION->formatpageid[$COURSE->id] = $currentpage;
        }
        return false;
    }
}

/**
* Get all course modules from that page
*
*/
function page_get_page_coursemodules($pageid) {
    global $DB;

    $pageitems = $DB->get_records_select_menu('format_page_items', " pageid = ? && cmid != 0 ", array($pageid),'sortorder', 'id, cmid');
    $cms = array();
    if ($pageitems) {
        foreach ($pageitems as $piid => $cmid) {
            $cm = $DB->get_record('course_modules', array('id' => $cmid));
            $module = $DB->get_record('modules', array('id' => $cm->module));
            $cm->modname = $module->name;
            $cm->modfullname = get_string('pluginname', $module->name);
            if (!$cm->visible) continue;
            $cms[$cmid] = $cm;
        }
    }
    return $cms;
}
