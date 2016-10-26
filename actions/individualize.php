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
 * Page reorganisation service
 *
 * @package format_page
 * @author Jeff Graham, Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

$PAGE->requires->js('/course/format/page/js/dhtmlxCalendar/codebase/dhtmlxcalendar.js', true);
$PAGE->requires->js('/course/format/page/js/individualization.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxCalendar/codebase/dhtmlxcommon.js');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);    
require_capability('moodle/course:manageactivities', $context);

// Set course display.

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    $page = course_page::get($pageid);
} else {
    if (!$page = course_page::get_current_page($course->id)) {
        print_error('errornopage', 'format_page');
    }
    $pageid = $page->id;  
}

$url = new moodle_url('/course/format/page/actions/individualize.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');
$PAGE->requires->js('/course/format/page/js/individualization.js');

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Start page content.

echo $OUTPUT->header();
$dhtmlcssurl = "{$CFG->wwwroot}/course/format/page/js/dhtmlxCalendar/codebase/dhtmlxcalendar.css";
echo '<link rel="stylesheet" type="text/css" href="'.$dhtmlcssurl.'" />';
$dhtmlskinurl = "{$CFG->wwwroot}/course/format/page/js/dhtmlxCalendar/codebase/skins/dhtmlxcalendar_dhx_skyblue.css";
echo '<link rel="stylesheet" type="text/css" href="'.$dhtmlskinurl.'"></link>';

$blockconfig = get_config('block_page_module');
$pagesize = (@$blockconfig->individualizewithtimes) ? 3 : 5;
$from = optional_param('from', 0, PARAM_INT);
$modtype = optional_param('modtype', '', PARAM_INT);
$usersearch = optional_param('usersearch', '', PARAM_TEXT);
$what = optional_param('what', '', PARAM_TEXT);

$mods = page_get_page_coursemodules($pageid);

/*
 * Right now storing modules in a section corresponding to the current
 * page - probably should all be section 0 though
 */
if ($course->id == SITEID) {
    $section = 1; // Front page only has section 1 - so use 1 as default.
} else if (isset($page->id)) {
    $section = $page->id;
} else {
    $section = 0;
}

// Select active users to process.

if (empty($usersearch)) {
    // Search by capability.
    $groupid = groups_get_course_group($course, true);

    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.email,u.emailstop';
    $users = get_enrolled_users($context, '', $groupid, $fields, $orderby = '', $from, $pagesize);
    $allusers = get_enrolled_users($context, '', 0, 'u.id,'.get_all_user_name_fields(true, 'u'), '', 0, 0);
} else {
    // First search by name.
    $select = " firstname LIKE ? OR lastname LIKE ? ";
    $params = array("%$usersearch%", "%$usersearch%");
    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',u.picture,u.email,u.emailstop';
    if ($users = $DB->get_records_select('user', $select, $params, $fields)) {
        // Need search users having the capability AND matching firstname or last name pattern.
        foreach ($users as $key => $user) {
            if (!has_capability('moodle/course:view', $context, $key)) {
                unset($users[$key]);
            }
        }
    }
}

// MVC Implementation.
// All modules are already known and processd users too.

if (!empty($what)) {
    include($CFG->dirroot.'/course/format/page/action/individualize.controller.php');
}

if (!empty($errors)) {
    echo $OUTPUT->box_start('errorbox');
    echo implode('<br/>', $errors);
    echo $OUTPUT->box_end();
}

echo $renderer->print_tabs('individualize', true);

echo $OUTPUT->box_start();
page_print_moduletype_filter($modtype, $mods, $url."&usersearch={$usersearch}");
page_print_user_filter($url."&modtype={$modtype}");

echo $OUTPUT->box_end();

if (empty($usersearch)) {
    page_print_pager($from, $pagesize, count($allusers), $url);
}

echo $OUTPUT->box_start();

