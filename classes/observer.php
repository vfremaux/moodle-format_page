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
 * Event observers used in forum.
 *
 * @package    mod_forum
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/page/blocklib.php');

/**
 * Event observer for format_page.
 */
class format_page_observer {

// Usefull event handler.

    /**
     * This is an event handler registered for when creating course modules in paged formated course
     * Conditions : be in page format for course, and having an awaiting to insert activity module
     * in session.
     * @param object $event
     */
    static function course_created(\core\event\course_created $event) {
        global $DB, $PAGE, $CFG;

        $course = $DB->get_record('course', array('id' => $event->objectid));
        $context = context_course::instance($event->objectid);

        // Do nothing if not a page format.
        if ($course->format != 'page') {
            return;
        }

        // Do nothing if ocurse is not empty.
        if ($DB->count_records('format_page', array('courseid' => $event->objectid))) {
            return;
        }

        if (!(get_class($PAGE->blocks) != 'page_enabled_block_manager')) {
            throw new coding_exception('the page block manager is not in service. check the page format install recommendations and customscripts wrappers.');
        }

        // Prepare a minimaly loaded moodle page object representing the new course page context.
        $moodlepage = new moodle_page();
        $moodlepage->set_course($course);
        $moodlepage->set_context($context);
        $moodlepage->set_pagelayout('format_page');

        // Prepare a block manager instance for operating blocks
        $blockmanager = new page_enabled_block_manager($moodlepage);

        if (!$blockmanager->is_known_region('main')) {
            // Add a custom regions that are not yet defined into the current operation page to allow page format region operations
            $blockmanager->add_region('side-pre');
            $blockmanager->add_region('main', true);
            $blockmanager->add_region('side-post');
        }

        // Build the course
        if (empty($CFG->defaultpageformat)) {

            // Build a hardcoded course

            // Make a first page.
            $pagerec = course_page::instance(0, $event->objectid);
            $pagerec->nameone = get_string('welcome', 'format_page');
            $pagerec->nametwo = get_string('welcome', 'format_page');
            $pagerec->display = FORMAT_PAGE_DISP_PUBLISHED;
            $pagerec->displaymenu = 1;
    
            $page = new course_page($pagerec);
            $page->save();
            $page->make_section(1);
    
            // Feed page with page tracker block and administration
            if ($DB->record_exists('block', array('name' => 'page_tracker', 'visible' => 1))) {
                $blockmanager->add_block('page_tracker', 'side-pre', 0, true, 'course-view-*', 'page-'.$page->id);
            }
    
            // Make a first page.
            $pagerec = course_page::instance(0, $event->objectid);
            $pagerec->nameone = get_string('administration', 'format_page');
            $pagerec->nametwo = get_string('administration', 'format_page');
            $pagerec->display = FORMAT_PAGE_DISP_PROTECTED;
            $pagerec->displaymenu = 1;
    
            $adminpage = new course_page($pagerec);
            $adminpage->save();
            $adminpage->make_section(2);
    
            // Feed page with page tracker block and administration
            
            if ($DB->record_exists('block', array('name' => 'page_tracker', 'visible' => 1))) {
                $blockmanager->add_block('page_tracker', 'side-pre', 0, true, 'course-view-*', 'page-'.$adminpage->id);
            }
            $blockmanager->add_block('participants', 'side-pre', 1, true, 'course-view-*', 'page-'.$adminpage->id);
            $blockmanager->add_block('settings', 'main', 0, true, 'course-view-*', 'page-'.$adminpage->id);
            $blockmanager->add_block('online_users', 'side-post', 1, true, 'course-view-*', 'page-'.$adminpage->id);
        } else {
            /**
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

                // autocalculate non bootstrap widths from the bs layout descriptor
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

                // Now populate
                $regions = array('side-pre', 'main', 'side-post');
                foreach($regionlayouts as $region) {
                    $regionname = array_shift($regions);
                    $items = explode(',', $region);
                    if (!empty($items)) {
                        $weight = 0;
                        foreach ($items as $item) {
                            if (strstr($item, 'block_') !== false) {
                                // This is a block.
                                $blockmanager->add_block(str_replace('block_', '', $item), $regionname, $weight, true, 'course-view-*', 'page-'.$page->id);
                            } else {
                                // This is an activity module.
                                /* this will instanciate a default activity instance and
                                 * wrap it into a new page_module block instance.
                                 * @TODO : discuss what policy to follow for gettign mandatory initialisation
                                 * data from the description.
                                 */
                                // $blockmanager->add_course_module(str_replace('mod_', '', $item), $regionname, $weight, true, 'course-view-*', 'page-'.$page->id);
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
    static function course_module_created(\core\event\course_module_created $event) {
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
            }
    
            // Now add cminstance id to configuration.
            $block = block_instance('page_module', $instance);
            $block->config->cmid = $event->objectid;
            $block->instance_config_save($block->config);
    
            // Finally ensure course module is visible.
            $DB->set_field('course_modules', 'visible', 1, array('id' => $event->objectid));
    
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
    static function course_module_deleted(\core\event\course_module_deleted $event) {
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
    static function course_deleted(\core\event\course_deleted $event) {
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

            // register viewed page ids for checking parent integrity.
            $pages[] = $pid;
            $line++;
        }

        return $errors;
    }
}
