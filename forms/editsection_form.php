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
 * @copyright 2008 Valery Fremaux (mylearningfactory.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/editsection_form.php');

/**
 * Default form for editing course section
 *
 * Course format plugins may specify different editing form to use
 */
class page_editsection_form extends editsection_form {

    public function definition() {

        $mform = $this->_form;
        $course = $this->_customdata['course'];

        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'usedefaultname');
        $mform->setType('usedefaultname', PARAM_BOOL);

        // Additional fields that course format has defined.
        $courseformat = course_get_format($course);
        $formatoptions = $courseformat->section_format_options(true);
        if (!empty($formatoptions)) {
            $courseformat->create_edit_form_elements($mform, true);
        }

        $mform->_registerCancelButton('cancel');
    }

    public function definition_after_data() {
        global $CFG;

        $mform = $this->_form;
        $course = $this->_customdata['course'];

        if (!empty($CFG->enableavailability)) {
            $mform->addElement('header', 'availabilityconditions', get_string('restrictaccess', 'availability'));
            $mform->setExpanded('availabilityconditions', true);

            /*
             * Availability field. This is just a textarea; the user interface
             * interaction is all implemented in JavaScript. The field is named
             * availabilityconditionsjson for consistency with moodleform_mod.
             */
            $mform->addElement('textarea', 'availabilityconditionsjson', get_string('accessrestrictions', 'availability'));
            \core_availability\frontend::include_all_javascript($course, null, $this->_customdata['cs']);
        }

        $this->add_action_buttons();
    }

    /**
     * Load in existing data as form defaults
     *
     * @param stdClass|array $default_values object or array of default values
     */
    public function set_data($default_values) {
        if (!is_object($default_values)) {
            // We need object for file_prepare_standard_editor.
            $default_values = (object) $default_values;
        }
        $default_values->usedefaultname = (is_null($default_values->name));
        parent::set_data($default_values);
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        global $DB;

        // Warning because summary_editor not found.
        $data = @parent::get_data();

        if ($data !== null) {
            $data->id = required_param('id', PARAM_INT);
            $data->sr = required_param('sr', PARAM_INT);

            if (!empty($data->usedefaultname)) {
                $data->name = null;
            }

            $course = $this->_customdata['course'];
            foreach (course_get_format($course)->section_format_options() as $option => $unused) {
                // Fix issue with unset checkboxes not being returned at all.
                if (!isset($data->$option)) {
                    $data->$option = null;
                }
            }
            $data->summary_editor = null;
            $data->summary = $DB->get_field('course_sections', 'summary', array('id' => $data->id));
            $data->summaryformat = $DB->get_field('course_sections', 'summaryformat', array('id' => $data->id));
        }
        return $data;
    }

}
