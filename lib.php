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
 * @package     format_page
 * @category    format
 * @author      Jeff Graham, Mark Nielsen
 * @author      Valery fremaux (valery.fremaux@gmail.com)
 * @copyright   2008 Valery Fremaux (Edunao.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/lib.php');

global $PAGE;
if (is_dir($CFG->dirroot.'/local/vflibs')) {
    if ($PAGE->state == 0) {
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('sparklines', 'local_vflibs');
    }
}

/**
 * This function is not implemented in this plugin, but is needed to mark
 * the vf documentation custom volume availability.
 */
function format_page_supports_feature() {
    assert(1);
}

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
function format_page_block_add_block_ui($page) {
    global $OUTPUT, $DB;

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
        // CHANGE+.
        $params = array('type' => 'block', 'plugin' => $block->name);
        $familyname = $DB->get_field('format_page_plugins', 'familyname', $params);
        if ($familyname) {
            $family = get_string('pfamily'.$familyname,'format_page' );
        } else {
            $family = get_string('otherblocks', 'format_page');
        }
        // CHANGE-.
        $blockobject = block_instance($block->name);
        if ($blockobject !== false && $blockobject->user_can_addto($page)) {
            $menu[$family][$block->name] = $blockobject->get_title();
        }
    }
    $i = 0;
    $selectmenu = array();
    foreach ($menu as $f => $m) {
        $selectmenu[$i][$f] = $m;
        $i++;
    }

    $actionurl = new moodle_url($page->url, array('sesskey' => sesskey()));
    $nochoice = array('' => get_string('addblock', 'format_page'));
    $select = new single_select($actionurl, 'bui_addblock', $selectmenu, null, $nochoice, 'add_block');
    $select->set_help_icon('blocks', 'format_page');
    $bc->content = $OUTPUT->render($select);
    return $bc;
}

/**
 * Deletes a page deleting non-referenced block instances, all page items for that page,
 * and deleting all sub pages
 *
 * @param int $pageid The page ID of the page to delte
 */
