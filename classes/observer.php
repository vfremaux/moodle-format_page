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
 * Event observers used in format page.
 *
 * @package     format_page
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');

use format\page\course_page;

/**
 * Event observer for format_page.
 */
class format_page_observer {

    /**
     * This is an event handler registered for when creating course modules in paged formated course
     * Conditions : be in page format for course, and having an awaiting to insert activity module
     * in session.
     * @param object $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $DB, $PAGE, $CFG;

        $config = get_config('format_page');

        $course = $DB->get_record('course', array('id' => $event->objectid));
        $context = context_course::instance($event->objectid);

        if (!self::precheck_conditions($course, $event)) {
            return;
        }

        // Prepare a minimaly loaded moodle page object representing the new course page context.
        $moodlepage = new moodle_page();
        $moodlepage->set_course($course);
        $moodlepage->set_context($context);
        $moodlepage->set_pagelayout('format_page');

        // Prepare a block manager instance for operating blocks.
        $blockmanager = new page_enabled_block_manager($moodlepage);

        if (!$blockmanager->is_known_region('main')) {
            /*
             * Add a custom regions that are not yet defined into the current operation page to
             * allow page format region operations
             */
            $blockmanager->add_region('side-pre');
            $blockmanager->add_region('main', true);
            $blockmanager->add_region('side-post');
        }

        // Build the course.
        if (empty($CFG->defaultpageformat)) {

            // Build a hardcoded course.
            debug_trace("Build wecome page", TRACE_NOTICE);
            self::build_welcome_page($event, $blockmanager, 1, $course);
            debug_trace("Build administration page", TRACE_NOTICE);
            self::build_administration_page($event, $blockmanager, 2);
        } else {
            /*
             * We expect an array of the form :
             * $CFG->defaultpageformat = array(
             *      '1:page name one:page name two:3-6-3:0:0' => '<blockormodulelist>:<blockormodulelist>:<blockormodulelist>',
             *      '2:page name one:page name two:0-6-3:1:1' => '<blockormodulelist>:<blockormodulelist>:<blockormodulelist>',
             *      '3:page name one:page name two:0-6-3:1:1' => '<blockormodulelist>:<blockormodulelist>:<blockormodulelist>',
             * );
             *
             * bloc or module list is a comma separated list of blocknames (full qualified frankenstyles) or activity names. some activity names can have
             * subtype provided with a | (vert bar), f.e : mod_customlabel|courseheading. Each actvitiy will be instanciated as
             * a new course module.
             */
            $pages = array();

            if ($errors = self::precheck_default_format()) {
                print_error('errordefaultpageoformat', 'format_page', $errors);
            }

            foreach ($CFG->defaultpageformat as $pagedesc => $pagedef) {
                $pagedescarray = explode(':', $pagedesc);
                list($pid, $pagenameone, $pagenametwo, $pagelayout, $pagedisplay, $parentid) = $pagedescarray;
                $regionlayouts = explode(':', $pagedef);

                $layout = explode('-', $pagelayout);

                // Make the page.
                $pagerec = course_page::instance(0, $event->objectid);
                $pagerec->nameone = $pagenameone;
                $pagerec->nametwo = $pagenametwo;
                $pagerec->display = $pagedisplay;
                $pagerec->displaymenu = 1;

                // Autocalculate non bootstrap widths from the bs layout descriptor.
                $defaultpagewidth = $pagerec->prefleftwidth + $pagerec->prefcenterwidth + $pagerec->prefrightwidth;
                $pagerec->prefleftwidth = $defaultpagewidth * $layout[0] / 12;
                $pagerec->prefcenterwidth = $defaultpagewidth * $layout[1] / 12;
                $pagerec->prefrightwidth = $defaultpagewidth * $layout[2] / 12;
                $pagerec->bsprefleftwidth = $layout[0];
                $pagerec->bsprefcenterwidth = $layout[1];
                $pagerec->bsprefrightwidth = $layout[2];

                if (array_key_exists($parentid, $pages)) {
                    $pagerec->parent = $pages[$parentid];
                }
                $page->save();
                $page->make_section(1);

                // Register created page.
                $pages[$pid] = $page->id;

                // Now populate.
                $regions = array('side-pre', 'main', 'side-post');
                foreach ($regionlayouts as $region) {
                    $regionname = array_shift($regions);
                    $items = explode(',', $region);
                    if (!empty($items)) {
                        $weight = 0;
                        foreach ($items as $item) {
                            if (strstr($item, 'block_') !== false) {
                                // This is a block.
                                $blockmanager->add_block(str_replace('block_', '', $item), $regionname, $weight, true, 'course-view-*', 'page-'.$page->id);
                            }
                            $weight++;
                        }
                    }
                }
            }
        }
    }

    /**
     * This is an event handler registered for when creating course modules in paged formated course
     * Conditions : be in page format for course, and having an awaiting to insert activity module
     * in session.
     * @param object $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
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
                $DB->set_field('format_page_items', 'cmid', $event->objectid, array('id' => $pageitem->id));

                // Now add cminstance id to configuration.
                $block = block_instance('page_module', $instance);
                $block->config->cmid = $event->objectid;
                $block->instance_config_save($block->config);

                // Finally ensure course module is visible.
                $DB->set_field('course_modules', 'visible', 1, array('id' => $event->objectid));
            }

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
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB, $PAGE;

        $pageitems = $DB->get_records('format_page_items', array('cmid' => $event->objectid));

        foreach ($pageitems as $pi) {
            if ($blockrec = $DB->get_record('block_instances', array('id' => $pi->blockinstance))) {
                $block = block_instance('page_module', $blockrec);

                // User_can_addto is not running on the actual block location PAGE, this could sometimes produce weird lockings.
                if (!$block->user_can_edit() || !$PAGE->user_can_edit_blocks() || !$block->user_can_addto($PAGE)) {
                    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('deleteablock'));
                }

                blocks_delete_instance($block->instance);
            }
        }

        // Delete all related page items.
        $DB->delete_records('format_page_items', array('cmid' => $event->objectid));
    }

    /**
     * allows deleting additional format dedicated
     * structures
     * @param object $event the event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $pages = $DB->get_records('format_page', array('courseid' => $event->objectid));
        if ($pages) {
            foreach ($pages as $page) {
                $DB->delete_records('format_page_items', array('pageid' => $page->id));
                $DB->delete_records('format_page', array('id' => $page->id));
            }
        }
    }

    /**
     * Processed to format conversion with following rules :
     * Section 0 will keep track of unpublished course modules.
     * All sections are shifted from one sectionnum to liberate the num 1.
     * Section 1 is inserted to match the "welcome page"
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB, $COURSE;

        $course = $DB->get_record('course', ['id' => $event->objectid]);
        $context = context_course::instance($course->id);

        debug_trace("Ingoing format : {$COURSE->format} => ".$course->format, TRACE_NOTICE);

        // If not page, clean out all page related info.
        if ($course->format != 'page') {

            // delete ADMIN and WELCOME pages by idnumber.
            $p = $DB->get_record('format_page', ['idnumber' => $course->id.'_ADMIN']);
            if ($p) {
                page_delete_page($p->id);
            }

            $p = $DB->get_record('format_page', ['idnumber' => $course->id.'_WELCOME']);
            if ($p) {

                // Get sequence 0
                $params = ['course' => $course->id, 'section' => $p->section];
                $welcomesection = $DB->get_record('course_sections', $params);

                page_delete_page($p->id);

                // Write sequence0 back.
                // NO NEED, and might put in deleted weird course_modules.
                // $params = ['course' => $course->id, 'section' => 0];
                // $section0 = $DB->get_record('course_sections', $params);
                // $DB->set_field('course_sections', 'sequence', $welcomesection->sequence, $params);

                // Move all modules to sequence0. We keep some modules that were added in added pages.
                $sql = "
                    UPDATE
                        {course_modules}
                    SET
                        section = ?
                    WHERE
                        course = ? AND
                        section = ?
                ";
                $params = [$section0->id, $course->id, $welcomesection->id];
                $DB->execute($sql, $params);
            }

            $pages = $DB->get_records('format_page', ['courseid' => $event->objectid]);
            foreach ($pages as $p) {
                $DB->delete_records('format_page_items', ['pageid' => $p->id]);
                $DB->delete_records('format_page', ['id' => $p->id]);
            }
            debug_trace("Retro conversion : Page data cleared", TRACE_NOTICE);
            return;
        }

        // Pass other 
        if (!self::precheck_conditions($course, $event)) {
            return;
        }

        /*
         * Mark format in in-memory $COURSE global
         */
         $COURSE->format = 'page';

        /*
         * We have no page structure. We are probably commng from another format.
         *  create the welcome page, section pages, and administration pages.
         * Preserve section sequences.
         */

        debug_trace("Conversion progress: Preparing Welcome section slot ", TRACE_NOTICE);

        $sections = $DB->get_records('course_sections', ['course' => $event->objectid], 'section');

        // Shift forth all sections to liberate the welcome slot. Need traverse slots in reverse order.
        if (!empty($sections)) {
            $keys = array_keys($sections);
            $keys = array_reverse($keys);
            foreach ($keys as $k) {
                $s = $sections[$k];
                if ($s->section > 0) {
                    $s->section++;
                    debug_trace("Conversion progress: Push section {$s->id} to  {$s->section}", TRACE_DEBUG);
                    $DB->set_field('course_sections', 'section', $s->section, ['id' => $s->id]);
                }
            }
        }

        $moodlepage = new moodle_page();
        $moodlepage->set_course($course);
        $moodlepage->set_context($context);
        $moodlepage->set_pagelayout('format_page');

        debug_trace("Conversion progress: Preparing Block Manager ", TRACE_NOTICE);

        // Prepare a block manager instance for operating blocks.
        $blockmanager = new page_enabled_block_manager($moodlepage);

        if (!$blockmanager->is_known_region('main')) {
            /*
             * Add a custom regions that are not yet defined into the current operation page to
             * allow page format region operations
             */
            $blockmanager->add_region('side-pre');
            $blockmanager->add_region('main', true);
            $blockmanager->add_region('side-post');
        }

        debug_trace("Conversion progress: Building Welcome page ", TRACE_NOTICE);

        // Add welcome slot.
        $welcomesectionid = self::build_welcome_page($event, $blockmanager, 1, $course);

        debug_trace("Conversion progress: Building pages for sections", TRACE_NOTICE);

        $lastsectionnum = 0;
        // Build section pages. Do not process section 0.
        if (!empty($sections)) {
            foreach ($sections as $s) {
                if (empty($s->sequence) && empty($s->name)) {
                    // Skip empty sequences.
                    continue;
                }
                if ($s->section > 0) {
                    debug_trace("Conversion progress: Building section $s->id / $s->section / $s->name ", TRACE_DEBUG);
                    $lastsectionnum = self::build_section_page($s, $event);
                }
            }
        }
        $lastsectionnum++;

        self::build_administration_page($event, $blockmanager, $lastsectionnum);
        debug_trace("Conversion progress: Administration page built at sectionum $lastsectionnum ", TRACE_NOTICE);

        // Finally adjust section 0 by moving old sequence to section 1 (welcome).
        $arrvalues = array_values($sections);
        $section0 = array_shift($arrvalues);
        if ($section0->section == 0) {
            if (!empty($section0->sequence)) {
                $section0modules = explode(',', $section0->sequence);

                foreach ($section0modules as $cmid) {
                    // Move all modules to welcome section.
                    $DB->set_field('course_modules', 'section', $welcomesectionid, ['id' => $cmid]);
                }

                // Add sequence elements from section 0 to welcome section.
                $welcomesection = $DB->get_record('course_sections', ['id' => $welcomesectionid]);
                if (empty($welcomesection->sequence)) {
                    // Should never be.
                    $welcomesection->sequence = $section0->sequence;
                } else {
                    // Append to initial modules.
                    $welcomesection->sequence .= ','.$section0->sequence;
                }
                $DB->set_field('course_sections', 'sequence', $welcomesection->sequence, ['id' => $welcomesectionid]);
                // Clear sequence of section 0 (unpublished modules).
                // DO NOT. We should keep the section 0 and unpublished new modules there. ??? try doing it.
                $DB->set_field('course_sections', 'sequence', '', ['id' => $section0->id]);

                $welcomepage = course_page::get_by_section($welcomesection->section, $COURSE->id);
                foreach ($section0modules as $cmid) {
                    debug_trace("Conversion progress: adding $cmid to page {$welcomepage->id} in region 'main' ", TRACE_DEBUG);
                    $welcomepage->add_cm_to_page($cmid, 'main');
                }
            }
        }

        debug_trace("Conversion progress: Conversion finished ", TRACE_NOTICE);
    }

    /**
     * Checks paged course initialisation manipulations conditions.
     */
    static protected function precheck_conditions($course, $event) {
        global $PAGE, $DB;

        if (empty($course)) {
            return;
        }

        // Do nothing if not a page format.
        if ($course->format != 'page') {
            return;
        }

        // Do nothing if course is not empty.
        if ($DB->count_records('format_page', array('courseid' => $event->objectid))) {
            return;
        }

        if (!(get_class($PAGE->blocks) != 'page_enabled_block_manager')) {
            $error = 'the page block manager is not in service. check the page format install ';
            $error .= 'recommendations and customscripts wrappers.';
            throw new coding_exception($error);
        }

        return true;
    }

    /**
     * Just check config structure without generating anything
     */
    static protected function precheck_default_format() {
        global $CFG;

        $errors = '';

        $pages = array();
        $line = 1;
        foreach ($CFG->defaultpageformat as $pagedesc => $pagedef) {
            $pagedescarray = explode(':', $pagedesc);

            if (count($pagedescarray) != 6) {
                $errors .= 'Bad page descriptor count at line '.$line."<br/>\n";
            }

            list($pid, $pagenameone, $pagenametwo, $pagelayout, $pagedisplay, $parentid) = $pagedescarray;
            $regionlayouts = explode(':', $pagedef);

            $layout = explode('-', $pagelayout);
            if (count($layout) != 3) {
                $errors .= 'Layout format error at line '.$line."<br/>\n";
            }

            if (!in_array($parentid, $pages)) {
                $errors .= 'Bad or possible forth reference of parent at line '.$line."<br/>\n";
            }

            // Register viewed page ids for checking parent integrity.
            $pages[] = $pid;
            $line++;
        }

        return $errors;
    }

    static protected function build_welcome_page($event, $blockmanager, $sectionnum, $course) {
        global $DB, $CFG;

        $context = context_course::instance($event->objectid);

        // Make a first page.
        $pagerec = course_page::instance(0, $event->objectid);
        $pagerec->nameone = get_string('welcome', 'format_page');
        $pagerec->nametwo = get_string('welcome', 'format_page');
        $pagerec->idnumber = $event->objectid.'_WELCOME';
        $pagerec->display = FORMAT_PAGE_DISP_PUBLISHED;
        $pagerec->sortorder = $sectionnum - 1;
        $pagerec->displaymenu = 1;

        $page = new course_page($pagerec);
        $welcomesectionid = $page->make_section($sectionnum);
        $page->save();

        // Feed page with page tracker block and administration.
        $params = array('blockname' => 'page_tracker', 'subpagepattern' => null, 'parentcontextid' => $context->id);
        if (!$DB->record_exists('block_instances', $params)) {
            // Checks has no "display on all course pages" instance.
            if ($DB->record_exists('block', array('name' => 'page_tracker', 'visible' => 1))) {
                // Display the bloc on all pages (resulting null subpagepattern).
                debug_trace("Conversion progress: adding block page_tracker as 'allpages-{$page->id}' ", TRACE_DEBUG);
                $blockmanager->add_block('page_tracker', 'side-pre', 0, true, 'course-view-*', 'allpages-'.$page->id);
            } else {
                debug_trace("Conversion progress: block page_tracker not enabled or not installed. ", TRACE_ERRORS);
            }
        } else {
            debug_trace("Conversion progress: block page_tracker as 'allpages-{$page->id}' exists. ", TRACE_DEBUG);
        }

        if (is_dir($CFG->dirroot.'/mod/customlabel')) {

            // Add course heading customlabel.
            include_once($CFG->dirroot.('/mod/customlabel/lib.php'));

            $sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => $page->section]);

            // Record a course module.
            $cmrec = new StdClass();
            $cmrec->module = $DB->get_field('modules', 'id', ['name' => 'customlabel']);
            $cmrec->course = $course->id;
            $cmrec->section = $sectionid;
            $cmrec->instance = 0;
            $cmid = $DB->insert_record('course_modules', $cmrec);

            // Ask for context creation for it.
            context_module::instance($cmid);

            debug_trace("Cmid created $cmid ", TRACE_DEBUG);

            // Make an instance.
            $customlabelrec = new StdClass();
            $customlabelrec->course = $course->id;
            $customlabelrec->labelclass = 'courseheading';
            $customlabelrec->name = $course->fullname;
            $customlabelrec->coursemodule = $cmid;
            $customlabelrec->title = '';
            $customlabelrec->fallacktype = 'text';
            $customlabelrec->intro = '';
            $customlabelrec->introformat = FORMAT_HTML;
            $customlabelrec->data = new StdClass(); // Use preloaded data rather then form input.
            $customlabelrec->data->showdescription = 1;
            $customlabelrec->data->showshortname = 1;
            $customlabelrec->data->showidnumber = 0;
            $customlabelrec->data->showcategory = 1;
            $customlabelrec->data->overimagetext = '';
            $customlabelrec->data->imageposition = 'none';
            $customlabelrec->data->moduletype = '';

            $instanceid = customlabel_add_instance($customlabelrec);
            debug_trace("Customlabel created $instanceid ", TRACE_DEBUG);

            // Rebind intance in course module.
            $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

            debug_trace("Add cm $cmid to section {$page->section} ", TRACE_DEBUG_FINE);
            $sectionid = course_add_cm_to_section($course, $cmid, $page->section);
            $DB->set_field('course_modules', 'section', $sectionid, ['id' => $cmid]);

            $page->add_cm_to_page($cmid, 'main', $course);
            debug_trace("ADDED $cmid ");

            // Add coursedata customlabel.

            $cmrec = new StdClass();
            $cmrec->module = $DB->get_field('modules', 'id', ['name' => 'customlabel']);
            $cmrec->course = $course->id;
            $cmrec->section = $sectionid;
            $cmrec->instance = 0;
            $cmid = $DB->insert_record('course_modules', $cmrec);

            // Ask for context creation for it.
            context_module::instance($cmid);

            debug_trace("Cmid created $cmid ", TRACE_DEBUG_FINE);

            $customlabelrec = new StdClass();
            $customlabelrec->course = $course->id;
            $customlabelrec->labelclass = 'coursedata';
            $customlabelrec->name = $course->fullname;
            $customlabelrec->coursemodule = $cmid;
            $customlabelrec->title = '';
            $customlabelrec->fallacktype = 'text';
            $customlabelrec->intro = '';
            $customlabelrec->introformat = FORMAT_HTML;
            $customlabelrec->data = new StdClass(); // Use preloaded data rather then form input.
            $customlabelrec->data->tablecaption = get_string('defaulttablecaption', 'customlabeltype_coursedata');
            $customlabelrec->data->showtarget = 1;
            $customlabelrec->data->target = '';
            $customlabelrec->data->showgoals = 1;
            $customlabelrec->data->goals = '';
            $customlabelrec->data->showobjectives = 1;
            $customlabelrec->data->objectives = '';
            $customlabelrec->data->showconcepts = 1;
            $customlabelrec->data->concepts = '';
            $customlabelrec->data->showduration = 1;
            $customlabelrec->data->duration = '';
            $customlabelrec->data->showteachingorganization = 1;
            $customlabelrec->data->teachingorganization = '';
            $customlabelrec->data->showprerequisites = 1;
            $customlabelrec->data->prerequisites = '';
            $customlabelrec->data->showevaluation = 1;
            $customlabelrec->data->evaluation = '';
            $customlabelrec->data->showoutcomes = 1;
            $customlabelrec->data->outcomes = '';
            $customlabelrec->data->showfollowers = 1;
            $customlabelrec->data->followers = '';
            $customlabelrec->data->leftcolumnratio = '30%';
            $customlabelrec->data->showheadteacher = 1;
            $customlabelrec->data->headteacher = '';
            $customlabelrec->data->showlastupdate = 1;
            $customlabelrec->data->lastupdate = '';

            $instanceid = customlabel_add_instance($customlabelrec);
            debug_trace("Customlabel created $instanceid ", TRACE_DEBUG_FINE);

            // Rebind intance in course module.
            $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

            $sectionid = course_add_cm_to_section($course, $cmid, $page->section);
            debug_trace("Target section for addition $sectionid ", TRACE_DEBUG_FINE);
            $DB->set_field('course_modules', 'section', $sectionid, ['id' => $cmid]);

            $page->add_cm_to_page($cmid, 'main', $course);
            debug_trace("ADDED $cmid ");
        }

        return $welcomesectionid;
    }

    static protected function build_administration_page($event, $blockmanager, $sectionnum) {
        global $DB;

        $config = get_config('format_page');

        // Make a second page.
        $pagerec = course_page::instance(0, $event->objectid);
        $pagerec->nameone = get_string('administration', 'format_page');
        $pagerec->nametwo = get_string('administration', 'format_page');
        $pagerec->idnumber = $event->objectid.'_ADMIN';
        $pagerec->display = FORMAT_PAGE_DISP_PROTECTED;
        $pagerec->sortorder = $sectionnum - 1;
        $pagerec->section = $sectionnum;
        $pagerec->displaymenu = 1;
        $pagerec->protected = $config->protectadminpage;

        $adminpage = new course_page($pagerec);
        $adminpage->make_section($sectionnum);
        $adminpage->save();

        // Feed page with page tracker block and administration.

        /*
        // Do not create the block page_tracker, use the welcome page "display everywhere"
        if ($DB->record_exists('block', array('name' => 'page_tracker', 'visible' => 1))) {
            $blockmanager->add_block('page_tracker', 'side-pre', 0, true, 'course-view-*', 'page-'.$adminpage->id);
        }
        */
        $blockmanager->add_block('participants', 'side-pre', 1, true, 'course-view-*', 'page-'.$adminpage->id);
        $blockmanager->add_block('settings', 'main', 0, true, 'course-view-*', 'page-'.$adminpage->id);
        $blockmanager->add_block('online_users', 'side-post', 1, true, 'course-view-*', 'page-'.$adminpage->id);
    }

    /**
     * Build a page for an existing section.
     * @param object $section the existing section record
     */
    static protected function build_section_page($section, $event) {
        global $DB;

        // Make a section page.
        $pagerec = course_page::instance(0, $event->objectid);
        $sectionnum = $section->section - 1;
        $pagerec->nameone = ($section->name) ? $section->name : 'Section '.$sectionnum;
        $pagerec->nametwo = $pagerec->nameone;
        $pagerec->display = FORMAT_PAGE_DISP_PUBLISHED;
        if (!$section->visible) {
            $pagerec->display = FORMAT_PAGE_DISP_HIDDEN;
        }
        $pagerec->sortorder = $section->section - 1;
        $pagerec->displaymenu = 1;
        $pagerec->section = $section->section;

        $spage = new course_page($pagerec);
        $spage->save();

        if (!empty($section->sequence)) {
            $coursemoduleids = explode(',', $section->sequence);
            foreach ($coursemoduleids as $cmid) {
                $spage->add_cm_to_page($cmid, 'main', $course);
            }
        }

        return $section->section;
    }
}
