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

defined('MOODLE_INTERNAL') || die();

/**
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->dirroot.'/group/lib.php');

/**
 * Base class to avoid duplicating code.
 */
abstract class page_group_selector_base {

    protected $name;
    protected $pageid;
    protected $courseid;
    protected $selection;
    protected $multiselect;
    protected $selected;

    /**
     * @param string $name control name
     * @param array $options should have two elements with keys pageid and courseid.
     */
    public function __construct($name, $options) {
        $this->multiselect = true;
        $this->rows = 10;
        $this->name = $name;
        $this->pageid = $options['pageid'];
        $this->courseid = $options['courseid'];
        $this->selection = array();
    }

    public function reload() {
        $this->selection = groups_get_all_groups($this->courseid);
    }

    /**
     * @return array of group objects. The groups that were selected. This is a more sophisticated version
     * of optional_param($this->name, array(), PARAM_INTEGER) that validates the
     * returned list of ids against the rules for this user selector.
     */
    public function get_selected_groups() {
        // Do a lazy load.
        if (is_null($this->selected)) {
            $this->selected = $this->load_selected_groups();
        }
        return $this->selected;
    }

    /**
     * Get the list of groups that were selected by doing optional_param then
     * validating the result.
     *
     * @return array of user objects.
     */
    protected function load_selected_groups() {
        global $DB;

        // See if we got anything.
        if ($this->multiselect) {
            $groupids = optional_param_array($this->name, array(), PARAM_INTEGER);
        } elseif ($groupid = optional_param($this->name, 0, PARAM_INTEGER)) {
            $groupids = array($groupid);
        }
        // If there are no groups there is nobody to load
        if (empty($groupids)) {
            return array();
        }

        $groups = array();
        foreach ($groupids as $gid) {
            $groups[$gid] = $DB->get_record('groups', array('id' => $gid));
        }

        // If we are only supposed to be selecting a single user, make sure we do.
        if (!$this->multiselect && count($groups) > 1) {
            $groups = array_slice($groups, 0, 1);
        }

        return $groups;
    }

    /**
     * If you update the database in such a way that it is likely to change the
     * list of groups that this component is allowed to select from, then you
     * must call this method.
     */
    public function invalidate_selected_groups() {
        $this->selected = null;
    }

    public function display() {

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="groupselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '">' . "\n";

        // Populate the select.
        foreach ($this->selection as $gid => $g) {
            $output .= "<option value=\"{$gid}\">{$g->name}</option>";
        }
        
        $output .= '</select>';

        echo $output;
    }
}

/**
 * User selector subclass for the list of groups assigned to a certain page.
 */
class page_group_selector extends page_group_selector_base {

    public function __construct($name = null, $options = null) {

        if (is_null($name)) {
            $name = 'removeselect';
        }
        parent::__construct($name, $options);

        $this->reload();
    }

    public function reload() {
        global $DB;
        
        $sql = "
            SELECT
                g.*
            FROM
                {groups} g,
                {format_page_access} fpa,
                {format_page} fp
            WHERE
                fp.id = fpa.pageid AND
                fp.courseid = ? AND
                fpa.policy = 'group' AND
                arg1int = g.id AND
                fp.id = ?
        ";

        $this->selection = $DB->get_records_sql($sql, array($this->courseid, $this->pageid));
    }
}

/**
 * User selector subclass for the list of groups not assigned to page.
 * Used on the add group members page.
 */
class page_non_group_selector extends page_group_selector_base {

    public function __construct($options) {

        parent::__construct('addselect', $options);

        $this->reload();
    }

    public function reload() {
        global $DB;

        parent::reload();

        $sql = "
            SELECT
                g.*
            FROM
                {groups} g,
                {format_page_access} fpa,
                {format_page} fp
            WHERE
                fp.id = fpa.pageid AND
                fp.courseid = ? AND
                fpa.policy = 'group' AND
                arg1int = g.id AND
                fp.id = ?
        ";

        if ($assigned = $DB->get_records_sql($sql, array($this->courseid, $this->pageid))) {
            foreach ($assigned as $gid => $gnotused) {
                unset($this->selection[$gid]);
            }
        }        
    }
}
