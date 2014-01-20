<?php
/**
 * Library of functions necessary for course format
 * 
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_page_uses_sections() {
    return false;
}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
function callback_page_ajax_support() {
    $ajaxsupport = new stdClass();
    $ajaxsupport->capable = false;
    $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
    return $ajaxsupport;
}

/**
 * Don't show the add block UI
 *
 * @return bool
 */
function callback_page_add_block_ui() {
    return false;
}

// Usefull event handler

/**
* This is an event handler registered for the mod_create event in course
* Conditions : be in page format for course, and having an awaiting to insert activity module
* in session.
*/
function format_page_mod_created_eventhandler($event){
	global $DB, $SESSION, $PAGE;
	
	// debug_trace('event catched mod_create '.$event->courseid.' '.$event->cmid);
	
	// check we are called in a course page format
	$format = $DB->get_field('course', 'format', array('id' => $event->courseid));
	if ($format != 'page') return;

	if (isset($SESSION->format_page_cm_insertion_page)){
		
		$pebm = new page_enabled_block_manager($PAGE);

		// build a page_block instance and feed it with the course module reference.
		// add page item consequently
		if ($instance = $pebm->add_block_at_end_of_page_region('page_module', $SESSION->format_page_cm_insertion_page)){
			$pageitem = $DB->get_record('format_page_items', array('blockinstance' => $instance->id));
			$DB->set_field('format_page_items', 'cmid', $event->cmid, array('id' => $pageitem->id));
		}

		// debug_trace('having new pageitem '.$pageitem->id);
		
		// now add cminstance id to configuration
		$block = block_instance('page_module', $instance);
		$block->config->cmid = $event->cmid;
		$block->instance_config_save($block->config);

		// release session marker		
		// debug_trace('end of sessionmark ');
		unset($SESSION->format_page_cm_insertion_page);
	}
}

/**
* This is an event handler registered for the mod_deleted event in course
* Conditions : be in page format for course
* Ensures all page_modules related tothis activity are properly removed
* Removes format_page_items accordingly
*/
function format_page_mod_deleted_eventhandler($event){
	global $DB, $SESSION, $PAGE;
	
	$pageitems = $DB->get_records('format_page_items', array('cmid' => $event->cmid));
	
	foreach($pageitems as $pi){
        $blockrec = $DB->get_record('block_instances', array('id' => $pi->blockinstance));
        $block = block_instance('page_module', $blockrec);

		// user_can_addto is not running on the actual block location PAGE, this could sometimes produce weird lockings
        if (!$block->user_can_edit() || !$PAGE->user_can_edit_blocks() || !$block->user_can_addto($PAGE)) {
            throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('deleteablock'));
        }

        blocks_delete_instance($block->instance);	        
		
	}

	// delete all related page items
    $DB->delete_records('format_page_items', array('cmid' => $event->cmid));
}

/**
* allows deleting additional format dedicated
* structures
* @param $int $courseid ID of the course being deleted
*/
function format_page_course_deleted_eventhandler($course){
	global $DB;
	
    $pages = $DB->get_records('format_page', array('courseid' => $course->id));
    if ($pages){
        foreach($pages as $page){
            $DB->delete_records('format_page_items', array('pageid' => $page->id));
            $DB->delete_records('format_page', array('id' => $page->id));
        }
    }
}

/**
 * Return a {@link block_contents} representing the add a new block UI, if
 * this user is allowed to see it.
 *
 * @return block_contents an appropriate block_contents, or null if the user
 * cannot add any blocks here.
 */
function format_page_block_add_block_ui($page, $output, $coursepage) {
    global $CFG, $OUTPUT;

    if (!$page->user_is_editing() || !$page->user_can_edit_blocks()) {
        return null;
    }

    $bc = new block_contents();
    $bc->title = get_string('addblock');
    $bc->add_class('block_adminblock');

    $missingblocks = $page->blocks->get_addable_blocks();
    if (empty($missingblocks)) {
        $bc->content = get_string('noblockstoaddhere');
        return $bc;
    }
    
    $menu = array();
    foreach ($missingblocks as $block) {
        $blockobject = block_instance($block->name);
        if ($blockobject !== false && $blockobject->user_can_addto($page)) {
            $menu[$block->name] = $blockobject->get_title();
        }
    }
    collatorlib::asort($menu);
    
    $actionurl = new moodle_url($page->url, array('sesskey'=>sesskey()));
    $select = new single_select($actionurl, 'bui_addblock', $menu, null, array(''=>get_string('addblock', 'format_page')), 'add_block');
    $bc->content = $OUTPUT->render($select);
    return $bc;
}

