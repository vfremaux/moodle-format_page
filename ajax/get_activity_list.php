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
require('../../../../config.php');

$filter = optional_param('filter', '', PARAM_TEXT);
$courseid = required_param('id', PARAM_INT);
$pageid = required_param('page', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    mtrace('courseerror');
}

// Security.

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
    $funccode = 'return strnatcmp(get_string(\'modulename\', $a->modname), get_string(\'modulename\', $b->modname));';
    $function = create_function('$a, $b', $funccode);

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
            $modurl = new moodle_url('/mod/'.$modname.'/index.php', array('id' => $mods->id));
            echo '<h2><a href="'.$modurl.'">'.get_string('modulename', $modname).'</a></h1>';
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
        $table->head = array("<b>$modulestr</b>", "<b>$usesstr</b>", "<b>$commandstr</b>");
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
                $modurl = new moodle_url('/mod/'.$mod->modname.'/view.php', array('id' => $mod->id));
                if ($mod->modname == 'customlabel') {
                    $label = $idnumberstring.format_string(strip_tags(urldecode($mod->extra)), true, $course->id);
                    $module .= '<a'.$linkclass.' href="'.$modurl.'">'.$label.'</a>&nbsp;';
                } else if (isset($mod->name)) {
                    $label = $idnumberstring.format_string(strip_tags($mod->name), true, $course->id);
                    $module .= '<a'.$linkclass.' href="'.$modurl.'">'.$label.'</a>&nbsp;';
                } else {
                    $label = $idnumberstring.format_string(strip_tags($mod->modname), true, $course->id);
                    $module .= '<a'.$linkclass.' href="'.$modurl.'">'.$label.'</a>&nbsp;';
                }
                $commands = '<span class="commands">';
                // We need pageids of all locations of the module.
                $pageitems = $DB->get_records('format_page_items', array('cmid' => $mod->id));

                if ($pageitems) {
                    foreach ($pageitems as $pageitem) {
                        $locateurl = new moodle_url('/course/view.php', array('id' => $course->id, 'page' => $pageitem->pageid));
                        $pix = '<img src="'.$OUTPUT->pix_url('/i/search').'" class="icon-locate" alt="'.$str->locate.'" />';
                        $commands .= '<a title="'.$str->locate.'" href="'.$locateurl.'">'.$pix.'</a>&nbsp;';
                    }
                }

                $editurl = new moodle_url('/course/mod.php', array('update' => $mod->id, 'sesskey' => sesskey()));
                $pix = '<img src="'.$OUTPUT->pix_url('/t/edit').'" class="icon-edit" alt="'.$str->update.'" />';
                $commands .= '<a title="'.$str->update.'" href="'.$editurl.'">'.$pix.'</a>&nbsp;';

                $params = array('id' => $course->id, 'page' => $pageid, 'what' => 'deletemod', 'sesskey' => sesskey());
                $deleteurl = new moodle_url('/course/format/page/actions/activities.php', $params);
                $pix = '<img src="'.$OUTPUT->pix_url('/t/delete') . '" class="icon-edit" alt="'.$str->delete.'" />';
                $commands .= '<a title="'.$str->delete.'" href="'.$deleteurl.'&amp;cmid='.$mod->id.'">'.$pix.'</a></span>';

                $pageitemcount = $DB->count_records('format_page_items', array('cmid' => $mod->id));
                $pagecount = $DB->count_records('format_page', array('courseid' => $course->id, 'cmid' => $mod->id));
                $uses = $pageitemcount + $pagecount;
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
