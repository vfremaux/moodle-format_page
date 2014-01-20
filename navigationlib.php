<?php

require_once $CFG->dirroot.'/course/format/page/page.class.php';

/**
 * The global navigation class used especially for AJAX requests.
 *
 * The primary methods that are used in the global navigation class have been overriden
 * to ensure that only the relevant branch is generated at the root of the tree.
 * This can be done because AJAX is only used when the backwards structure for the
 * requested branch exists.
 * This has been done only because it shortens the amounts of information that is generated
 * which of course will speed up the response time.. because no one likes laggy AJAX.
 *
 * @package   core
 * @category  navigation
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_page_navigation_for_ajax extends global_navigation_for_ajax {

    /**
     * Constructs the navigation for use in an AJAX request
     *
     * @param moodle_page $page moodle_page object
     * @param int $branchtype
     * @param int $id
     */
    public function __construct($page, $branchtype, $id) {
    	parent::__construct($page, $branchtype, $id);
    }
    /**
     * Initialise the navigation given the type and id for the branch to expand.
     *
     * @return array An array of the expandable nodes
     */
    public function initialise() {
        global $DB, $SITE;

        if ($this->initialised || during_initial_install()) {
            return $this->expandable;
        }
        $this->initialised = true;

        $this->rootnodes = array();
        $this->rootnodes['site']    = $this->add_course($SITE);
        $this->rootnodes['mycourses'] = $this->add(get_string('mycourses'), new moodle_url('/my'), self::TYPE_ROOTNODE, null, 'mycourses');
        $this->rootnodes['courses'] = $this->add(get_string('courses'), null, self::TYPE_ROOTNODE, null, 'courses');

        // Branchtype will be one of navigation_node::TYPE_*
        switch ($this->branchtype) {
            case 0:
                if ($this->instanceid === 'mycourses') {
                    $this->load_courses_enrolled();
                } else if ($this->instanceid === 'courses') {
                    $this->load_courses_other();
                }
                break;
            case self::TYPE_CATEGORY :
                $this->load_category($this->instanceid);
                break;
            case self::TYPE_COURSE :
                $course = $DB->get_record('course', array('id' => $this->instanceid), '*', MUST_EXIST);
                require_course_login($course, true, null, false, true);
                $this->page->set_context(context_course::instance($course->id));
                $coursenode = $this->add_course($course);
                $this->add_course_essentials($coursenode, $course);
                $this->load_course_sections($course, $coursenode);
                break;
            case self::TYPE_SECTION : // Section is shifted to page concept
                $sql = 'SELECT c.*, fp.id AS pageid
                        FROM {course} c
                        LEFT JOIN {format_page} fp ON fp.courseid = c.id
                        WHERE fp.id = ?';
                
                $page = course_page::get($this->instanceid);
                $course = $DB->get_record_sql($sql, array($this->instanceid), MUST_EXIST);
        		$modinfo = get_fast_modinfo($course); // original info is better to take here
                require_course_login($course, true, null, false, true);
                $this->page->set_context(context_course::instance($course->id));
                $coursenode = $this->add_course($course);
                $this->add_course_essentials($coursenode, $course);
                if ($activities = $page->get_activities()){
                	foreach($activities as $key => $cm){
		                $cm = $modinfo->cms[$key];
		                if (!$cm->uservisible) {
		                    continue;
		                }
		                $activity = new stdClass;
		                $activity->id = $cm->id;
		                $activity->course = $course->id;
		                $activity->section = 0;
		                $activity->name = $cm->name;
		                $activity->icon = $cm->icon;
		                $activity->iconcomponent = $cm->iconcomponent;
		                $activity->hidden = (!$cm->visible);
		                $activity->modname = $cm->modname;
		                $activity->nodetype = navigation_node::NODETYPE_LEAF;
		                $activity->onclick = $cm->get_on_click();
		                $url = $cm->get_url();
		                if (!$url) {
		                    $activity->url = null;
		                    $activity->display = false;
		                } else {
		                    $activity->url = $cm->get_url()->out();
		                    $activity->display = true;
		                    if (self::module_extends_navigation($cm->modname)) {
		                        $activity->nodetype = navigation_node::NODETYPE_BRANCH;
		                    }
		                }
		                $activities[$key] = $activity;
		            }

	                $this->load_course_sections($course, $coursenode, 0, $activities);
	            }
                break;
            case self::TYPE_ACTIVITY :
                $sql = "SELECT c.*
                          FROM {course} c
                          JOIN {course_modules} cm ON cm.course = c.id
                         WHERE cm.id = :cmid";
                $params = array('cmid' => $this->instanceid);
                $course = $DB->get_record_sql($sql, $params, MUST_EXIST);
                $modinfo = get_fast_modinfo($course);
                $cm = $modinfo->get_cm($this->instanceid);
                require_course_login($course, true, $cm, false, true);
                $this->page->set_context(context_module::instance($cm->id));
                $coursenode = $this->load_course($course);
                if ($course->id != $SITE->id) {
                    $this->load_course_sections($course, $coursenode, null, $cm);
                }
                $modulenode = $this->load_activity($cm, $course, $coursenode->find($cm->id, self::TYPE_ACTIVITY));
                break;
            default:
                throw new Exception('Unknown type');
                return $this->expandable;
        }

        if ($this->page->context->contextlevel == CONTEXT_COURSE && $this->page->context->instanceid != $SITE->id) {
            $this->load_for_user(null, true);
        }

        $this->find_expandable($this->expandable);
        return $this->expandable;
    }

    /**
     * Returns an array of expandable nodes
     * @return array
     */
    public function get_expandable() {
        return $this->expandable;
    }
}
