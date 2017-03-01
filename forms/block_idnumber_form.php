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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class block_idnumber_form extends moodleform {

    public function definition() {
        global $COURSE;

        $context = context_course::instance($COURSE->id);

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id'); // Course id.
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'page'); // Page id.
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'blockid'); // Block id.
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('text', 'blockidnumber', get_string('idnumber'));
        $mform->setType('blockidnumber', PARAM_RAW);

        $this->add_action_buttons(true, get_string('update'));
    }
}
