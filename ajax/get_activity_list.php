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

require('../../../../config.php');

$filter = optional_param('filter', '', PARAM_TEXT);
$courseid = required_param('id', PARAM_INT);
$pageid = required_param('page', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    mtrace('courseerror');
}

require_login($course);

$modinfo = get_fast_modinfo($course);

$mods = $modinfo->get_cms();

if (!empty($mods)) {
    $str = new stdClass();
    $str->delete = get_string('delete');
    $str->update = get_string('update');
    $str->locate = get_string('locate', 'format_page');
    $path = $CFG->wwwroot.'/course';
    $sortedmods  = array();

    foreach ($mods as $mod) {
        if (!$modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance))) {
            continue;
        }
        if (!empty($filter) && !preg_match('/.*'.$filter.'.*/', $modinstance->name)) {
            continue;
        }
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
    $last = ''; // Keeps track of modules.
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
            echo "<h2><a href=\"$CFG->wwwroot/mod/$modname/index.php?id=$course->id\">".get_string('modulename', $modname).'</a></h1>';
            $last    = $modname;
            $lastsub = '';
        }

        if ($lastsub != $subname) {
            $strtype = get_string($subname, $modname);
            if (strpos($strtype, '[') !== false) {
                $strtype = get_string($modname.':'.$subname, 'format_page');
            }
            echo '&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.$strtype.'</strong><br />';
            $lastsub = $subname;
        }

        echo '<p align="right">';
        $modulestr = get_string('module', 'format_page');
        $usesstr = get_string('occurrences', 'format_page');
        $commandstr = get_string('commands', 'format_page');
        $table = new html_table();
        $table->head = array("<b>$modulestr</b>","<b>$usesstr</b>","<b>$commandstr</b>");
        $table->align = array('left', 'center', 'right');
        $table->size = array('50%', '10%', '40%');
        $table->width = '95%';
        foreach ($mods as $mod) {
            if (!empty($modinfo)) {
                if (empty($mod->visible)) {
                    $linkclass = ' class="dimmed"';
                } else {
                    $linkclass = '';
                }

                $iconurl = $mod->get_icon_url();
                $module = '<img src="'.$iconurl.'" class="icon" />';
                $idnumberstring = '';
                if ($idnumber = $DB->get_field('course_modules', 'idnumber', array('id' => $mod->id))){
                    $idnumberstring = "[$idnumber] ";
                }
                if ($mod->modname == 'customlabel') {
                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags(urldecode($mod->extra)), true, $course->id).'</a>&nbsp;';
                } else if (isset($mod->name)) {
                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags($mod->name), true, $course->id).'</a>&nbsp;';
                } else {
                    $module .= '<a'.$linkclass.' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$idnumberstring.format_string(strip_tags($mod->modname), true, $course->id).'</a>&nbsp;';
                }
                $commands = '<span class="commands">';
                // we need pageids of all locations of the module.
                $pageitems = $DB->get_records('format_page_items', array('cmid' => $mod->id));

                if ($pageitems) {
                    foreach ($pageitems as $pageitem) {
                        $commands .= '<a title="'.$str->locate.'" href="'.$path.'/view.php?id='.$course->id."&amp;page={$pageitem->pageid}\"><img".
                           ' src="'.$OUTPUT->pix_url('/i/search') . '" class="icon-locate" '.
                           ' alt="'.$str->locate.'" /></a>&nbsp;';
                    }
                }

                $commands .= '<a title="'.$str->update.'" href="'.$path.'/mod.php?update='.$mod->id.'&sesskey='.sesskey().'"><img'.
                   ' src="'.$OUTPUT->pix_url('/t/edit') . '" class="icon-edit" '.
                   ' alt="'.$str->update.'" /></a>&nbsp;';
                $commands .= '<a title="'.$str->delete.'" href="'.$CFG->wwwroot.'/course/format/page/actions/activities.php?id='.$course->id.'&amp;page='.$pageid.'&amp;what=deletemod&amp;sesskey='.sesskey().'&amp;cmid='.$mod->id.'"><img'.
                   ' src="'.$OUTPUT->pix_url('/t/delete') . '" class="icon-edit" '.
                   ' alt="'.$str->delete.'" /></a></span>';
                $uses = $DB->count_records('format_page_items', array('cmid' => $mod->id)) + $DB->count_records('format_page', array('courseid' => $course->id, 'cmid' => $mod->id));
                $table->data[] = array($module, $uses, $commands);
            } else {
                if ($mod->modname == 'customlabel') {
                    echo '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.'</li>';
                } else {
                    echo '<li>'.get_string('misconfiguredmodule', 'format_page').' '.$mod->modname.': '.$mod->instance.'</li>';
                }
            }
        }
        if (!empty($table->data)) {
            echo html_writer::table($table);
        }
        echo '</p>';
    }
} else {
    echo $OUTPUT->box(get_string('noactivitiesfound', 'format_page'));
    echo '<br/>';
}
