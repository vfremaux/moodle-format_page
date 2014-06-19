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
 * Page management
 * 
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Page management service
 * 
 * @package format_page
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/renderers.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);

// If no pages available, jump back to "edit first page";

// Set course display.
if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    $page = course_page::get($pageid);
    $page->fix_tree();
} else {
    if (!$page = course_page::get_current_page($course->id)) {
        print_error('errornopage', 'format_page');
    }
    $pageid = $page->id;
}

$url = $CFG->wwwroot.'/course/format/page/actions/manage.php?id='.$course->id;

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = new format_page_renderer($page);

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'page-actionform');
echo $renderer->print_tabs('manage', true);

if ($pages = course_page::get_all_pages($course->id, 'nested')) {

    $table = new html_table();
    $table->head = array(get_string('pagename','format_page'),
                         get_string('pageoptions','format_page'),
                         get_string('displaymenu', 'format_page'),
                         get_string('publish', 'format_page'));
    $table->align       = array('left', 'center', 'center', 'center');
    $table->width       = '95%';
    $table->cellspacing = '0';
    $table->id          = 'editing-table';
    $table->class       = 'generaltable pageeditingtable';
    $table->data        = array();

    foreach ($pages as $page) {
        page_print_page_row($table, $page, $renderer);
    }

    echo '<center>';
    echo html_writer::table($table);
    echo '</center>';
} else {
    print_error('nopages', 'format_page');
}

echo '<br/><center>';
$opts['id'] = $course->id;
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/format/page/actions/moving.php?id=', $opts), get_string('reorganize', 'format_page'), 'get');
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/view.php?id=', $opts), get_string('backtocourse', 'format_page'), 'get');
echo '<br/></center>';

echo $OUTPUT->box_end();

echo $OUTPUT->footer();

// Local utility functions.

/**
 * Local methods to assist with generating output
 * that is specific to this page
 *
 */
function page_print_page_row(&$table, $page, &$renderer) {
    global $OUTPUT, $COURSE;

    // Page link/name.
    $name = $renderer->pad_string('<a href="'.$page->url_build('page', $page->id).'">'.format_string($page->nameone).'</a>', $page->get_page_depth());

    // Edit, move and delete widgets.
    $widgets  = ' <a href="'.$page->url_build('page', $page->id, 'action', 'editpage', 'returnaction', 'manage').'" class="icon edit" title="'.get_string('edit').'"><img src="'.$OUTPUT->pix_url('/t/edit') . '" alt="'.get_string('editpage', 'format_page').'" /></a>&nbsp;';
    $widgets .= ' <a href="'.$page->url_build('action', 'copypage', 'copypage', $page->id, 'sesskey', sesskey()).'" class="icon copy" title="'.get_string('clone', 'format_page').'"><img src="'.$OUTPUT->pix_url('/t/copy') . '" /></a>&nbsp;';
    $widgets .= ' <a href="'.$page->url_build('action', 'confirmdelete', 'page', $page->id, 'sesskey', sesskey()).'" class="icon delete" title="'.get_string('delete').'"><img src="'.$OUTPUT->pix_url('/t/delete') . '" alt="'.get_string('deletepage', 'format_page').'" /></a>';

    // If we have some users.
    if ($users = get_enrolled_users(context_course::instance($COURSE->id))) {
        $dimmedclass = (!$page->has_user_accesses()) ? 'dimmed' : '';
        $widgets .= ' <a href="'.$page->url_build('action', 'assignusers', 'page', $page->id, 'sesskey', sesskey()).'" class="icon user" title="'.get_string('assignusers', 'format_page').'"><img class="'.$dimmedclass.'" src="'.$OUTPUT->pix_url('/i/user') . '" alt="'.get_string('assignusers', 'format_page').'" /></a>';
    }

    // If we have some groups.
    if ($groups = groups_get_all_groups($COURSE->id)) {
        $dimmedclass = (!$page->has_group_accesses()) ? 'dimmed' : '';
        $widgets .= ' <a href="'.$page->url_build('action', 'assigngroups', 'page', $page->id, 'sesskey', sesskey()).'" class="icon group" title="'.get_string('assigngroups', 'format_page').'"><img class="'.$dimmedclass.'" src="'.$OUTPUT->pix_url('/i/users') . '" alt="'.get_string('assigngroups', 'format_page').'" /></a>';
    }

    $menu    = page_manage_showhide_menu($page);
    $publish = page_manage_display_menu($page);

    $table->data[] = array($name, $widgets, $menu, $publish);

    $childs = $page->childs;
    if (!empty($childs)) {
        foreach ($childs as $child) {
            page_print_page_row($table, $child, $renderer);
        }
    }
}

/**
 * This function displays the hide/show icon & link page display settings
 *
 * @param object $page Page to show the widget for
 * @param int $type a display type to show
 * @uses $CFG
 */
function page_manage_showhide_menu($page) {
    global $CFG, $OUTPUT;

    if ($page->displaymenu) {
        $showhide = 'showhide=0';
        $str = 'hide';
    } else {
        $showhide = 'showhide=1';
        $str = 'show';
    }

    $return = "<a href=\"$CFG->wwwroot/course/format/page/action.php?id=$page->courseid&page=$page->id".
               "&action=showhidemenu&$showhide&sesskey=".sesskey().'">'.
               "<img src=\"".$OUTPUT->pix_url("i/$str")."\" alt=\"".get_string($str).'" /></a>';
    return $return;
}

function page_manage_display_menu($page) {
    global $CFG, $OUTPUT, $COURSE;

    $url = "/course/format/page/action.php?id={$COURSE->id}&page={$page->id}&action=setdisplay&sesskey=".sesskey().'&display=';
    $selected = $url.$page->display;

    $optionurls = array();
    $optionurls[$url.FORMAT_PAGE_DISP_HIDDEN] = get_string('hidden', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PROTECTED] = get_string('protected', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PUBLISHED] = get_string('published', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PUBLIC] = get_string('public', 'format_page');

    $return = $OUTPUT->url_select($optionurls, $selected, array());

    return $return;
}