/**
 * Deletes a page deleting non-referenced block instances, all page items for that page,
 * and deleting all sub pages
 *
 * @param int $pageid The page ID of the page to delte
 **/
function page_delete_page($pageid) {
	global $DB;
	
    if (!$page = course_page::get($pageid)) {
        print_error('errorpageid', 'format_page');
    }
    $page->delete_all_blocks();


    // Need to get the page out of there so we can get
    // proper sortorder value for children
    if (! $DB->delete_records('format_page', array('id' => $page->id))){
    	return false;
    }
    
    // fix sort order for all brother pages
	$sql = "
		UPDATE
			{format_page}
		SET
			sortorder = sortorder - 1
		WHERE
			courseid = ? AND
			parent = ? AND
			sortorder > ?
    ";
	$DB->execute($sql, array($page->courseid, $page->parent, $page->sortorder));

    // Now remap the parent id and sortorder of all the brother pages.
    if ($children = $DB->get_records('format_page', array('parent' => $pageid), 'sortorder', 'id')) {

        foreach($children as $child) {
        	page_delete_page($child->id);
        }
    }
    
    $landing = course_page::get_default_page($page->courseid);

    return $landing->id;
}

/**
* prints a generic pager using the from param to control offset
*/
function page_print_pager($offset, $page, $maxobjects, $url){
    global $CFG;
    if ($maxobjects <= $page) return;
    $current = ceil(($offset + 1) / $page);
    $pages = array();
    $off = 0;    

    for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++){
        if ($p == $current){
            $pages[] = $p;
        } else {
            $pages[] = "<a href=\"{$url}&from={$off}\">{$p}</a>";
        }
        $off = $off + $page;    
    }    
    echo implode(' - ', $pages);
}

///////////////////////// Deprecated functions
// @see the format_page class

/** 
 * DEPRECATED : page_items sortorders not in use anymore.
 * Function returns the next sortorder value for a particular page column combo
 *
 * @param int $pageid Page id
 * @param string $position The column get the next weight for
 * @return int next sortorder 
 * @uses $CFG
 */
 /*
function page_get_next_weight($pageid, $position) {
    global $CFG, $DB;

    $weight = $DB->get_record_sql("SELECT 1, MAX(sortorder) + 1 as nextfree
                                FROM {format_page_items}
                               WHERE pageid = $pageid
                                 AND position = '$position'");

    if (empty($weight->nextfree)) {
        $weight->nextfree = 0;
    }

    return $weight->nextfree;
}
*/


/**
 * This gets the full child tree of the passed page.
 *
 * @param object $pageid Page ID to return children for
 * @param string $structure Structure of the tree, EG: flat or nested
 * @param int $courseid (Optional) ID of the current course
 * @return array
 */
 /*
function page_get_children($pageid, $structure = 'flat', $courseid = NULL) {
    global $COURSE;

    $children = array();

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

    if ($allpages = format_page::get_all_pages($courseid, 'flat')) {

        switch ($structure) {
            case 'flat':
                // Loop through the pages until we find the passed
                // pageid and then collect its children
                $found = false;
                foreach ($allpages as $page) {
                    if ($page->id == $pageid) {
                        $found = true;
                        $depth = $page->depth;
                        // Don't include this one, skip it
                        continue;
                    }
                    if ($found) {
                        if ($page->depth <= $depth) {
                            // Not a child, break
                            break;
                        }
                        $children[$page->id] = $page;
                    }
                }
                break;
            case 'nested':
                // Find the parent page IDs
                $parentids = array();
                while ($pageid != 0 and !empty($allpages[$pageid])) {
                    array_unshift($parentids, $pageid);
                    $pageid = $allpages[$pageid]->parent;
                }
                // Dig down through the parents to get the children
                $children = format_page::get_all_pages($courseid, 'nested');
                foreach ($parentids as $pageid) {
                    $children = $children[$pageid]->children;
                }
                break;
        }
    }

    return $children;
}
*/

/**
 * This function returns a number of "theme" pages that are first in the sortorder
 * 
 * @param int $courseid the course id ot get pages from
 * @param int $limit (optional) the maximum number of pages to return (0 meaning no limit);
 * @return array of pages
 */
 /*
function page_get_theme_pages($courseid, $limit=0) {
    return page_get_master_pages($courseid, $limit, DISP_THEME | DISP_PUBLISH);
}
*/

/**
 * This function returns a number of "menu" pages that are first in the sortorder
 * 
 * @param int $courseid the course id ot get pages from
 * @param int $limit (optional) the maximum number of pages to return (0 meaning no limit);
 * @return array of pages
 */
 /*
function page_get_menu_pages($courseid, $limit=0) {
    return page_get_master_pages($courseid, $limit, DISP_MENU | DISP_PUBLISH);
}
*/

