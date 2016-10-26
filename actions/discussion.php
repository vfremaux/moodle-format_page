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
 *
 * Page reorganisation service
 */
require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/forms/discussion_form.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// Security.

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

$url = new moodle_url('/course/format/page/actions/discussion.php', array('id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Starts page content.

$editing = optional_param('edit', 0, PARAM_INT);

if (!$discussion = $DB->get_record('format_page_discussion', array('pageid' => $pageid))) {
    $discussion = new StdClass;
    $discussion->discussion = '';
}

$mform = new page_discussion_form();

if ($editing) {
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('', 'discussion-panel');
    $discussion->discussionid = @$discussion->id;
    $discussion->id = $COURSE->id;
    $discussion->pageid = $pageid;
    $discussion->discussionformat = FORMAT_HTML;
    $mform->set_data($discussion);
    $mform->display();
    echo $OUTPUT->box_end();
} else {
    // Discussion data submitted.
    if ($mform->is_cancelled()) {
        redirect($url.'&sesskey='.sesskey(), get_string('discussioncancelled', 'format_page'));
    } elseif (($discussion = $mform->get_data())) {
        if (!empty($discussion->discussionid)) {
            $discussion_draftid_editor = file_get_submitted_draft_itemid('discussion_editor');
            $data = new StdClass;
            $data->discussion = $discussion->discussion_editor['text'];
            $data->discussion = file_save_draft_area_files($discussion_draftid_editor, $context->id, 'format_page',
                                                           'discussion', $pageid, array('subdirs' => true), $data->discussion);

            $discussion->id = $discussion->discussionid;
            $discussion->lastmodified = time();
            $discussion->pageid = $pageid;
            $discussion->discussion = $discussion->discussion_editor['text'];
            $discussion->lastwriteuser = $USER->id;

            $discussion = file_postupdate_standard_editor($discussion, 'discussion', $mform->editoroptions, $context,
                                                          'format_page', 'discussion', $pageid);

            $DB->update_record('format_page_discussion', $discussion);
        } else {
            $draftideditor = file_get_submitted_draft_itemid('discussion_editor');
            $data = new StdClass;
            $data->discussion = $discussion->discussion_editor['text'];
            $data->discussion = file_save_draft_area_files($draftideditor, $context->id, 'format_page',
                                                           'discussion', $pageid, array('subdirs' => true), $data->discussion);

            $discussion->discussion = $discussion->discussion_editor['text'];
            $discussion->lastmodified = time();
            $discussion->pageid = $pageid;
            $discussion->lastwriteuser = $USER->id;
            $discussion->id = $DB->insert_record('format_page_discussion', $discussion);
        }
    }

    // Mark last read for the current user.
    $params = array('userid' => $USER->id, 'pageid' => $pageid);
    if ($discussionuser = $DB->get_record('format_page_discussion_user', $params)) {
        $discussionuser->lastread = time();
        $DB->update_record('format_page_discussion_user', $discussionuser);
    } else {
        $discussionuser = new StdClass;
        $discussionuser->userid = $USER->id;
        $discussionuser->pageid = $pageid;
        $discussionuser->lastread = time();
        $DB->insert_record('format_page_discussion_user', $discussionuser);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->box_start('', 'discussion-panel');

    echo '<br/>';
    echo $OUTPUT->box_start();
    echo '<center>';
    print_string('localdiscussionadvice', 'format_page');
    echo '<hr>';
    echo '</center>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start();

    // Get it again because smashed out by the form return processing.
    if (!$discussion = $DB->get_record('format_page_discussion', array('pageid' => $pageid))) {
        $discussion = new StdClass;
        $discussion->discussion = '';
    }

    $discussiontext = file_rewrite_pluginfile_urls($discussion->discussion, 'pluginfile.php', $context->id,
                                                   'format_page', 'discussion', $pageid);
    echo $discussiontext;
    echo $OUTPUT->box_end();

    echo '<center>';
    if (!empty($discussion->lastmodified)) {
        print_string('lastmodified', 'format_page');
        echo ' <span class="date">'.userdate($discussion->lastmodified).'</span>';
        print_string('by', 'format_page');
        $lastauthor = $DB->get_record('user', array('id' => $discussion->lastwriteuser));
        echo ' <span class="user">'.fullname($lastauthor) .'</span>';
    }

    echo '</center>';
    $options['id'] = $COURSE->id;
    $options['action'] = 'discussion';
    $options['edit'] = 1;
    $options['pageid'] = $pageid;
    $options['sesskey'] = sesskey();
    echo '<center>';
    echo '<br/>';
    echo $OUTPUT->single_button(new moodle_url($url, $options), get_string('discuss', 'format_page'), 'get');

    $buttonurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
    echo $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'format_page'), 'get');
    echo '<br/>';
    echo '</center>';

    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
