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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
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
            $b->timemodified = time();
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
            for ($i = 0; $i < count($seqarr); $i++) {
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

/**
 * Fix scenario :
 * 0. Read all course sections in the course for crossmapping reference
 * 1. Deletes all course sections in the course
 * 2. Examines all pages for non null cmid page items in order of parent/sortorder
 * 3. Redraw sections from section 1 in order of pages and set name to page name, sequence to GROUP_CONCAT of cmids
 * 4. Add section of sectionnum 0
 * 5. Search for non published course modules that still are in the course and add them to section 0
 *
 * This will NOT fix any section ID used in other components (section availability). this will need to be done further
 */
function page_format_redraw_sections($course, $verbose = false) {
    global $DB;

    // This is used similarily to backup crossids mapping.
    $transformmapping = [];
    $transformmappingbyname = []; // indexed by name (with possible writeover)
    $transformmappingbynum = []; // indexed by sectionnum (should not have writeover, but however possible).
    $select = "
        course = ?
        -- AND deletioninprogress <> 1
    ";
    $coursecmids = $DB->get_records_select_menu('course_modules', $select, [$course->id], 'id,id'); // All registered modules in course.
    $coursecmidsreinserted = [];

    /*
     * A good mapping heuristic is that byname and bynum search point the same oldid.
     */

    // 0
    $oldsections = $DB->get_records('course_sections', ['course' => $course->id]);
    if ($oldsections) {
        if ($verbose) mtrace("Registering old sections.");
        foreach ($oldsections as $s) {
            $s->oldid = $s->id;
            $s->oldnum = $s->section;
            $transformmapping[$s->oldid] = $s;
            $transformmappingbyname[$s->name] = $s->oldid;
            $transformmappingbynum[$s->section] = $s->oldid;
        }
    }

    // 1
    if ($verbose) mtrace("Deleting sections.");
    $DB->delete_records('course_sections', ['course' => $course->id]);

    // 2
    $pages = $DB->get_records('format_page', ['courseid' => $course->id], 'parent, sortorder');
    if ($pages) {
        $i = 1;
        foreach ($pages as $p) {

            if ($verbose) mtrace("Processing page {$p->id}.");
            // Check if an old section matches to complete the mapping info.
            $oldsectionid = null;
            $oldid1 = @$transformmappingbyname[$p->nameone];
            $oldid2 = @$transformmappingbynum[$p->section];
            if ($oldid1  && ($oldid1 == $oldid2)) {
                $oldsectionid = $oldid1;
            }

            $section = new StdClass;
            $section->course = $p->courseid;
            $section->section = $i;
            $section->name = $p->nameone;
            $section->summary = '';
            $section->summaryformat = FORMAT_MOODLE;
            $section->visible = 1;
            $section->availability = null;
            $section->timemodified = time();

            // Search real modules identities.
            $select = ' pageid = ? AND cmid > 0 ';
            $pis = $DB->get_records_select('format_page_items', $select, [$p->id]);
            $sequence = [];
            if ($pis) {
                foreach ($pis as $pi) {
                    if (!array_key_exists($pi->cmid, $coursecmids)) {
                        if ($verbose) mtrace("Page item to a module ($pi->cmid) NOT in course ($course->id) ");
                        continue;
                    }
                    $sequence[] = $pi->cmid;
                    // Unregister collected cmid.
                    if (!in_array($pi->cmid, $coursecmidsreinserted)) {
                        $coursecmidsreinserted[] = $pi->cmid;
                    }
                }
            }

            $section->sequence = implode(',', $sequence);

            if ($oldsectionid) {
                $section->visible = $transformmapping[$oldsectionid]->visible;
                $section->summary = $transformmapping[$oldsectionid]->summary;
                $section->summaryformat = $transformmapping[$oldsectionid]->summaryformat;
                $section->timemodified = $transformmapping[$oldsectionid]->timemodified;
                $section->availability = $transformmapping[$oldsectionid]->availability;
            }

            // Register section.
            $newid = $DB->insert_record('course_sections', $section);

            // Ensure page has good sectionnum.
            $DB->set_field('format_page', 'section', $section->section, ['id' => $p->id]);

            // Now remap all course modules in sequence to that section.
            foreach ($sequence as $cmid) {
                $DB->set_field('course_modules', 'section', $newid, ['id' => $cmid]);
            }

            if ($oldsectionid) {
                $transformmapping[$oldsectionid]->newid = $newid;
            }
            if ($verbose) mtrace("Section {$newid}/{$section->section} recorded.");

            $i++;
        }
    } else {
        if ($verbose) mtrace("No pages found");
    }

    /*
     * get coursecmids / reinserted difference. Result may be empty array.
     * Result is published nowhere modules.
     */
    foreach ($coursecmidsreinserted as $cmid) {
        unset($coursecmids[$cmid]);
    }

    // 4 & 5
    $section0 = new StdClass;
    $section0->course = $course->id;
    $section0->name = null;
    $section0->section = 0;
    $section0->summary = null;
    $section0->summaryformat = 0;
    $section0->visible = 1;
    $section0->timemodified = time();
    $section0->sequence = implode(',', array_keys($coursecmids));
    $section0->id = $DB->insert_record('course_sections', $section0);
    if ($verbose) mtrace("Section {$section0->id}/{$section0->section} recorded.");

    // Now remap all course modules in section0 to that section.
    foreach (array_keys($coursecmids) as $cmid) {
        $DB->set_field('course_modules', 'section', $section0->id, ['id' => $cmid]);
    }

    rebuild_course_cache($course->id);

    if ($verbose) mtrace("Section modules remaped in section 0.");
}