/**
 * Finds the top-level parent page of a given page ID.  If a page has no parent(s)
 * then it will return the same page.
 *
 * @param int $pageid The page ID of the page you want to find the parent of
 * @param int $courseid (Optional) ID of the course that the page is in
 * @return object if toplevel parent exists else returns false for no parent
 **/
 /*
function page_get_toplevel_parent($pageid, $courseid = NULL) {
    if ($courseid === NULL) {
        if (!$courseid = $DB->get_field('format_page', 'courseid', array('id' => $pageid))) {
            print_error('errorpageid', 'format_page');
        }
    }
    $page     = false;
    $allpages = format_page::get_all_pages($courseid, 'flat');

    while ($pageid != 0 and !empty($allpages[$pageid])) {
        $page   = $allpages[$pageid];
        $pageid = $allpages[$pageid]->parent;
    }

    return $page;
}
*/


/**
 * This function returns the page objects of the next/previous pages
 * relative to the passed page ID
 *
 * @param int $pageid Base next/previous off of this page ID
 * @param int $courseid (Optional) ID of the course
 */
 /*
function page_get_next_previous_pages($pageid, $courseid = NULL) {
    global $COURSE, $PAGE;

    if ($courseid === NULL) {
        $courseid = $COURSE->id;
    }

    $return = new stdClass;
    $return->prev = false;
    $return->next = false;

    if ($pages = format_page::get_all_pages($courseid, 'flat')) {
        // Remove any unpublished pages
        foreach ($pages as $id => $page) {
            if (!($page->display & DISP_PUBLISH) && !$PAGE->user_is_editing() && $pageid != $page->id) {
                unset($pages[$id]);
            }
        }
        if (!empty($pages)) {
            // Search for the pages
            $get = false;
            foreach($pages as $id => $page) {
                if ($get) {
                    // We have seen the id we're looking for
                    $return->next = $page;
                    break;  // quit this business
                }
                if ($id == $pageid) {
                    // We've found the id that we are looking for
                    $get = true;
                }
                if (!$get) {
                    // Only if we haven't found what we're looking for
                    $return->prev = $page;
                }
            }
        }
    }

    return $return;
}
*/

/**
 * This function is called when printing the format
 * in /index.php
 *
 * @return void
 **/
function page_frontpage() {
    // Get all of the standard globals - the format script is usually included
    // into a file that has called config.php
    global $CFG, $PAGE, $USER, $SESSION, $COURSE, $SITE;

    $course = get_site();

    if (has_capability('moodle/course:update', context_course::instance(SITEID))) {
        echo '<div style="text-align:right">'.update_course_icon($course->id).'</div>';
    }
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->dirroot.'/course/format/page/format.php');
    echo $OUTPUT->footer('home');
    die;
}

// format page representation

