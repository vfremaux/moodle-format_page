<?php

/**
 * Page management
 * 
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Page reorganisation service
 * 
 * @package format_page
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

	include '../../../../config.php';
	include_once $CFG->dirroot.'/course/format/page/lib.php';
	include_once $CFG->dirroot.'/course/format/page/page.class.php';
	include_once $CFG->dirroot.'/course/format/page/locallib.php';
	include_once $CFG->dirroot.'/course/format/page/renderer.php';
    require_once($CFG->dirroot.'/course/format/page/actions/discussion_form.php');

    $id = required_param('id', PARAM_INT);
    $pageid = optional_param('page', 0, PARAM_INT);

    if (!$course = $DB->get_record('course', array('id' => $id))){
    	print_error('invalidcourseid');
    }
    
	require_login($course);
    $context = context_course::instance($course->id);
	require_capability('format/page:managepages', $context);    

/// Set course display
    if ($pageid > 0) {
    	// changing page depending on context
        $pageid = course_page::set_current_page($course->id, $pageid);
        $page = course_page::get($pageid);
    } else {
        if (!$page = course_page::get_current_page($course->id)) {
    		print_error('errornopage', 'format_page');
        }
        $pageid = $page->id;
    }

	$url = $CFG->wwwroot.'/course/format/page/actions/discussion.php?id='.$course->id;

    $PAGE->set_url($url); // Defined here to avoid notices on errors etc
    $PAGE->set_pagelayout('format_page_action');
    $PAGE->set_context($context);
    $PAGE->set_pagetype('course-view-' . $course->format);

	$renderer = new format_page_renderer($page);

/// Starts page content

    $editing = optional_param('edit', 0, PARAM_INT);

    if (!$discussion = $DB->get_record('format_page_discussion', array('pageid' => $pageid))){
    	$discussion = new StdClass;
        $discussion->discussion = '';
    }
    
    $mform = new Page_Discussion_Form();

    if ($editing){
		echo $OUTPUT->header();
        echo $OUTPUT->box_start('', 'discussion-panel');
    	$discussion->discussionid = @$discussion->id;
    	$discussion->id = $COURSE->id;
    	$discussion->discussionformat = FORMAT_HTML;
    	$mform->set_data($discussion);
        $mform->display();
        echo $OUTPUT->box_end();
    } else {
        // discussion data submitted
        if ($mform->is_cancelled()){  
            redirect($url.'&sesskey='.sesskey(), get_string('discussioncancelled', 'format_page'));
        } else if (($discussion = $mform->get_data())) {
            if (!empty($discussion->discussionid)){
            	
				$discussion_draftid_editor = file_get_submitted_draft_itemid('discussion_editor');
				$data = new StdClass;
				$data->discussion = $discussion->discussion_editor['text'];
				$data->discussion = file_save_draft_area_files($discussion_draftid_editor, $context->id, 'format_page', 'discussion', $pageid, array('subdirs' => true), $data->discussion);

                $discussion->id = $discussion->discussionid;
                $discussion->lastmodified = time();
                $discussion->pageid = $pageid;
            	$discussion->discussion = $discussion->discussion_editor['text'];
                $discussion->lastwriteuser = $USER->id;

	    		$discussion = file_postupdate_standard_editor($discussion, 'discussion', $mform->editoroptions, $context, 'format_page', 'discussion', $pageid);

                $DB->update_record('format_page_discussion', $discussion);
            } else {
            	$discussion = new StdClass;

				$discussion_draftid_editor = file_get_submitted_draft_itemid('discussion_editor');
				$discussion->discussion = file_save_draft_area_files($discussion_draftid_editor, $context->id, 'format_page', 'discussion', $pageid, array('subdirs' => true), $data->discussion);

                $discussion->lastmodified = time();
                $discussion->pageid = $pageid;
                $discussion->lastwriteuser = $USER->id;
                $DB->insert_record('format_page_discussion', $discussion);
            }
        } else {
        	// recreate new one
	    	$discussion = new StdClass;
	        $discussion->discussion = '';
        }

        // mark last read for the current user
        if ($discussionuser = $DB->get_record('format_page_discussion_user', array('userid' => $USER->id, 'pageid' => $pageid))){
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

        $discussiontext = file_rewrite_pluginfile_urls($discussion->discussion, 'pluginfile.php', $context->id, 'format_page', 'discussion', $pageid);
        echo $discussiontext;
        echo $OUTPUT->box_end();

        echo '<center>';
        if (!empty($discussion->lastmodified)){
            print_string('lastmodified', 'format_page');
            echo ' <span class="date">'.userdate($discussion->lastmodified).'</span> ';
            print_string('by', 'format_page');
            $lastauthor = $DB->get_record('user', array('id' => $discussion->lastwriteuser));
            echo ' <span class="user">' . fullname($lastauthor) .'</span>';
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
        echo '<br/>';

		$opts['id'] = $COURSE->id;
        echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/view.php', $opts), get_string('backtocourse', 'format_page'), 'get');
        echo '<br/>';
        echo '</center>';

		echo $OUTPUT->box_end();
    }

	echo $OUTPUT->footer();
?>