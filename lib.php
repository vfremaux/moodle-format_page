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
 * Library of functions necessary for course format
 * 
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/lib.php');

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

// Usefull event handler.

/**
 * This is an event handler registered for the mod_create event in course
 * Conditions : be in page format for course, and having an awaiting to insert activity module
 * in session.
 * @param object $event
 */
function format_page_mod_created_eventhandler($event) {
    global $DB, $SESSION, $PAGE;

    // Check we are called in a course page format.
    $format = $DB->get_field('course', 'format', array('id' => $event->courseid));
    if ($format != 'page') {
        return;
    }

    if (isset($SESSION->format_page_cm_insertion_page)) {

        $pebm = new page_enabled_block_manager($PAGE);

        // Build a page_block instance and feed it with the course module reference.
        // Add page item consequently.
        if ($instance = $pebm->add_block_at_end_of_page_region('page_module', $SESSION->format_page_cm_insertion_page)) {
            $pageitem = $DB->get_record('format_page_items', array('blockinstance' => $instance->id));
            $DB->set_field('format_page_items', 'cmid', $event->cmid, array('id' => $pageitem->id));
        }

        // Now add cminstance id to configuration.
        $block = block_instance('page_module', $instance);
        $block->config->cmid = $event->cmid;
        $block->instance_config_save($block->config);

        // Finally ensure course module is visible.
        $DB->set_field('course_modules', 'visible', 1, array('id' => $event->cmid));

        // Release session marker.
        unset($SESSION->format_page_cm_insertion_page);
    }
}

/**
 * This is an event handler registered for the mod_deleted event in course
 * Conditions : be in page format for course
 * Ensures all page_modules related tothis activity are properly removed
 * Removes format_page_items accordingly
 * @param object $event
 */
function format_page_mod_deleted_eventhandler($event) {
    global $DB, $SESSION, $PAGE;

    $pageitems = $DB->get_records('format_page_items', array('cmid' => $event->cmid));

    foreach ($pageitems as $pi) {
        $blockrec = $DB->get_record('block_instances', array('id' => $pi->blockinstance));
        $block = block_instance('page_module', $blockrec);

        // User_can_addto is not running on the actual block location PAGE, this could sometimes produce weird lockings.
        if (!$block->user_can_edit() || !$PAGE->user_can_edit_blocks() || !$block->user_can_addto($PAGE)) {
            throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('deleteablock'));
        }

        blocks_delete_instance($block->instance);
    }

    // Delete all related page items.
    $DB->delete_records('format_page_items', array('cmid' => $event->cmid));
}

/**
 * allows deleting additional format dedicated
 * structures
 * @param object $course the course being deleted
 */
function format_page_course_deleted_eventhandler($course) {
    global $DB;

    $pages = $DB->get_records('format_page', array('courseid' => $course->id));
    if ($pages) {
        foreach ($pages as $page) {
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
 * @param object $page
 * @param object $output
 * @param object $coursepage
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

    $actionurl = new moodle_url($page->url, array('sesskey' => sesskey()));
    $select = new single_select($actionurl, 'bui_addblock', $menu, null, array('' => get_string('addblock', 'format_page')), 'add_block');
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
    $page->delete_section();

    // Need to get the page out of there so we can get proper sortorder value for children.
    if (! $DB->delete_records('format_page', array('id' => $page->id))) {
        return false;
    }

    // Fix sort order for all brother pages.
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

        foreach ($children as $child) {
            page_delete_page($child->id);
        }
    }

    $landing = course_page::get_default_page($page->courseid);

    return $landing->id;
}

/**
 * Prints a generic pager using the from param to control offset
 * @param int $offset the amount of page to start after
 * @param int $page the currentpage we are in
 * @param int $maxobjects the maximum number of objects in the list
 * @param string $url the base URL to return to
 */
function page_print_pager($offset, $page, $maxobjects, $url) {
    global $CFG;

    if ($maxobjects <= $page) {
        return;
    }
    $current = ceil(($offset + 1) / $page);
    $pages = array();
    $off = 0;

    for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++) {
        if ($p == $current) {
            $pages[] = $p;
        } else {
            $pages[] = "<a href=\"{$url}&from={$off}\">{$p}</a>";
        }
        $off = $off + $page;
    }
    echo implode(' - ', $pages);
}

/**
 * This function is called when printing the format
 * in /index.php
 *
 * @return void
 **/
