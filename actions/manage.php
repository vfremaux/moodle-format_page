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
 * Page management service
 * 
 * @package format_page
 * @category format
 * @author Jeff Graham, Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

$id = required_param('id', PARAM_INT); // this is the course id
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);

// If no pages available, jump back to "edit first page";

// Set course display.
course_page::fix_tree();

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
    if (!$page = course_page::get($pageid)) {
        // Happens when deleting a page. We need find another where to start from safely.
        $page = course_page::get_default_page($course->id);
    }
} else {
    $page = course_page::get_current_page($course->id);
}

if (!$page) {
    // Course is empty maybe ?
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
} else {
    $pageid = $page->id;
}

if ($page->courseid != $course->id) {
    print_error('pageerror', 'format_page');
}

$url = new moodle_url('/course/format/page/actions/manage.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'format-page-editing-block');
echo $renderer->print_tabs('manage', true);
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('', 'page-actionform');
if ($pages = course_page::get_all_pages($course->id, 'nested')) {

    $table = new html_table();
    $table->head = array(get_string('pagename','format_page'),
                         get_string('pageoptions','format_page'),
                         get_string('displaymenu', 'format_page'),
                         get_string('templating', 'format_page'),
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

    $context = context_course::instance($COURSE->id);

    // Page link/name.
    $name = $renderer->pad_string('<a href="'.$page->url_build('page', $page->id).'">'.format_string($page->nameone).'</a>', $page->get_page_depth());

    // Edit, move and delete widgets.
    if (!$page->protected || has_capability('format/page:editprotectedpages', $context)) {
        $widgets  = ' <a href="'.$page->url_build('page', $page->id, 'action', 'editpage', 'returnaction', 'manage').'" class="icon edit" title="'.get_string('edit').'"><img src="'.$OUTPUT->pix_url('/t/edit') . '" alt="'.get_string('editpage', 'format_page').'" /></a>&nbsp;';
        $widgets .= ' <a href="'.$page->url_build('action', 'copypage', 'copypage', $page->id, 'sesskey', sesskey()).'" class="icon copy" title="'.get_string('clone', 'format_page').'"><img src="'.$OUTPUT->pix_url('/t/copy') . '" /></a>&nbsp;';
        $widgets .= ' <a href="'.$page->url_build('action', 'fullcopypage', 'copypage', $page->id, 'sesskey', sesskey()).'" class="icon copy" title="'.get_string('fullclone', 'format_page').'"><img src="'.$OUTPUT->pix_url('fullcopy', 'format_page') . '" /></a>&nbsp;';
        $widgets .= ' <a href="'.$page->url_build('action', 'confirmdelete', 'page', $page->id, 'sesskey', sesskey()).'" class="icon delete" title="'.get_string('delete').'"><img src="'.$OUTPUT->pix_url('/t/delete') . '" alt="'.get_string('deletepage', 'format_page').'" /></a>';

        // If we have some users.
        if ($users = get_enrolled_users(context_course::instance($COURSE->id))) {
            $dimmedclass = (!$page->has_user_accesses()) ? 'dimmed' : '';
            $widgets .= ' <a href="'.$page->url_build('action', 'assignusers', 'page', $page->id, 'sesskey', sesskey()).'" class="icon user" title="'.get_string('assignusers', 'format_page').'"><img class="'.$dimmedclass.'" src="'.$OUTPUT->pix_url('/i/user') . '" alt="'.get_string('assignusers', 'format_page').'" /></a>';
        }
    
        // If we have some groups.
        // this is being obsoleted by page/section conditionnality
        /**
        if ($groups = groups_get_all_groups($COURSE->id)) {
            $dimmedclass = (!$page->has_group_accesses()) ? 'dimmed' : '';
            $widgets .= ' <a href="'.$page->url_build('action', 'assigngroups', 'page', $page->id, 'sesskey', sesskey()).'" class="icon group" title="'.get_string('assigngroups', 'format_page').'"><img class="'.$dimmedclass.'" src="'.$OUTPUT->pix_url('/i/users') . '" alt="'.get_string('assigngroups', 'format_page').'" /></a>';
        }
        */
        $menu = page_manage_showhide_menu($page);
        $template = page_manage_switchtemplate_menu($page);
        $publish = page_manage_display_menu($page);
    } else {
        $widgets = '';
        $menu = '';
        $template = '';
        $publish = '';
    }

    $table->data[] = array($name, $widgets, $menu, $template, $publish);

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

    $params = array('id' => $page->courseid, 
                    'page' => $page->id,
                    'action' => 'showhidemenu',
                    'sesskey' => sesskey());

    if ($page->displaymenu) {
        $params['showhide'] = 0;
        $str = 'hide';
    } else {
        $params['showhide'] = 1;
        $str = 'show';
    }
    $url = new moodle_url('/course/format/page/action.php', $params);

    $return = '<a href="'.$url.'"><img src="'.$OUTPUT->pix_url("i/$str").'" alt="'.get_string($str).'" /></a>';
    return $return;
}

function page_manage_display_menu($page) {
    global $CFG, $OUTPUT, $COURSE;
    
    $DISPLAY_CLASSES[FORMAT_PAGE_DISP_HIDDEN] = 'format-page-urlselect-hidden';
    $DISPLAY_CLASSES[FORMAT_PAGE_DISP_PROTECTED] = 'format-page-urlselect-protected';
    $DISPLAY_CLASSES[FORMAT_PAGE_DISP_PUBLISHED] = 'format-page-urlselect-published';
    $DISPLAY_CLASSES[FORMAT_PAGE_DISP_PUBLIC] = 'format-page-urlselect-public';

    $url = "/course/format/page/action.php?id={$COURSE->id}&page={$page->id}&action=setdisplay&sesskey=".sesskey().'&display=';
    $selected = $url.$page->display;

    $optionurls = array();
    $optionurls[$url.FORMAT_PAGE_DISP_HIDDEN] = get_string('hidden', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PROTECTED] = get_string('protected', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PUBLISHED] = get_string('published', 'format_page');
    $optionurls[$url.FORMAT_PAGE_DISP_PUBLIC] = get_string('public', 'format_page');

    $select = new url_select($optionurls, $selected, array());
    $select->class = $DISPLAY_CLASSES[$page->display];
    return $OUTPUT->render($select);
}

function page_manage_switchtemplate_menu($page) {
    global $CFG, $OUTPUT;

    $params = array('id' => $page->courseid, 
                    'page' => $page->id,
                    'action' => 'templating',
                    'sesskey' => sesskey());
    if ($page->globaltemplate) {
        $params['enable'] = 0;
        $str = 'disabletemplate';
        $pix = 'activetemplate';
    } else {
        $params['enable'] = 1;
        $str = 'enabletemplate';
        $pix = 'inactivetemplate';
    }
    $url = new moodle_url('/course/format/page/action.php', $params);

    $return = '<a href="'.$url.'"><img src="'.$OUTPUT->pix_url($pix, 'format_page').'" alt="'.get_string($str, 'format_page').'" /></a>';
    return $return;
}
