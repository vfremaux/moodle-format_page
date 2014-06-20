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
 * This is a tecnhical tool for fing inconsistent information
 *
 */

require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

/**
 * Fix scripts for page format.
 *
 *
 */

// This script repairs subpage unassigned blocks.

function page_format_remap_subpages($courseid = null, $blockinstanceid = null) {
    global $DB, $CFG;

    $verbose = (!$courseid && !$blockinstanceid);

    if ($verbose) {
        mtrace("start converting page format blocks...");
        mtrace("querying in DB $CFG->dbname at $CFG->dbhost ");
    }

    $courseclause = '';
    if ($courseid) {
        $context = context_course::instance($courseid);
        $courseclause = ' AND bi.parentcontextid = '.$context->id;
    }

    $instanceclause = '';
    if ($blockinstanceid) {
        $instanceclause = ' AND bi.id = '.$blockinstanceid;
    }

    $sql = "
        SELECT
            bi.*,
            fpi.pageid as pageid
        FROM
            {block_instances} bi,
            {format_page_items} fpi
        WHERE
            fpi.blockinstance = bi.id
            {$courseclause} AND
            (bi.subpagepattern = '' OR bi.subpagepattern IS NULL)
    ";

    $allpagedblocks = $DB->get_records_sql($sql);

    if ($allpagedblocks) {
        if ($verbose) {
            mtrace("fixing ".count($allpagedblocks)." blocks ");
        }
        foreach ($allpagedblocks as $b) {
            $pageid = $b->pageid;
            $b->subpagepattern = 'page-'.$pageid;
            unset($b->pageid);
            $DB->update_record('block_instances', $b);
            if ($verbose) {
                mtrace("Fixing block instance $b->id in $pageid ");
            }
            if ($p = $DB->get_record('block_positions', array('blockinstanceid' => $b->id))) {
                $p->subpage = 'page-'.$pageid;
                $DB->update_record('block_positions', $p);
                if ($verbose) {
                    mtrace("Fixing block position $p->id , $p->blockinstanceid in $p->subpage ");
                }
            }
        }
    }

    if ($verbose) {
        mtrace("...finished");
    }
}

function page_format_fix_bad_items() {
    global $DB;

    // Check real blocks with instances not in our course.
    // Delete those items.

    echo "Search for block records to fix\n";
    $sql = "
        SELECT
            fpi.id as id,
            bi.id as bid,
            fp.courseid as pcid,
            ctx.id as ctx,
            ctx.instanceid as bcid
        FROM
            {format_page_items} fpi,
            {format_page} fp,
            {context} ctx,
            {block_instances} bi
        WHERE
            fpi.pageid = fp.id AND
            fpi.blockinstance = bi.id AND
            bi.parentcontextid = ctx.id AND
            ctx.contextlevel = ?
        HAVING
            pcid != bcid
    ";
    if ($badrecords = $DB->get_records_sql($sql, array(50))) {
        $badcount = count($badrecords);
        echo "Found $badcount block records to fix\n";
        foreach ($badrecords as $badrec) {
            $DB->delete_records('format_page_items', array('id' => $badrec->id));
        }
    } else {
        echo "no bad records.\n";    
    }

    // Now check those items mapped to course modules not in the same course.

    echo "Search for module records to fix\n";
    $sql = "
        SELECT
            fpi.id as id,
            fp.courseid as pcid,
            cm.course as cmcid
        FROM
            {format_page_items} fpi,
            {format_page} fp,
            {course_modules} cm
        WHERE
            fpi.pageid = fp.id AND
            fpi.cmid = cm.id
        HAVING
            pcid != cmcid
    ";
    if ($badrecords = $DB->get_records_sql($sql, array(50))) {
        $badcount = count($badrecords);
        echo "Found $badcount module records to fix\n";
        foreach ($badrecords as $badrec) {
            $DB->delete_records('format_page_items', array('id' => $badrec->id));
        }
    } else {
        echo "no bad records.\n";    
    }
    
    // Check for bad section mapping by item link.
    echo "Search for inconsistant section course by page items\n";    
    $sql = "
        SELECT
            fpi.id as id,
            fpi.cmid as cmid,
            fp.section as psection,
            fp.courseid as pcid,
            cs.id as sid,
            cs.sequence as seq,
            cs.course as scid
        FROM
            {format_page_items} fpi,
            {format_page} fp,
            {course_sections} cs
        WHERE
            fpi.cmid != 0 AND
            fpi.pageid = fp.id AND
            FIND_IN_SET(fpi.cmid, cs.sequence)
        HAVING
            pcid != scid
    ";
    if ($badrecords = $DB->get_records_sql($sql, array(50))) {
        $badcount = count($badrecords);
        echo "Found $badcount page items in inconsistant sections\n";
        foreach ($badrecords as $badrec) {
            // fix by removing module from section
            $sequence = $DB->get_field('course_sections', 'sequence', array('id' => $badrec->sid));
            $seqarr = explode(',', $sequence);
            for ($i = 0; $i < count($seqarr) ; $i++) {
                if ($seqarr[$i] == $badrec->cmid) {
                    unset($seqarr[$i]);
                }
            }
            $sequence = implode(',', $seqarr);
            $DB->set_field('course_sections', 'sequence', $sequence, array('id' => $badrec->sid));
        }
    } else {
        echo "no bad records.\n";
    }

    // Check orphan items.

    echo "Search for orphan records to fix\n";
    $sql = "
        SELECT
            fpi.id,
            fpi.id
        FROM
            {format_page_items} fpi
        WHERE
            fpi.pageid NOT IN ( SELECT id FROM {format_page} )
    ";

    if ($badrecords = $DB->get_records_sql($sql, array(50))) {
        $badcount = count($badrecords);
        echo "Found $badcount orphan records to fix\n";
        foreach ($badrecords as $badrec) {
            $DB->delete_records('format_page_items', array('id' => $badrec->id));
        }
    } else {
        echo "no bad records.\n";
    }

    // Check page-orphan items and delete them (they are not course visible anyway).

    echo "Search for page-orphan records to fix\n";
    $sql = "
        SELECT
            fpi.id as piid,
            bi.id as bid
        FROM
            {format_page_items} fpi,
            {block_instances} bi
        WHERE
            fpi.blockinstance = bi.id AND
            bi.subpagepattern = 'page-';
    ";

    if ($badrecords = $DB->get_records_sql($sql, array())) {
        $badcount = count($badrecords);
        echo "Found $badcount page-orphan blocks to fix\n";
        foreach ($badrecords as $badrec) {
            $DB->delete_records('format_page_items', array('id' => $badrec->piid));
            $DB->delete_records('block_instances', array('id' => $badrec->bid));
        }
    } else {
        echo "no bad records.\n";
    }
}

function page_format_redraw_sections($course) {
    global $DB;

    // Delete all sections.
    $DB->delete_records_select('course_sections', " course = $course->id AND section != 0 ");

    // Rebuild all section list from page information.
    if ($allpages = course_page::get_all_pages($course->id, 'flat')) {

        $i = 1;
        foreach ($allpages as $page) {
            $page->make_section($i, null, true);
            $page->save();
            $i++;
        }
    }

    rebuild_course_cache($course->id, true);
}