class format_page extends format_base{

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Page format uses the following options:
     * - usesindividualization
     * - usespagediscussions
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'usesindividualization' => array(
                    'default' => 0,
                    'type' => PARAM_BOOL,
                ),
                'usespagediscussions' => array(
                    'default' => 1,
                    'type' => PARAM_BOOL,
                ),
            );
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $yesnnomenu = array(0 => new lang_string('no'), 1 => new lang_string('yes'));
            $courseformatoptionsedit = array(
                'usesindividualization' => array(
                    'label' => get_string('usesindividualization', 'format_page'),
                    'help' => 'pageindividualization',
                    'help_component' => 'format_page',
                    'element_type' => 'select',
                    'element_attributes' => array($yesnnomenu),
                ),
                'usespagediscussions' => array(
                    'label' => get_string('usespagediscussions', 'format_page'),
                    'help' => 'pagediscussions',
                    'help_component' => 'format_page',
                    'element_type' => 'select',
                    'element_attributes' => array($yesnnomenu)
                ),
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'weeks', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * If previous course format did not have 'numsections' option, we populate it with the
     * current number of sections
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        if ($oldcourse !== null) {
            $data = (array)$data;
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        // If previous format does not have the field 'numsections'
                        // and $data['numsections'] is not set,
                        // we fill it with the maximum section number from the DB
                        $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                            WHERE course = ?', array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default
                            $data['numsections'] = $maxsection;
                        }
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }
	
	/**
	* We need all all pages tree as sections
	*
	*/
	function extend_course_navigation($navigation, navigation_node $coursenode){
        if ($course = $this->get_course()) {
        	
        	$context = context_course::instance($course->id);

        	$currentpage = course_page::get_current_page($course->id);

			$allpages = course_page::get_all_pages($course->id, 'nested');
			
			// this deals with first level
			if ($allpages){
				foreach($allpages as $page){
					$this->extend_page_navigation($navigation, $coursenode, $page, $currentpage, $context);
				}
			}
        }
        return array();
	}

	/**
	* recursive scandown for sub pages
	*
	*/
	function extend_page_navigation(&$navigation, navigation_node &$uppernode, &$page, &$currentpage, $context){

		if (!has_capability('format/page:viewhiddenpages', $context) && !$page->is_visible()) continue;

		$url = $page->url_build('page', $page->id);
     	$pagenode = $uppernode->add($page->get_name(), $url, navigation_node::TYPE_SECTION, null, $page->id);		     	
    	$pagenode->hidden = !$page->is_visible();
		$pagenode->nodetype = navigation_node::NODETYPE_BRANCH;
		if ($children = $page->get_children()){
			foreach($children as $ch){
				$this->extend_page_navigation($navigation, $pagenode, $ch, $currentpage, $context);
			}
		}
		// scan all page tree and make nodes If page is current, deploy in page activites
		// echo "($currentpage->id == $page->id)";
        if ($currentpage !== false && ($currentpage->id == $page->id)) {
        	$activities = $page->get_activities();
        	// print_object($activities);
        	// use a fake sectionnumber for all activities in page....
            $this->load_section_activities($pagenode, 0, $activities);
        }
	}

    /**
     * Loads all of the activities for a section into the navigation structure.
     *
     * @param navigation_node $sectionnode
     * @param int $sectionnumber
     * @param array $activities An array of activites as returned by {@link global_navigation::generate_sections_and_activities()}
     * @param stdClass $course The course object the section and activities relate to.
     * @return array Array of activity nodes
     */
    protected function load_section_activities(navigation_node $sectionnode, $sectionnumber, array $activities, $course = null) {
        global $CFG, $SITE;
        // A static counter for JS function naming
        static $legacyonclickcounter = 0;

        $activitynodes = array();
        if (empty($activities)) {
            return $activitynodes;
        }

        if (!is_object($course)) {
            $activity = reset($activities);
            $courseid = $activity->course;
        } else {
            $courseid = $course->id;
        }
        $showactivities = ($courseid != $SITE->id || !empty($CFG->navshowfrontpagemods));

        foreach ($activities as $activity) {
            if ($activity->section != $sectionnumber) {
                continue;
            }
            if ($activity->icon) {
                $icon = new pix_icon($activity->icon, get_string('modulename', $activity->modname), $activity->iconcomponent);
            } else {
                $icon = new pix_icon('icon', get_string('modulename', $activity->modname), $activity->modname);
            }

            // Prepare the default name and url for the node
            $activityname = format_string($activity->name, true, array('context' => context_module::instance($activity->id)));
            $action = new moodle_url($activity->url);

            // Check if the onclick property is set (puke!)
            if (!empty($activity->onclick)) {
                // Increment the counter so that we have a unique number.
                $legacyonclickcounter++;
                // Generate the function name we will use
                $functionname = 'legacy_activity_onclick_handler_'.$legacyonclickcounter;
                $propogrationhandler = '';
                // Check if we need to cancel propogation. Remember inline onclick
                // events would return false if they wanted to prevent propogation and the
                // default action.
                if (strpos($activity->onclick, 'return false')) {
                    $propogrationhandler = 'e.halt();';
                }
                // Decode the onclick - it has already been encoded for display (puke)
                $onclick = htmlspecialchars_decode($activity->onclick);
                // Build the JS function the click event will call
                $jscode = "function {$functionname}(e) { $propogrationhandler $onclick }";
                $this->page->requires->js_init_code($jscode);
                // Override the default url with the new action link
                $action = new action_link($action, $activityname, new component_action('click', $functionname));
            }

            $activitynode = $sectionnode->add($activityname, $action, navigation_node::TYPE_ACTIVITY, null, $activity->id, $icon);
            $activitynode->title(get_string('modulename', $activity->modname));
            $activitynode->hidden = $activity->hidden;
            $activitynode->display = $showactivities && $activity->display;
            $activitynode->nodetype = $activity->nodetype;
            $activitynodes[$activity->id] = $activitynode;
        }

        return $activitynodes;
    }

}

/**
 * Serves the format page context (course context) attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function format_page_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    require_course_login($course);

    $fileareas = array('discussion');
    $areastotables = array('discussion' => 'format_page_discussion');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }
    
    $relatedtable = $areastotables[$filearea];

    $pageid = (int)array_shift($args);

	$page = course_page::load($pageid);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/format_page/$filearea/$pageid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    
    // Make sure groups allow this user to see this file
    if (!has_capability('format/page:discuss', $context)){
    	return false;
    }
    
    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}
