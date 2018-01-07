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
 * Page internal check service
 *
 * @package format_page
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */

function page_audit_check_cm_vs_sections($course, $action = '') {
    global $DB, $CFG;

    $sql = "
        SELECT
            cm.id as cmid,
            cm.module as module,
            cm.instance as instance,
            m.name as modname,
            cs.id as sectionid,
            cs.name as sectioname
        FROM
            {modules} m,
            {course_modules} cm
        LEFT JOIN
            {course_sections} cs
        ON
            (cs.id = cm.section OR cs.id IS NULL)
        WHERE
            m.id = cm.module AND
            cm.course = ? AND
            cs.id IS NULL
    ";

    if ('fixorphancms' == $action) {

        $sectionmissrecs = $DB->get_records_sql($sql, array($course->id));

        // Remove cms out of sections. They are not visible.
        foreach ($sectionmissrecs as $cmid => $cm) {
            $module = $DB->get_record('modules', array('id' => $cm->module));
            $deletefunc = $module->name.'_delete_instance';
            include_once($CFG->dirroot.'/mod/'.$module->name.'/lib.php');
            try {
                $deletefunc($cm->instance);
            } catch (Exception $ex) {
                echo "Failed deleting course module $cmid | $module->name <br/>";
            }
            $DB->delete_records('course_modules', array('id' => $cmid));
        }
    }

    $sectionmissrecs = $DB->get_records_sql($sql, array($course->id));

    $sql = "
        SELECT
            cs.id as sectionid,
            cs.name as sectionname,
            cm.id as cmid
        FROM
            {course_modules} cm
        RIGHT JOIN
            {course_sections} cs
        ON
            (cm.id IS NULL OR cs.id = cm.section)
        WHERE
            cs.course = ? AND
            cm.id IS NULL
    ";
    $emptysecs = $DB->get_records_sql($sql, array($course->id));

    $sql = "
        SELECT
            cm.id as cmid,
            cs.id as sectionid,
            cs.name as sectionname,
            m.name as modname
        FROM
            {modules} m,
            {course_modules} cm,
            {course_sections} cs
        WHERE
            cm.module = m.id AND
            cs.id = cm.section AND
            cs.course = ?
    ";
    $regularmods = $DB->get_records_sql($sql, array($course->id));

    $emptysections = array();
    $modnosection = array();
    $regular = array();

    if ($sectionmissrecs) {
        foreach ($sectionmissrecs as $rec) {
            $modnosection[] = $rec->cmid.'|'.$rec->modname;
        }
    }

    if ($emptysecs) {
        foreach ($emptysecs as $sec) {
            $emptysections[] = $sec->sectionid.'|'.$sec->sectionname;
        }
    }

    if ($regularmods) {
        foreach ($regularmods as $rec) {
            $regular[] = $rec->cmid.'|'.$rec->modname;
        }
    }

    return array($emptysections, $regular, $modnosection);
}

/**
 * Page internal check service.
 * Good modules are referenced in sections
 * Bad modules are in base modules that are missing in sections
 *
 * @package format_page
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (valery.fremaux@gmail.com)
 */
function page_audit_check_sections($course) {
    global $DB;

    $sections = $DB->get_records('course_sections', array('course' => $course->id));

    // Get all modules registered in sequences for all the course.
    $allseqmodlistarr = array();
    $sequences = array();
    foreach ($sections as $sec) {
        if (!empty($sec->sequence)) {
            $sequences[$sec->id] = explode(',', $sec->sequence);
            foreach ($sequences[$sec->id] as $modid) {
                if (!empty($modid)) {
                    $allseqmodlistarr[] = $modid;
                }
            }
        }
    }

    $good = array();
    $bad = array();
    $outofcourse = array();
    if (!empty($allseqmodlistarr)) {
        $allseqmodlist = implode(',', $allseqmodlistarr);
        $good = $DB->get_records_select('course_modules', " id IN ($allseqmodlist) AND course = {$course->id} ");
        $bad = $DB->get_records_select('course_modules', " id NOT IN ($allseqmodlist) AND course = {$course->id} ");
        $outofcourse = $DB->get_records_select('course_modules', " id IN ($allseqmodlist) AND course != {$course->id} ");
    }

    return array($good, $bad, $outofcourse);
}