if (!empty($mods)) {
    echo '<form name="individualize_form" method="post" action="" >';
    echo '<input type="hidden" name="id" value="'.$course->id.'" >';
    echo '<input type="hidden" name="what" value="update" >';
    echo '<table width="100%" class="individualize generaltable">';
    echo '<tr>';
    echo '<td width="20%"></td>';
    $removeallstr = get_string('removeall', 'format_page');
    $addallstr = get_string('addall', 'format_page');
    $removeforallstr = get_string('removeforall', 'format_page');
    $addtoallstr = get_string('addtoall', 'format_page');
    foreach($users as $user){
        echo '<td class="individualize col1">';
        echo fullname($user);
        echo '<br/>';
        echo '<a href="'.$url.'&what=removeall&userid='.$user->id.'&modtype='.$modtype.'">'.$removeallstr.'</a>';
        echo ' / <a href="'.$url.'&what=addall&userid='.$user->id.'&modtype='.$modtype.'">'.$addallstr.'</a>';
        echo '</td>';
    }
    echo '</tr>';
    $span = 1;
    foreach ($mods as $mod) {
        if (!empty($modtype) && $mod->module != $modtype) {
            continue;
        }
        if (!$DB->get_records_select('format_page_items', " cmid = $mod->id")) {
            // Forget modules who are not viewed in page.
            continue;
        }
        $modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance));
        $modinstance->modname = $mod->modname;
        $modinstance->cmid = $mod->id;
        if (preg_match('/label$/', $mod->modname)) {
            $modinstance->name = get_string('modulename', $mod->modname).' '.$modinstance->id;
        }
        echo '<tr valign="top">';
        echo '<td>';
        echo '<span class="modtype">'.$mod->modfullname.':</span><br/>';
        echo "$modinstance->name";
        echo '<br/>';
        echo '<a href="'.$url.'&what=removeforall&cmid='.$mod->id.'&modtype='.$modtype.'">'.$removeforallstr.'</a>';
        echo ' / <a href="'.$url.'&what=addtoall&cmid='.$mod->id.'&modtype='.$modtype.'">'.$addtoallstr.'</a>';
        echo '</td>';
        $span = 1;
        // Calculate absolute max time for all bars.
        $maxabsolutetime = page_get_max_access_event_time($course);
        foreach ($users as $user) {
            echo '<td>';
            $params = array('userid' => $user->id, 'pageitemid' => $mod->id);
            if (!$record = $DB->get_record('block_page_module_access', $params)) {
                $record = new StdClass;
                $record->hidden = 0;
                $record->revealtime = 0;
                $record->hidetime = 0;
            }
            $checkedstr = (!$record->hidden) ? 'checked="checked"' : '';
            $oncheckedstr = ($record->revealtime) ? 'checked="checked"' : '';
            $offcheckedstr = ($record->hidetime) ? 'checked="checked"' : '';
            echo '<input type="checkbox" name="visible_cm_'.$mod->id.'_'.$user->id.'" value="1" '.$checkedstr.' >';
            echo '<input type="hidden" name="cm[]" value= "'.$mod->id.'_'.$user->id.'" /><br/>'; // For negative logic GUI.
            if (@$blockconfig->individualizewithtimes) {
                page_print_timebar($course, $record, $maxabsolutetime);

                $revealdate = ($record->revealtime) ? date('Y-m-d', $record->revealtime) : '';

                echo '<div class="onoffselectors">';
                echo '<img src="'.$OUTPUT->pix_url('/t/hide').'" />';
                echo '<input type="checkbox"
                             name="on_enable_'.$mod->id.'_'.$user->id.'"
                             value="1"
                             '.$oncheckedstr.'
                             onclick="change_selector_state(this, \''.$mod->id.'_'.$user->id.'\', \'on\');" />';
                echo '<input type="text"
                             size="10"
                             id="on_date_'.$mod->id.'_'.$user->id.'"
                             name="on_date_'.$mod->id.'_'.$user->id.'"
                             value="'.$revealdate.'" />';
                echo '<script type="text/javascript">';
                echo 'var on_'.$mod->id.'_'.$user->id.' = new dhtmlXCalendarObject(["on_date_'.$mod->id.'_'.$user->id.'"]);';
                echo '</script>';

                echo html_writer::select_time('hours', "on_hour_{$mod->id}_{$user->id}",  $record->revealtime, 1);
                echo html_writer::select_time('minutes', "on_min_{$mod->id}_{$user->id}",  $record->revealtime, 5);

                if (empty($revealdate)) {
                    echo '<script type="text/javascript" />';
                    echo "set_disabled('{$mod->id}_{$user->id}', 'on');";
                    echo '</script>';
                }

                echo '<br/>';

                $hidedate = ($record->hidetime) ? date('Y-m-d', $record->hidetime) : '';

                echo '<img src="'.$OUTPUT->pix_url('/t/show').'" /> ';
                echo '<input type="checkbox"
                             name="off_enable_'.$mod->id.'_'.$user->id.'"
                             value="1"
                             '.$offcheckedstr.'
                             onclick="change_selector_state(this, \''.$mod->id.'_'.$user->id.'\', \'off\');" />';
                echo '<input type="text"
                             size="10"
                             id="off_date_'.$mod->id.'_'.$user->id.'"
                             name="off_date_'.$mod->id.'_'.$user->id.'"
                             value="'.$hidedate.'" />';
                echo '<script type="text/javascript">';
                echo "var off_{$mod->id}_{$user->id} = new dhtmlXCalendarObject([\"off_date_{$mod->id}_{$user->id}\"]);";
                echo '</script>';

                echo html_writer::select_time('hours', "off_hour_{$mod->id}_{$user->id}",  $record->revealtime, 1);
                echo html_writer::select_time('minutes', "off_min_{$mod->id}_{$user->id}",  $record->revealtime, 5);

                if (empty($hidedate)) {
                    echo '<script type="text/javascript" />';
                    echo 'set_disabled(\''.$mod->id.'_'.$user->id.'\', \'off\');';
                    echo '</script>';
                }
            }
            echo '</div>';
            echo '</td>';
            $span++;
        }
        echo '</tr>';
    }
    echo '<tr>';
    echo '<td colspan="'.$span.'" align="center" >';
    $savestr = get_string('update');
    echo '<p><input type="submit" name="go_btn" value="'.$savestr.'" /></p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
} else {
    echo $OUTPUT->box(get_string('noactivitiesfound', 'format_page'));
}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