function page_delete_page($pageid) {
    global $DB;

    if (!$page = course_page::get($pageid)) {
        print_error('errorpageid', 'format_page');
    }
    $page->delete_all_blocks();
    $page->delete_section();

    // Need to get the page out of there so we can get proper sortorder value for children.
    if (!$DB->delete_records('format_page', array('id' => $page->id))) {
        return false;
    }

    // Fix sort order for all brother pages.
    page_update_page_sortorder($page->courseid, $page->parent, $page->sortorder);

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

    if ($maxobjects <= $page) {
        return;
    }
    $current = ceil(($offset + 1) / $page);
    $pages = array();
    $off = 0;

    for ($p = 1; $p <= ceil($maxobjects / $page); $p++) {
        if ($p == $current) {
            $pages[] = $p;
        } else {
            $pages[] = "<a href=\"{$url}&from={$off}\">{$p}</a>";
        }
        $off = $off + $page;
    }
    echo implode(' - ', $pages);
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
            $data = (array) $data;
            $oldcourse = (array) $oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        /*
                         * If previous format does not have the field 'numsections'
                         * and $data['numsections'] is not set,
                         * we fill it with the maximum section number from the DB
                         */
                        $sql = '
                            SELECT
                                max(section)
                            FROM
                                {course_sections}
                            WHERE
                                course = ?
                        ';
                        $maxsection = $DB->get_field_sql($sql, array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default.
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
            $sectionname = $section->name;
        } else {
            $sectionnum = $section;
        }
        if ($page = course_page::get_by_section($sectionnum)) {
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

        // A static counter for JS function naming.
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

    /**
     * @global type $CFG
     * @param type $action
     * @param type $customdata
     * @return \pageeditsection_form
     */
    public function editsection_form($action, $customdata = array()) {
        global $CFG;

        include_once($CFG->dirroot.'/course/format/page/forms/editsection_form.php');
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }
        return new page_editsection_form($action, $customdata);
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
    global $CFG, $DB;

    if (($filearea == 'intro') && ($course->format == 'page')) {
        // Exceptionnnaly we let pass without control the course modules context queries to intro files.
        // We allow format_page component pages which real component identity is given by the context id.

        include_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
        if (!course_page::check_page_public_accessibility($course)) {
            // Process as usual.
            require_course_login($course);
        }
        $fs = get_file_storage();

        // Seek for the real component hidden beside the context.
        $cm = $DB->get_record('course_modules', array('id' => $context->instanceid));
        $component = 'mod_'.$DB->get_field('modules', 'name', array('id' => $cm->module));
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/$component/$filearea/$relativepath";
        // echo $fullpath;
        if ((!$file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
        die;
    }

    $fileareas = array('discussion', 'pagerendererimages');
    $areastotables = array('discussion' => 'format_page_discussion');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    if ($filearea == 'pagerendererimages') {
        $context = context_system::instance();
    } else {
        if ($context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        require_course_login($course);
    }

    $pageid = (int) array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/format_page/$filearea/$pageid/$relativepath";
    if ((!$file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    // Make sure groups allow this user to see this file.
    if (($filearea == 'discussion') && !has_capability('format/page:discuss', $context)) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
}

/**
 * Fix width when editing by letting no column, at null width.
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
        foreach ($nulls as $null) {
            $$null += 2;
            $$maxvar -= 2;
            $classes[$null] = 'no-width';
        }
    }

    return $classes;
}

function format_page_is_bootstrapped() {
    global $PAGE;

    $bootstrapped = (in_array($PAGE->theme->name, array('snap', 'boost', 'fordson')) ||
            in_array('bootstrapbase', $PAGE->theme->parents) ||
                    in_array('clean', $PAGE->theme->parents) ||
                            preg_match('/bootstrap|essential|fordson/', $PAGE->theme->name));

    return $bootstrapped;
}

/**
 * Resolves the current page showing against all page access related rules and
 * page id given.
 */
function format_page_resolve_page($course) {
    global $PAGE, $COURSE;

    $id     = optional_param('id', SITEID, PARAM_INT);    // Course ID.
    $pageid = optional_param('page', 0, PARAM_INT);       // format_page record ID.

    if (!$pageid) {
        if ($page = course_page::get_current_page($course->id)) {
            $displayid = $page->id;
        } else {
            $displayid = 0;
        }
    }

    // Check out the $pageid - set? valid? belongs to this course?

    if (!empty($pageid)) {
        if (empty($page) or $page->id != $pageid) {
            // Didn't get the page above or we got the wrong one...
            if (!$page = course_page::get($pageid)) {
                print_error('errorpageid', 'format_page');
            }
            $page->formatpage->id = $pageid;
        }
        // Ensure this page is in this course.
        if ($page->courseid != $course->id) {
            // Try return to default page. Somethging was wrong between pageid and course id
            $page = course_page::get_default_page($course->id);
        }
    }

    $editing = $PAGE->user_is_editing();

    if (!$editing && !($page->is_visible())) {
        // Seek for a visible page forth or back
        if ($pagenext = $page->get_next()) {
            $page = $pagenext;
            $pageid = course_page::set_current_page($COURSE->id, $page->id);
        } else if ($pageprevious = $page->get_previous()) {
            $page = $pageprevious;
            $pageid = course_page::set_current_page($COURSE->id, $page->id);
        }

        // We don't have a page ID to work with (probably no pages yet in course).
        if (!$page) {
            if (has_capability('format/page:editpages', $context)) {
                $action = 'editpage';
                $page = new course_page(null);
                if (empty($CFG->customscripts)) {
                    print_error('errorflexpageinstall', 'format_page');
                }
                // Setup new page to add.
                $params = array('id' => $COURSE->id, 'page' => 0);
                $editurl = new moodle_url('/course/format/page/actions/editpage.php', $params);
                redirect($editurl);
            } else {
                // Nothing this person can do about it, error out.
                $PAGE->set_title($SITE->name);
                $PAGE->set_heading($SITE->name);
                echo $OUTPUT->box_start('notifyproblem');
                echo $OUTPUT->notification(get_string('nopageswithcontent', 'format_page'));
                echo $OUTPUT->box_end();
                echo $OUTPUT->footer();
                die;
            }
        } else {
            // We have another page.
            $otherpageurl = new moodle_url('/course/view.php', array('id' => $course->id, 'page' => $page->id));
            redirect($otherpageurl);
        }
    }

    course_page::set_current_page($course->id, $page->id);

    return $page;
}

/**
 * This function allows the tool_dbcleaner to register integrity checks
 */
function format_page_dbcleaner_add_keys() {
    $keys = array(
        array('format_page', 'courseid', 'course', 'id', ''),
        array('format_page_items', 'pageid', 'format_page', 'id', ''),
        array('format_page_items', 'cmid', 'course_modules', 'id', ''),
        array('format_page_discussion', 'pageid', 'format_page', 'id', ''),
        array('format_page_discussion_user', 'pageid', 'format_page', 'id', ''),
        array('format_page_discussion_user', 'userid', 'user', 'id', ''),
        array('format_page_access', 'pageid', 'format_page', 'id', ''),
    );

    return $keys;
}

/**
 * Experimental : a Call back function for inplace page name edition.
 */
function format_page_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB;

    if ($itemtype == 'pagename') {
        $DB->set_field('format_page', 'nameone', $newvalue, array('id' => $itemid));
    }
}