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
 * This page allows one to hide/show children of menu master pages
 *
 * @author Mark Nielsen, Jeff Graham
 * @author for Moodle 2 : Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * Not sure to use
 **/

require_once('../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$success = optional_param('success', '', PARAM_ALPHA);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('coursemisconf');
}
require_login($course->id);

// Load up the context for calling has_capability later.
$context = context_course::instance($course->id);
if (!(has_capability('format/page:managepages', $context) || has_capability('format/page:viewpagesettings', $context))) {
    print_error('erroractionnotpermitted', 'format_page');
}

// Only let those with managepages edit the settings.
if (has_capability('format/page:managepages', $context)) {
    if ($pageid = optional_param('pageid', 0, PARAM_INT) and $showhide = optional_param('showhide', '', PARAM_ALPHA) and confirm_sesskey()) {
        $page = course_page::get($pageid);

        // Get child pages.
        if ($childpages = $page->get_children($pageid)) {
            foreach ($childpages as $childpage) {
                // For each child, hide or show it depending on the action (check before doing so!).
                if ($showhide == 'show') {
                    if (!($childpage->display == FORMAT_PAGE_DISP_PROTECTED)) {
                        $DB->set_field('format_page', 'display', FORMAT_PAGE_DISP_PUBLISHED, array('id' => $childpage->id));
                    }
                } else if ($showhide == 'hide') {
                    if ($childpage->display == FORMAT_PAGE_DISP_PUBLISHED) {
                        $DB->set_field('format_page', 'display', FORMAT_PAGE_DISP_PROTECTED, array('id' => $childpage->id));
                    }
                }
            }
        }
        // Prevent problems with refreshes and need to reset page format cache.
        redirect(new moodle_url('/course/format/page/managemenu.php', array('id' => $course->id, 'success' => $showhide)));
    }
}

$titlestr = get_string('hideshowmodules', 'format_page');

$PAGE->set_title("$course->fullname: $titlestr");
$PAGE->set_heading($titlestr);

echo $OUTPUT->header();

// Title and instructions.
echo '<div class="wrapper" style="width: 60%; margin: 0 auto;">';
echo $OUTPUT->heading($titlestr);
if (has_capability('format/page:managepages', $context)) {
    echo '<div class="instructions">'.get_string('hideshowmodulesinstructions', 'format_page').'</div>';
}
echo '</div>';
if (!empty($success)) {
    // Notify of success.
    if ($success == 'show') {
        $notifysuccess = get_string('menuitemunlocked', 'format_page');
    } else if ($success == 'hide') {
        $notifysuccess = get_string('menuitemlocked', 'format_page');
    } else {
        $notifysuccess = get_string('changessaved');
    }
    echo $OUTPUT->notification($notifysuccess, 'notifysuccess');
}
if ($masters = page_get_menu_pages($course->id)) {
    // Display all menu pages with show/hide eyes.
    $table = new html_table();
    $table->head        = array(get_string('coursemenu', 'format_page'), get_string('menuitem', 'format_page'), get_string('showhide', 'format_page'));
    $table->wrap        = array('nowrap', 'nowrap', '');
    $table->size        = array('', '', '150px');
    $table->align       = array('left', 'left', 'center');
    $table->width       = '60%';
    $table->tablealign  = 'center';
    $table->cellpadding = '5px';
    $table->cellspacing = '0';
    $table->data        = array();

    foreach ($masters as $master) {
        $table->data[] = array($master->nameone, '', '');
        if ($pages = $DB->get_records('format_page', array('parent' => $master->id), 'sortorder, nameone')) {
            foreach($pages as $page) { 
                if ($childpages = page_get_children($page->id)) {
                    $showhide = 'show';  // Default
                    // If any child is published, then this menu item is considered unlocked
                    foreach ($childpages as $childpage) {
                        if ($childpage->display & DISP_PUBLISH) {
                            $showhide = 'hide';
                            break;
                        }
                    }
                    $sesskey = sesskey();
                    $showhidestr = get_string($showhide);
                    $eye = '';
                    if (has_capability('format/page:managepages', $context)) {
                        $params = array('id' => $course->id, 'pageid' => $page->id, 'showhide' => $showhide, 'sesskey' => $sesskey);
                        $manageurl = new moodle_url('/course/format/page/managemenu.php', $params);
                        $eye .= '<a href="'.$manageurl.'">';
                    }
                    $eye .= "<img src=\"".$OUTPUT->pix_url('/i/$showhide')."\" alt=\"$showhidestr\" />";
                    if (has_capability('format/page:managepages', $context)) {
                        $eye .= '</a>';
                    }
                } else {
                    // No children, so cannot lock/unlock anything.
                    $eye = get_string('nochildpages', 'format_page');
                }
                $name = page_get_name($page);
                $page = link_to_popup_window (new moodle_url('/course/view.php', array('id' => $course->id, 'page' => $page->id)), 'course', $name, 800, 1000, $name, 'none', true);
                $table->data[] = array('', $page, $eye);
            }
        }
    }
    echo html_writer::table($table);
} else {
    // No pages found.
    echo $OUTPUT->notification(get_string('nomenupagesfound', 'format_page'));
}
echo $OUTPUT->footer($course);

