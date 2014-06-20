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
include_once($CFG->dirroot.'/course/format/page/lib.php');
include_once($CFG->dirroot.'/course/format/page/page.class.php');
include_once($CFG->dirroot.'/course/format/page/locallib.php');
include_once($CFG->dirroot.'/course/format/page/renderers.php');
include_once($CFG->dirroot.'/course/format/page/actions/assigngroupslib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:managepages', $context);    

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

$url = $CFG->wwwroot.'/course/format/page/actions/manage.php?id='.$course->id;

$PAGE->set_url($url); // Defined here to avoid notices on errors etc
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = new format_page_renderer($page);

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'page-actionform');
echo $renderer->print_tabs('manage', true);

echo $OUTPUT->heading($page->get_name());

$pagegroupsselector = new page_group_selector(null, array('pageid' => $pageid, 'courseid' => $course->id));
$potentialgroupsselector = new page_non_group_selector(array('pageid' => $pageid, 'courseid' => $course->id));

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $groupstoadd = $potentialgroupsselector->get_selected_groups();
    if (!empty($groupstoadd)) {
        foreach ($groupstoadd as $gid => $g) {
            if (!$page->add_group($gid)) {
                print_error('erroraddremovegroup', 'format_page', $url);
            }
        }
        $pagegroupsselector->reload();
        $potentialgroupsselector->reload();
    }
}

if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $groupstoremove = $pagegroupsselector->get_selected_groups();
    if (!empty($groupstoremove)) {
        foreach ($groupstoremove as $g) {
            if (!$page->remove_group($g->id)) {
                print_error('erroraddremovegroup', 'format_page', $url);
            }
        }
        $pagegroupsselector->reload();
        $potentialgroupsselector->reload();
    }
}

?>

<div id="addgroupsform">
<form id="assignform" method="post" action="<?php echo $CFG->wwwroot; ?>/course/format/page/actions/assigngroups.php?page=<?php echo $pageid; ?>">
<div>
<input type="hidden" name="id" value="<?php p($course->id); ?>" />
<input type="hidden" name="pageid" value="<?php p($pageid); ?>" />
<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />

<table class="generaltable generalbox pagemanagementtable boxaligncenter" summary="">
<tr>
  <td id="existingcell">
      <p>
        <label for="removeselect"><?php print_string('pagegroups', 'format_page'); ?></label>
      </p>
      <?php $pagegroupsselector->display(); ?>
      </td>
  <td id='buttonscell'>
    <p class="arrow_button">
        <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
        <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
    </p>
  </td>
  <td id="potentialcell">
      <p>
        <label for="addselect"><?php print_string('potentialgroups', 'format_page'); ?></label>
      </p>
      <?php $potentialgroupsselector->display(); ?>
  </td>
</tr>
</table>
</div>
</form>
</div>

<?php
echo '<br/><center>';
$opts['id'] = $course->id;
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/format/page/actions/manage.php?id=', $opts), get_string('manage', 'format_page'), 'get');
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/view.php?id=', $opts), get_string('backtocourse', 'format_page'), 'get');
echo '<br/></center>';

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
