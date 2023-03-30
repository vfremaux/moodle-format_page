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
 * Objectivates a tree manipulation library
 * this is a utility class when creating page in the tree structure.
 *
 * @author Valery Fremaux
 * @package format_page
 * @category format
 */
namespace format\page;

defined('MOODLE_INTERNAL') or die();

class tree {

    /**
     * @global type $DB
     * @param type $pageid
     * @param type $position
     * @param type $sortorder
     */
    public static function discard_pageitem_sortorder($pageid, $position, $sortorder) {
        global $DB;

        $sql = "
            UPDATE
                {format_page_items}
            SET
                sortorder = sortorder - 1
            WHERE
                pageid = ? AND
                position = ? AND
                sortorder > ?
        ";
        $DB->execute($sql, array($pageid, $position, $sortorder));
    }

    /**
     * Function returns the next sortorder value for a group of pages with the same parent
     *
     * @param int $parentid ID of the parent grouping, can be 0
     * @return int
     */
    public static function get_next_page_sortorder($courseid, $parentid) {
        global $DB;

        $sql = "
            SELECT
                1,
                MAX(sortorder) + 1 AS nextfree
            FROM
                {format_page}
            WHERE
                parent = ? AND
                courseid = ?
        ";

        $sortorder = $DB->get_record_sql($sql, array($parentid, $courseid));

        if (empty($sortorder->nextfree)) {
            $sortorder->nextfree = 0;
        }

        return $sortorder->nextfree;
    }

    /**
     * Frees a location into the sortorder sequence.
     *
     */
    public static function insert_page_sortorder($courseid, $parentid, $sortorder) {
        global $DB;

        $sql = "
            UPDATE
                {format_page}
            SET
                sortorder = sortorder + 1
            WHERE
                courseid = :courseid AND
                parent = :parentid AND
                sortorder > :insertedorder
        ";
        $params = ['courseid' => $courseid, 'parentid' => $parentid, 'insertedorder' => $sortorder];
        $DB->execute($sql, $params);
    }

    /**
     * Moves a location into the sortorder sequence from a start point to a position
     * Algorithm : for ([e to s[)
     *   if (e < s) shift+ else shift-
     */
    public static function move_page_sortorder($courseid, $parentid, $startpage, $endlocation) {
        global $DB;

        $start = $startpage->sortorder;
        $end = $endlocation;
        debug_trace("S: $start, E:$end");
        if ($start < $end) {
            // Move down any upper before end (inclusive).
            $op = '-';
            $boundaries = "
                sortorder > :start AND
                sortorder <= :end
            ";
        } else {
            // Move up any lower after end (inclusive).
            $op = '+';
            $boundaries = "
                sortorder >= :end AND
                sortorder < :start
            ";
        }

        $sql = "
            UPDATE
                {format_page}
            SET
                sortorder = sortorder {$op} 1
            WHERE
                courseid = :courseid AND
                parent = :parentid AND
                {$boundaries}
        ";
        $params = ['courseid' => $courseid, 'parentid' => $parentid, 'start' => $start, 'end' => $end];
        $DB->execute($sql, $params);

        debug_trace('Moving ('.$startpage->id.') to '.$end, TRACE_DEBUG);
        $DB->set_field('format_page', 'sortorder', $end, ['id' => $startpage->id]);
    }

    /**
     * Removes a page from its current location by decrementing
     * the sortorder field of all pages that have the same
     * parent and course as the page. Needs the page beeing deleted before or after.
     *
     * @param int $courseid the current course
     * @param int $parentid parent page scope
     * @param int $sortorder location to remove from order.
     * @return boolean
     */
    public static function discard_page_sortorder($courseid, $parentid, $sortorder) {
        global $DB;

        $sql = "
            UPDATE
                {format_page}
            SET
                sortorder = sortorder - 1
            WHERE
                courseid = :courseid AND
                parent = :parentid AND
                sortorder > :discardedorder
        ";
        $params = ['courseid' => $courseid, 'parentid' => $parentid, 'discardedorder' => $sortorder];
        $DB->execute($sql, $params);
    }

    /**
     * Function checks and fixes sortorder in a parent scope.
     * NEVER USED
     * @param int $parentid ID of the parent grouping, can be 0
     */
    public static function fix_level($parentid) {
        global $DB, $COURSE;

        $params = array('courseid' => $COURSE->id, 'parent' => $parentid);
        if ($allchilds = $DB->get_records('format_page', $params, 'sortorder, id')) {
            $so = 0;
            foreach ($allchilds as $child) {
                $DB->set_field('format_page', 'sortorder', $so, array('id' => $child->id));
                $so++;
            }
        }
    }

    /**
     * Function rebuilds the section sortorder sequence of all course and
     * repairs missing sections.
     *
     * @param int $courseid a course id to fix. Defaults to current COURSE
     * @return int
     */
    public static function fix($courseid = 0) {
        global $DB, $COURSE;

        if (!$courseid) {
            $courseid = $COURSE->id;
        }

        $oldparent = 9999999;
        $sections[0] = 0;
        if ($allchilds = $DB->get_records('format_page', array('courseid' => $courseid), 'parent,sortorder')) {
            $so = 0;
            foreach ($allchilds as $child) {
                if ($child->parent != $oldparent) {
                    $so = 0;
                }
                $DB->set_field('format_page', 'sortorder', $so, array('id' => $child->id));
                $oldparent = $child->parent;
                $so++;

                $sections[$child->section] = course_page::get($child->id);
            }
        }

        $fixedsections = array();
        if (!empty($sections)) {
            foreach ($sections as $sid => $page) {
                if (is_object($page)) {
                    $params = array('course' => $courseid, 'section' => $page->get_section());
                    if (!$DB->get_record('course_sections', $params)) {
                        $page->make_section($sid);
                    } else {
                        $page->update_section($sid);
                    }
                }
            }
        }

        // Removing extra sections. Finding all ids that are NOT in the list (4th parameter).
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($sections), SQL_PARAMS_QM, 'param', false);
        $params = array_merge(array($courseid), $inparams);
        $extras = $DB->get_records_select('course_sections', " course = ? AND section $insql ", $params);
        if (!empty($extras)) {
            foreach ($extras as $extra) {
                $DB->delete_records('course_sections', array('course' => $courseid, 'section' => $extra->section));
            }
        }

        rebuild_course_cache($courseid);
    }
}