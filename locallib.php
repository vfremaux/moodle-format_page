<?php
/**
 * More internal functions we may need
 * These functions are essentially direct use and data 
 * extration from the underlying DB model, that will not
 * use object instance context to proceed.
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 * @version $Id: pagelib.php,v 1.12 2012-07-30 15:02:46 vf Exp $
 * @package format_page
 **/

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

    // load up the context for calling has_capability later
    $context = context_course::instance($courseid);

    // handle any actions that need to push a little state data to the session
    switch($action) {
        case'deletemod':
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            if (!isloggedin()) {
                // If on site page, then require_login may not be called
                // At this point, we make sure the user is logged in
                require_login($course->id);
            }
            if (has_capability('moodle/course:manageactivities', $context)) {
                // set some session stuff so we can find our way back to where we were
                $SESSION->cfp = new stdClass;
                $SESSION->cfp->action = 'finishdeletemod';
                $SESSION->cfp->deletemod = required_param('cmid', PARAM_INT);
                $SESSION->cfp->id = $courseid;
                // redirect to delete mod
                redirect($CFG->wwwroot.'/course/mod.php?delete='.$SESSION->cfp->deletemod.'&amp;sesskey='.sesskey());
            }
            break;
    }

    // handle any cleanup as a result of session being pushed from above block
    if (isset($SESSION->cfp)) {
        // the user did something we need to clean up after
        if (!empty($SESSION->cfp->action)) {
            switch ($SESSION->cfp->action) {
                case 'finishdeletemod':
                    if (!isloggedin()) {
                        // If on site page, then require_login may not be called
                        // At this point, we make sure the user is logged in
                        require_login($course->id);
                    }
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // Get what we need from session then unset it
                        $sessioncourseid = $SESSION->cfp->id;
                        $deletecmid      = $SESSION->cfp->deletemod;
                        unset($SESSION->cfp);

                        // See if the user deleted a module
                        if (!$DB->record_exists('course_modules', array('id' => $deletecmid))) {
                            // Looks like the user deleted this so clear out corresponding entries in format_page_items
                            if ($pageitems = $DB->get_records('format_page_items', array('cmid' => $deletecmid))) {
                                foreach ($pageitems as $pageitem) {
                                    $pageitemobj = new format_page_item($pageitem);
                                    $pageitemobj->delete();
                                }
                            }
                        }
                        if ($courseid == $sessioncourseid and empty($action) and !optional_param('page', 0, PARAM_INT)) {
                            // We are in same course and not performing another action or
                            // looking at a specific page, so redirect back to manage modules 
                            // for a nice workflow
                            $action = 'activities';
                        }
                    }
                    break;
                default:
                    // Doesn't match one of our handled session action hacks
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
function page_get_next_sortorder($courseid, $parent){
	global $DB;
	
	$maxsort = 0 + $DB->get_field('format_page', 'MAX(sortorder)', array('courseid' => $courseid, 'parent' => $parent));
	return $maxsort + 1;
}
