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
 * Activity management
 *
 * @author Jeff Graham, Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com) for Moodle 2
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Page reorganisation service
 * 
 * @package format_page
 * @category format
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);
$action = optional_param('what', '', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$url = new moodle_url('/course/format/page/actions/activities.php', array('id' => $course->id));
$PAGE->set_url($url); // Defined here to avoid notices on errors etc
$PAGE->requires->js('/course/format/page/js/actions.js');

// Security.
require_login($course);

$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);
require_capability('moodle/course:manageactivities', $context);

// Activate controller after security.

if (!empty($action)) {
    include($CFG->dirroot.'/course/format/page/actions/activities.controller.php');
}

/// Set course display
if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    $page = course_page::get($pageid);
} else {
    if (!$page = course_page::get_current_page($course->id)) {
        print_error('errornopage', 'format_page');
    }
}

$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Start page content.

echo $OUTPUT->header();

// Right now storing modules in a section corresponding to the current page.
// Probably should all be section 0 though.
if ($course->id == SITEID) {
    $section = 1; // Front page only has section 1 - so use 1 as default
} else if (isset($page->id)) {
    $section = $page->id;
} else {
    $section = 0;
}

rebuild_course_cache($course->id, true);

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs('activities', true);
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('page-block-region bootstrap block-region', 'region-main');
echo $OUTPUT->box_start('boxwidthwide boxaligncenter pageeditingtable', 'editing-table');

$modnames = get_module_types_names();
echo $renderer->course_section_add_cm_control($COURSE, 0, 0);
echo get_string('search').' : <input type="text" name="cmfilter" onchange="reload_activity_list(\''.$CFG->wwwroot.'\', \''.$COURSE->id.'\',\''.$pageid.'\', this)" />';
// 2.5 Change : print_section_add_menus($course, $section, $modnames);

echo $OUTPUT->box_start('', 'page-mod-list');

$modinfo = get_fast_modinfo($course);

$mods = $modinfo->get_cms();

