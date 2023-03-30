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
 * Light screen to setup a block idnumber.
 *
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/forms/block_idnumber_form.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);

if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badblockid');
}

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:editpages', $context);

$PAGE->set_context($context);

$instance = block_instance($blockrec->blockname, $blockrec);

// Security.

$PAGE->set_url('/course/view.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_pagetype('course-view-' . $course->format);

$mform = new block_idnumber_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $id, 'page' => $pageid)));
} else if ($data = $mform->get_data()) {
    $DB->set_field('format_page_items', 'idnumber', $data->blockidnumber, array('blockinstance' => $blockid));
    redirect(new moodle_url('/course/view.php', array('id' => $id, 'page' => $data->page)));
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('', 'region-main');
echo $OUTPUT->heading(get_string('blockidnumber', 'format_page', $instance->get_title()));

$formdata = new StdClass;
$formdata->id = $id;
$formdata->page = $pageid;
$formdata->blockid = $blockid;
$formdata->blockidnumber = $DB->get_field('format_page_items', 'idnumber', array('blockinstance' => $blockid));
$mform->set_data($formdata);
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();