function page_audit_check_page_vs_section($course, $action) {
    global $DB;

    $sql = "
        SELECT DISTINCT
            fp.id,
            fp.nameone as pagename,
            cs.id as sectionid,
            cs.name as sectionname
        FROM
            {format_page} fp
        LEFT JOIN
            {course_sections} cs
        ON
            (cs.course = fp.courseid AND cs.section = fp.section AND cs.section != 0)
        WHERE
            fp.courseid = ?
    ";
    $allpages = $DB->get_records_sql($sql, array($course->id));

    $sql = "
        SELECT DISTINCT
            cs.id as sectionid,
            cs.name as sectionname
        FROM
            {format_page} fp
        RIGHT JOIN
            {course_sections} cs
        ON
            (cs.course = fp.courseid AND cs.section = fp.section AND cs.section != 0) OR fp.id IS NULL
        WHERE
            cs.course = ? AND
            fp.id IS NULL AND
            cs.section != 0
    ";
    $missingsecs = $DB->get_records_sql($sql, array($course->id));

    $pagesnosection = array();
    $regular = array();
    $orphansections = array();
    if ($allpages) {
        foreach ($allpages as $rec) {
            if (empty($rec->sectionid)) {
                $pagesnosection[] = $rec->id.' '.$rec->pagename;
            } else {
                $regular[] = $rec->id.'|'.$rec->sectionid.' '.$rec->pagename;
            }
        }
    }
    if ($missingsecs) {
        foreach ($missingsecs as $rec) {
            $orphansections[] = $rec->sectionid.'|'.$rec->sectionname;
        }
    }

    return array($orphansections, $regular, $pagesnosection);
}

function page_audit_check_pageitem_vs_module($course, $action) {
    global $DB;

    $sql = "
        SELECT
            fpi.id,
            cm.id as modid
        FROM
            {format_page} fp,
            {format_page_items} fpi
        LEFT JOIN
            {course_modules} cm
        ON
            (cm.id = fpi.cmid OR cm.id IS NULL)
        WHERE
            fp.courseid = ? AND
            cm.course = ? AND
            fpi.pageid = fp.id AND
            fpi.cmid != 0
    ";
    $allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

    $pageitemsnomodule = array();
    $regular = array();
    $emptypages = array();
    if ($allrecs) {
        foreach ($allrecs as $rec) {
            if (empty($rec->id)) {
                $emptypages[] = $rec->modid;
            } else if (empty($rec->modid)) {
                if ($action == 'fixbadmodpageitems') {
                    $DB->delete_records('format_page_items', array('id' => $rec->id));
                } else {
                    $pageitemsnomodule[] = $rec->id;
                }
            } else {
                $regular[] = $rec->id.'|cm'.$rec->modid;
            }
        }
    }

    return array($emptypages, $regular, $pageitemsnomodule);
}

function page_audit_check_pageitem_vs_block($course, $action) {
    global $DB;

    $sql = "
        SELECT
            fpi.id,
            fpi.cmid,
            fpi.blockinstance,
            bi.id as blockid,
            bi.blockname
        FROM
            {format_page} fp,
            {format_page_items} fpi
        LEFT JOIN
            {block_instances} bi
        ON
            bi.blockname != 'page_module' AND
            (bi.id = fpi.blockinstance OR bi.id IS NULL)
        WHERE
            fp.courseid = ? AND
            fpi.pageid = fp.id AND
            fpi.cmid = 0
    ";
    $allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

    $pageitemsnoblock = array();
    $regular = array();
    $emptypages = array();
    if ($allrecs) {
        foreach ($allrecs as $rec) {
            if (empty($rec->id)) {
                $emptypages[] = $rec->blockid.' '.$rec->blockname;
            } else if (empty($rec->blockid)) {
                if ($action == 'fixbadpageitems') {
                    $DB->delete_records('format_page_items', array('id' => $rec->id));
                } else {
                    $pageitemsnoblock[] = $rec->id.'|bi'.$rec->blockinstance;
                }
            } else {
                $regular[] = $rec->id.'|bi'.$rec->blockid;
            }
        }
    }

    return array($emptypages, $regular, $pageitemsnoblock);
}

function page_audit_check_block_vs_pageitem($course, $action) {
    global $DB;

    $sql = "
        SELECT
            bi.id as blockid,
            bi.blockname,
            fpi.id,
            fpi.cmid,
            fpi.blockinstance
        FROM
            {format_page} fp,
            {format_page_items} fpi
        RIGHT JOIN
            {block_instances} bi
        ON
            bi.blockname != 'page_module' AND
            (bi.id = fpi.blockinstance OR fpi.id IS NULL)
        WHERE
            fp.courseid = ? AND
            fpi.pageid = fp.id AND
            (fpi.blockinstance != 0 OR fpi.blockinstance IS NULL)
    ";
    $allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

    $blocksnopageitem = array();
    if ($allrecs) {
        foreach ($allrecs as $rec) {
            if (empty($rec->id)) {
                $blocksnopageitem[] = $rec->blockid;
            }
        }
    }

    return $blocksnopageitem;
}