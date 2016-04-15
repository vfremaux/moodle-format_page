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
 * More internal functions we may need
 * These functions are essentially direct use and data 
 * extration from the underlying DB model, that will not
 * use object instance context to proceed.
 *
 * @author Mark Nielsen
 * @author for Moodle 2 Valery Fremaux
 * @version $Id: pagelib.php,v 1.12 2012-07-30 15:02:46 vf Exp $
 * @package format_page
 * @category format
 */

/**
 * this function handles all of the necessary session hacks needed by the page course format
 *
 * @param int $courseid course id (used to ensure user has proper capabilities)
 * @param string the action the user is performing
 * @uses $SESSION
 * @uses $USER
 */
function page_handle_session_hacks($page, $courseid, $action) {
    global $SESSION, $USER, $CFG, $DB;

    // Load up the context for calling has_capability later.
    $context = context_course::instance($courseid);

    // Handle any actions that need to push a little state data to the session.
    switch($action) {
        case 'deletemod':
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            if (!isloggedin()) {
                // If on site page, then require_login may not be called.
                // At this point, we make sure the user is logged in.
                require_login($course->id);
            }
            if (has_capability('moodle/course:manageactivities', $context)) {
                // Set some session stuff so we can find our way back to where we were.
                $SESSION->cfp = new stdClass;
                $SESSION->cfp->action = 'finishdeletemod';
                $SESSION->cfp->deletemod = required_param('cmid', PARAM_INT);
                $SESSION->cfp->id = $courseid;
                // Redirect to delete mod.
                redirect(new moodle_url('/course/mod.php', array('delete' => $SESSION->cfp->deletemod, 'sesskey' => sesskey())));
            }
            break;
    }

    // Handle any cleanup as a result of session being pushed from above block.
    if (isset($SESSION->cfp)) {
        // The user did something we need to clean up after.
        if (!empty($SESSION->cfp->action)) {
            switch ($SESSION->cfp->action) {
                case 'finishdeletemod':
                    if (!isloggedin()) {
                        // If on site page, then require_login may not be called.
                        // At this point, we make sure the user is logged in.
                        require_login($course->id);
                    }
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // Get what we need from session then unset it.
                        $sessioncourseid = $SESSION->cfp->id;
                        $deletecmid = $SESSION->cfp->deletemod;
                        unset($SESSION->cfp);

                        // See if the user deleted a module.
                        if (!$DB->record_exists('course_modules', array('id' => $deletecmid))) {
                            // Looks like the user deleted this so clear out corresponding entries in format_page_items.
                            if ($pageitems = $DB->get_records('format_page_items', array('cmid' => $deletecmid))) {
                                foreach ($pageitems as $pageitem) {
                                    $pageitemobj = new format_page_item($pageitem);
                                    $pageitemobj->delete();
                                }
                            }
                        }
                        if ($courseid == $sessioncourseid and empty($action) and !optional_param('page', 0, PARAM_INT)) {
                            /*
                             * We are in same course and not performing another action or
                             * looking at a specific page, so redirect back to manage modules 
                             * for a nice workflow.
                             */
                            $action = 'activities';
                        }
                    }
                    break;
                default:
                    // Doesn't match one of our handled session action hacks.
                    unset($SESSION->cfp);
                    break;
            }
        }
    }

    return $action;
}

/**
 *
 *
 */
function page_get_next_sortorder($courseid, $parent) {
    global $DB;
    
    $maxsort = 0 + $DB->get_field('format_page', 'MAX(sortorder)', array('courseid' => $courseid, 'parent' => $parent));
    return $maxsort + 1;
}