if (!empty($mods)) {
    $str = new stdClass;
    $str->delete = get_string('delete');
    $str->update = get_string('update');
    $str->locate = get_string('locate', 'format_page');
    $path = $CFG->wwwroot.'/course';
    $sortedmods  = array();

    global $DB;

    foreach($mods as $mod) {
        if (!$modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance))) continue;
        $modinstance->modname = $mod->modname;
        $modinstance->cmid = $mod->id;

        if (isset($modinstance->type)) {
            if (empty($sortedmods[$mod->modname.':'.$modinstance->type])) {
                $sortedmods[$mod->modname.':'.$modinstance->type] = array();
            }
            $sortedmods[$mod->modname.':'.$modinstance->type][$mod->id] = $mod;
        } else {
            if (empty($sortedmods[$mod->modname])) {
                $sortedmods[$mod->modname] = array();
            }
            $sortedmods[$mod->modname][$mod->id] = $mod;
        }
    }

    ksort($sortedmods);
    $last    = ''; // Keeps track of modules.
    $lastsub = ''; // Keeps track of module sub-types.

    // Create an object sorting function.
    $function = create_function('$a, $b', 'return strnatcmp(get_string(\'modulename\', $a->modname), get_string(\'modulename\', $b->modname));');

    foreach ($sortedmods as $modname => $mods) {

        uasort($mods, $function);

        if (strpos($modname, ':')) {
            $parts = explode(':', $modname);
            $modname = $parts[0];
            $subname = $parts[1];
        } else {
            $subname = '';
        }

        if ($last != $modname) {
            $modulelink = new moodle_url('/mod/'.$modname.'/index.php', array('id' => $course->id));
            echo '<h2><a href="'.$modulelink.'">'.get_string('modulename', $modname).'</a></h1>';
            $last = $modname;
            $lastsub = '';
        }

        if ($lastsub != $subname) {
            $strtype = @get_string($subname, $modname);
            if (strpos($strtype, '[') !== false) {
                $strtype = get_string($modname.':'.$subname, 'format_page');
            }
            print '&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.$strtype.'</strong><br />';
            $lastsub = $subname;
        }

        print '<p align="right">';
        $modulestr = get_string('module', 'format_page');
        $usesstr = get_string('occurrences', 'format_page');
        $commandstr = get_string('commands', 'format_page');
        $table = new html_table();
        $table->head = array("<b>$modulestr</b>","<b>$usesstr</b>","<b>$commandstr</b>");
        $table->align = array('left', 'center', 'right');
        $table->size = array('50%', '10%', '40%');
        $table->width = '95%';

        foreach($mods as $mod) {
            if (!empty($modinfo)) {
                if (empty($mod->visible)) {
                    $linkclass = ' class="dimmed"';
                } else {
                    $linkclass = '';
                }

                $iconurl = $mod->get_icon_url();
                $module = '<img src="'.$iconurl.'" class="icon" />';
                // old : 
                // print '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->cmid.'">'.format_string(strip_tags($mod->name), true, $course->id).'</a>&nbsp;';
                $idnumberstring = '';
                if ($idnumber = $DB->get_field('course_modules', 'idnumber', array('id' => $mod->id))) {
                    $idnumberstring = "[$idnumber] ";
                }

                $moduleurl = new moodle_url('/mod/'.$mod->modname.'/view.php', array('id' => $mod->id));

                if ($mod->modname == 'customlabel') {
                    $module .= '<a'.$linkclass.' href="'.$moduleurl.'">'.$idnumberstring.format_string(strip_tags(urldecode($mod->extra)), true, $course->id).'</a>&nbsp;';
                } else if (isset($mod->name)) {
                    $module .= '<a'.$linkclass.' href="'.$moduleurl.'">'.$idnumberstring.format_string(strip_tags($mod->name), true, $course->id).'</a>&nbsp;';
                } else {
                    $module .= '<a'.$linkclass.' href="'.$moduleurl.'">'.$idnumberstring.format_string(strip_tags($mod->modname), true, $course->id).'</a>&nbsp;';
                }
                $commands = '<span class="commands">';
                // we need pageids of all locations of the module
                $pageitems = $DB->get_records('format_page_items', array('cmid' => $mod->id));

                if ($pageitems) {
                    foreach ($pageitems as $pageitem) {
                        $commands .= '<a title="'.$str->locate.'" href="'.$path.'/view.php?id='.$course->id."&amp;page={$pageitem->pageid}\"><img".
                           ' src="'.$OUTPUT->pix_url('/i/search') . '" class="icon-locate" '.
                           ' alt="'.$str->locate.'" /></a>&nbsp;';
                    }
                }

                if (!course_page::is_module_on_protected_page($mod->id) || has_capability('format/page:editprotectedpages', $context)) {
                    $commands .= '<a title="'.$str->update.'" href="'.$path.'/mod.php?update='.$mod->id.'&sesskey='.sesskey().'"><img'.
                       ' src="'.$OUTPUT->pix_url('/t/edit') . '" class="icon-edit" '.
                       ' alt="'.$str->update.'" /></a>&nbsp;';

                    $activitiesurl = new moodle_url('/course/format/page/actions/activities.php', array('id' => $course->id, 'page' => $pageid, 'what' => 'deletemod', 'sesskey' => sesskey(), 'cmid' => $mod->id));
                    $commands .= '<a title="'.$str->delete.'" href="'.$activitiesurl.'"><img'.
                       ' src="'.$OUTPUT->pix_url('/t/delete') . '" class="icon-edit" '.
                       ' alt="'.$str->delete.'" /></a></span>';
                }

                // print '</li>';
                $uses = $DB->count_records('format_page_items', array('cmid' => $mod->id)) + $DB->count_records('format_page', array('courseid' => $course->id, 'cmid' => $mod->id));
                $table->data[] = array($module, $uses, $commands);
            } else {
                if ($mod->modname == 'customlabel') {
                    print '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.'</li>';
                } else {
                    print '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.': '.$mod->instance.'</li>';
                }
            }
        }
        if (!empty($table->data)) {
            echo html_writer::table($table);
        }
        print '</p>';
    }
} else {
    echo $OUTPUT->box(get_string('noactivitiesfound', 'format_page'));
    echo '<br/>';
}

echo $OUTPUT->box_end(); // closes page-mode-list

echo $OUTPUT->box_end(); // Closes editing table.
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
