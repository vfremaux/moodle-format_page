<?php

require_once $CFG->libdir.'/formslib.php';

class Page_Discussion_Form extends moodleform {

    function definition(){
        global $COURSE;
        
        $context = context_course::instance($COURSE->id);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $context);
        
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id'); // course id

        $mform->addElement('hidden', 'discussionid'); // dicussion record id

        $mform->addElement('hidden', 'action');
        $mform->setDefault('action', 'discussion');
        $mform->addElement('header', 'discussionheader', get_string('discussion', 'format_page'));
        $mform->addElement('editor', 'discussion_editor', get_string('discussion', 'format_page'), array('cols' => 120, 'rows' => 30), $this->editoroptions);
        $mform->setType('discussion_editor', PARAM_RAW);

        $this->add_action_buttons(true, get_string('update'));

    }
    
    function set_data($defaults){
    	global $COURSE;

    	$context = context_course::instance($COURSE->id);

		$discussion_draftid_editor = file_get_submitted_draft_itemid('discussion_editor');
		$currenttext = file_prepare_draft_area($discussion_draftid_editor, $context->id, 'format_page', 'discussion_editor', $defaults->pageid, array('subdirs' => true), $defaults->discussion);
		$defaults = file_prepare_standard_editor($defaults, 'discussion', $this->editoroptions, $context, 'format_page', 'discussion', $defaults->pageid);
		$defaults->discussion = array('text' => $currenttext, 'format' => $defaults->discussionformat, 'itemid' => $discussion_draftid_editor);

    	parent::set_data($defaults);
    }
}
?>