function page_frontpage() {
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

// Format page representation.

class format_page extends format_base {

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
    function extend_course_navigation($navigation, navigation_node $coursenode) {
        if ($course = $this->get_course()) {

            $context = context_course::instance($course->id);

            $currentpage = course_page::get_current_page($course->id);

            $allpages = course_page::get_all_pages($course->id, 'nested');

            // This deals with first level.
            if ($allpages) {
                foreach ($allpages as $page) {
                    $this->extend_page_navigation($navigation, $coursenode, $page, $currentpage, $context);
                }
            }
        }
        return array();
    }

    /**
     * Returns the display name of the given section that the course prefers.
     * In flexipage, section is equivalent to page
     * @param int|stdClass $section Section object from database or just field course_sections.section
     * @return Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        
        if (is_object($section)) {
            $sectionnum = $section->section;
            $sectioname = $section->name;
        } else {
            $sectionnum = $section;
        }
        if($page = course_page::get_by_section($sectionnum)){
            $sectionname = $page->nametwo;
        }

        if (empty($sectionname)) {
            $sectionname = get_string('section').' '.$sectionnum;
        }
        return $sectionname;
    }

    /**
     * recursive scandown for sub pages
     */
    function extend_page_navigation(&$navigation, navigation_node &$uppernode, &$page, &$currentpage, $context) {

        if (!has_capability('format/page:viewhiddenpages', $context) && !$page->is_visible()) {
            return;
        }

        if ($page->id != $page->id) {
            $url = $page->url_build('page', $page->id);
            $pagenode = $uppernode->add($page->get_name(), $url, navigation_node::TYPE_SECTION, null, $page->id);
            $pagenode->hidden = !$page->is_visible();
            $pagenode->nodetype = navigation_node::NODETYPE_BRANCH;
            if ($children = $page->get_children()) {
                foreach ($children as $ch) {
                    $this->extend_page_navigation($navigation, $pagenode, $ch, $currentpage, $context);
                }
            }
        } else {
            $pagenode = $uppernode;
        }

        // Scan all page tree and make nodes If page is current, deploy in page activites.
        // TODO check how to get a page node without adding to navigation
        /*
        if ($currentpage !== false && ($currentpage->id == $page->id)) {
            $activities = $page->get_activities();
            // Use a fake sectionnumber for all activities in page...
            $this->load_section_activities($pagenode, 0, $activities);
        }
        */
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

            // Prepare the default name and url for the node.
            $activityname = format_string($activity->name, true, array('context' => context_module::instance($activity->id)));
            $action = new moodle_url($activity->url);

            // Check if the onclick property is set (puke!).
            if (!empty($activity->onclick)) {
                // Increment the counter so that we have a unique number.
                $legacyonclickcounter++;
                // Generate the function name we will use.
                $functionname = 'legacy_activity_onclick_handler_'.$legacyonclickcounter;
                $propogrationhandler = '';

                /*
                 * Check if we need to cancel propogation. Remember inline onclick
                 * events would return false if they wanted to prevent propogation and the
                 * default action.
                 */
                if (strpos($activity->onclick, 'return false')) {
                    $propogrationhandler = 'e.halt();';
                }

                // Decode the onclick - it has already been encoded for display (puke).
                $onclick = htmlspecialchars_decode($activity->onclick);

                // Build the JS function the click event will call.
                $jscode = "function {$functionname}(e) { $propogrationhandler $onclick }";
                $this->page->requires->js_init_code($jscode);

                // Override the default url with the new action link.
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

    // Make sure groups allow this user to see this file.
    if (!has_capability('format/page:discuss', $context)) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
}

/**
 * fix width when editing by letting no column, at null width.
 */
function format_page_fix_editing_width(&$prewidthspan, &$mainwidthspan, &$postwidthspan) {

    $max = 0;
    if ($prewidthspan > $max) {
        $max = $prewidthspan;
        $maxvar = 'prewidthspan';
    } else {
        if (!$prewidthspan) {
            $nulls[] = 'prewidthspan';
        }
    }
    if ($mainwidthspan > $max) {
        $max = $mainwidthspan;
        $maxvar = 'mainwidthspan';
    } else {
        if (!$mainwidthspan) {
            $nulls[] = 'mainwidthspan';
        }
    }
    if ($postwidthspan > $max) {
        $maxvar = 'postwidthspan';
    } else {
        if (!$postwidthspan) {
            $nulls[] = 'postwidthspan';
        }
    }

    $classes = array();
    if (!empty($nulls)) {
        foreach($nulls as $null) {
            $$null+=2;
            $$maxvar-=2;
            $classes[$null] = 'no-width';
        }
    }
    
    return $classes;
}

function format_page_is_bootstrapped() {
    global $PAGE;
    
    return in_array('bootstrapbase', $PAGE->theme->parents) || in_array('clean', $PAGE->theme->parents) || preg_match('/bootstrap|essential/', $PAGE->theme->name);
}