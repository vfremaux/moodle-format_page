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

require_once $CFG->libdir.'/formslib.php';

class Page_Discussion_Form extends moodleform {

    public function definition() {
        global $COURSE;

        $context = context_course::instance($COURSE->id);

        $maxfiles = 99;
        $maxbytes = $COURSE->maxbytes;
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $context);
        
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id'); // Course id
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid'); // Page id
        $mform->setType('pageid', PARAM_INT);

        $mform->addElement('hidden', 'discussionid'); // Discussion record id
        $mform->setType('discussionid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setDefault('action', 'discussion');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'discussionheader', get_string('discussion', 'format_page'));

        $mform->addElement('editor', 'discussion_editor', get_string('discussion', 'format_page'), array('cols' => 120, 'rows' => 30), $this->editoroptions);
        $mform->setType('discussion_editor', PARAM_RAW);

        $this->add_action_buttons(true, get_string('update'));
    }

    public function set_data($defaults) {
        global $COURSE;

        $context = context_course::instance($COURSE->id);

        $discussion_draftid_editor = file_get_submitted_draft_itemid('discussion_editor');
        $currenttext = file_prepare_draft_area($discussion_draftid_editor, $context->id, 'format_page', 'discussion_editor', @$defaults->pageid, array('subdirs' => true), $defaults->discussion);
        $defaults = file_prepare_standard_editor($defaults, 'discussion', $this->editoroptions, $context, 'format_page', 'discussion', @$defaults->pageid);
        $defaults->discussion = array('text' => $currenttext, 'format' => $defaults->discussionformat, 'itemid' => $discussion_draftid_editor);

        parent::set_data($defaults);
    }
}
