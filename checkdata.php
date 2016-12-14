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
require('../../../config.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);
$action = optional_param('what', '', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login();
$context = context_course::instance($course->id);
require_capability('moodle/site:config', $context);

// Set course display.

$url = new moodle_url('/course/format/page/checkdata.php', array('page' => $pageid, 'id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->heading('Orphan course modules / Bad course section ID');

$sql = "
    SELECT
        cm.id,
        cs.id as sectionid,
        cs.name as sectioname
    FROM
        {course_modules} cm
    LEFT JOIN
        {course_sections} cs
    ON
        (cm.id IS NULL OR cs.id = cm.section OR cs.id IS NULL)
    WHERE
        cm.course = ? AND
        cs.course = ?
";
$allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

$emptysections = array();
$modnosection = array();
$regular = array();
if ($allrecs) {
    foreach ($allrecs as $rec) {
        if (empty($rec->id)) {
            $emptysections[] = $rec->sectionid;
        } else if (empty($rec->sectionid)) {
            $modnosection[] = $rec->id;
        } else {
            $regular[] = $rec->id;
        }
    }
}

echo '<div class="error">Empty sections :<br/>'.implode(', ', $emptysections).'</div>';
echo '<div class="good">Regular modules ('.count($regular).') :<br/>'.implode(', ', $regular).'</div>';
echo '<div class="error">Orphan modules :<br/> '.implode(', ', $modnosection).'</div>';

echo $OUTPUT->heading('Orphan course modules / Not in Course Sections Sequence');

$sections = $DB->get_records('course_sections', array('course' => $course->id));

$sequences = array();
foreach ($sections as $secid => $section) {
    $sequences[$secid] = explode(',', $section->sequence);
}

list($good, $bad, $outofcourse) = page_audit_check_sections($course);

if ('fixbadcms' == $action) {
    /*
     * Fix all bad items removing them from sequences and store back sequences into course. 
     * the empty the $bad bag.
     */
    mtrace('Fixing bad cms...');

    foreach ($sequences as $secid => $sequ) {
        mtrace("Fixing section $secid ");
        $fixedsequ = array();
        foreach ($sequ as $cmid) {
            if (!in_array($cmid, array_keys($bad))) {
                $fixedsequ[] = $cmid;
            }
        }
        $fixedsequlist = implode(',', $fixedsequ);
        $section = new Stdclass();
        $section->id = $secid;
        $section->sequence = $fixedsequlist;
        $DB->update_record('course_sections', $section);
    }

    // Deleting all course_modules and activities that are not registered in sequences.
    if (!empty($bad)) {
        mtrace("Deleting bad instances ");
        foreach (array_keys($bad) as $badcmid) {
            mtrace("Deleting bad instance $badcmid ");
            $cm = $DB->get_record('course_modules', array('id' => $badcmid));
            if ($cm) {
                $module = $DB->get_record('modules', array('id' => $cm->module));
                $deletefunc = $module->name.'_delete_instance';
                include_once($CFG->dirroot.'/mod/'.$module->name.'/lib.php');
                try {
                    $deletefunc($cm->instance);
                } catch (Exception $ex) {
                }
                mtrace("Deleting cm $cm->id ");
                $DB->delete_records('course_modules', array('id' => $cm->id));
            }
        }
    }

    // Refresh data from DB.
    list($good, $bad, $outofcourse) = page_audit_check_sections($course);
}

if ('fixoutofcourse' == $action) {

    // Fix all bad items removing them from sequences and store back sequences into course.
    foreach ($sequences as $secid => $sequ) {
        $fixedsequ = array();
        foreach ($sequ as $cmid) {
            if (!in_array($cmid, array_keys($outofcourse))) {
                $fixedsequ[] = $cmid;
            }
        }
        $fixedsequlist = implode(',', $fixedsequ);
        $section = new Stdclass();
        $section->id = $secid;
        $section->sequence = $fixedsequlist;
        $DB->update_record('course_sections', $section);
    }

    // Refresh data from DB.
    list($good, $bad, $outofcourse) = page_audit_check_sections($course);
}

foreach ($bad as $badid => $b) {
    $modname = $DB->get_field('modules', 'name', array('id' => $b->module));
    if ($section = $DB->get_record('course_sections', array('id' => $b->section))) {
        $binfo = $b->course.'|'.$b->section.' '.$modname.' Mod section course : '.$section->course;
    } else {
        $binfo = $b->course.'|'.$b->section.' '.$modname.' !! Broken section !!';
    }
    $bad[$badid] = '<a href="" title="'.$binfo.'">'.$badid.'</a>';
}

foreach ($outofcourse as $badid => $b) {
    $modname = $DB->get_field('modules', 'name', array('id' => $b->module));
    if ($section = $DB->get_record('course_sections', array('id' => $b->section))) {
        $binfo = $b->course.'|'.$b->section.' '.$modname.' Mod section course : '.$section->course;
    } else {
        $binfo = $b->course.'|'.$b->section.' '.$modname.' !! Broken section !!';
    }
    $outofcourse[$badid] = '<a href="" title="'.$binfo.'">'.$badid.'</a>';
}

echo '<div class="cmaudit good"> Good modules : '.implode(', ',array_keys($good)).'</div>';
$fixbutton = '';
if (!empty($bad)) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadcms'));
    $fixbutton = $OUTPUT->single_button($buttonurl, 'Fix bad cms');
}
echo '<div class="cmaudit bad"> Bad modules : '.$fixbutton.'<br>'.implode(', ',$bad).'</div>';
$fixbutton = '';
if (!empty($outofcourse)) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixoutofcourse'));
    $fixbutton = $OUTPUT->single_button($buttonurl, 'Remove out of course');
}
echo '<div class="cmaudit outofcourse"> Out of course section modules : '.$fixbutton.'<br/>'.implode(', ',$outofcourse).'</div>';
echo '<br/>';

foreach ($sections as $sec) {
    $sequence = explode(',', $sec->sequence);
    echo $sec->id.' '.$sec->name.' ('.$sec->section.') : ';
    foreach($sequence as $seqmod) {
        if (array_key_exists($seqmod, $bad)) {
            $class = "error";
        } else if (array_key_exists($seqmod, $good)) {
            $class = "green";
        } else {
            $class = "notincourse";
        }
        echo '<div class="'.$class.'" style="display:inline-block">'.$seqmod.'</div>';
    }
    echo '<br/>';
}

echo $OUTPUT->heading('Orphan sections  / pages');

$sql = "
    SELECT
        fp.id,
        fp.nameone as pagename,
        cs.id as sectionid,
        cs.name as sectioname
    FROM
        {format_page} fp
    LEFT JOIN
        {course_sections} cs
    ON
        cs.section = fp.section
    WHERE
        fp.courseid = ? AND
        cs.course = ?
    UNION
    SELECT
        fp.id,
        fp.nameone as pagename,
        cs.id as sectionid,
        cs.name as sectioname
    FROM
        {format_page} fp
    RIGHT JOIN
        {course_sections} cs
    ON
        cs.section = fp.section
    WHERE
        fp.courseid = ? AND
        cs.course = ?
";
$allrecs = $DB->get_records_sql($sql, array($course->id, $course->id, $course->id, $course->id));

$pagenosection = array();
$regular = array();
$emtysections = array();
if ($allrecs) {
    foreach ($allrecs as $rec) {
        if (empty($rec->id)) {
            $emptysections[] = $rec->sectionid.' '.$rec->sectionname;
        } else if (empty($rec->sectionid)) {
            $pagenosection[] = $rec->id.' '.$rec->pagename;
        } else {
            $regular[] = $rec->id.'|'.$rec->sectionid.' '.$rec->pagename;
        }
    }
}

echo '<div class="error">Orphan sections : <br/>'.implode(', ',$emptysections).'</div>';
echo '<div class="good">Regular pages ('.count($regular).') :<br/>'.implode(', ', $regular).'</div>';
echo '<div class="error">Orphan pages :<br/>'.implode(', ', $pagenosection).'</div>';

echo $OUTPUT->heading('Orphan page items / modules');

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

$pageitemnomodule = array();
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
                $pageitemnomodule[] = $rec->id;
            }
        } else {
            $regular[] = $rec->id.'|cm'.$rec->modid;
        }
    }
}

echo '<div class="error">Empty (no mods) page : <br/>'.implode(', ', $emptypages).'</div>';
echo '<div class="good">Regular cm page items ('.count($regular).') : <br/>'.implode(', ', $regular).'</div>';
echo '<div class="error">Orphan cm page items : '.implode(', ', $pageitemnomodule).'</div>';

if ($pageitemnomodule) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadmodpageitems'));
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan Module page items');
}

echo $OUTPUT->heading('Modules with no page items (unpublished)');

$sql = "
    SELECT
        cm.id as modid,
        fpi.id
    FROM
        {course_modules} cm
    RIGHT JOIN
        {format_page_items} fpi
    ON
        cm.id = fpi.cmid OR fpi.id IS NULL
    LEFT JOIN
        {format_page} fp
    ON
        fpi.pageid = fp.id
    WHERE
        fp.courseid = ? AND
        cm.course = ? AND
        (fpi.cmid != 0 OR fpi.cmid IS NULL)
";
$allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

$modulesnopageitem = array();
$regular = array();
$emptypages = array();
if ($allrecs) {
    foreach ($allrecs as $rec) {
        if (empty($rec->modid)) {
            $modulesnopageitem[] = $rec->id;
        } else {
            $regular[] = $rec->modid.'|fpi'.$rec->id;
        }
    }
}

echo '<div class="good">Published modules ('.count($regular).') : <br/>'.implode(', ', $regular).'</div>';
echo '<div class="">Unpublished modules : '.implode(', ', $modulesnopageitem).'</div>';

echo $OUTPUT->heading('Orphan page items / blocks');

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

$pageitemnoblocks = array();
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
                $pageitemnoblocks[] = $rec->id.'|bi'.$rec->blockinstance;
            }
        } else {
            $regular[] = $rec->id.'|bi'.$rec->blockid;
        }
    }
}

echo '<div class="error">Empty (no blocks) page : <br/>'.implode(', ', $emptypages).'</div>';
echo '<div class="good">Regular page items ('.count($regular).') : <br/>'.implode(', ', $regular).'</div>';
echo '<div class="error">Orphan page items : <br/>'.implode(', ', $pageitemnoblocks).'</div>';

if ($pageitemnoblocks) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadpageitems'));
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan page items');
}

echo $OUTPUT->heading('Blocks without page items');

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
$regular = array();
$emptypages = array();
if ($allrecs) {
    foreach ($allrecs as $rec) {
        if (empty($rec->id)) {
            $blocksnopageitem[] = $rec->blockid;
        } else {
            $regular[] = $rec->blockid.'|fpi'.$rec->id;
        }
    }
}

echo '<div class="error">Empty (no blocks) page : <br/>'.implode(', ', $emptypages).'</div>';
echo '<div class="good">Regular page items ('.count($regular).') : <br/>'.implode(', ', $regular).'</div>';
echo '<div class="error">Orphan page items : <br/>'.implode(', ', $blocksnopageitem).'</div>';


echo $OUTPUT->heading('Orphan activity instances / course modules');

$modules = $DB->get_records('modules');

$badmodinstances = array();
foreach ($modules as $module) {

    $sql = "
        SELECT
            i.*
        FROM
            {{$module->name}} i
        LEFT JOIN
            {course_modules} cm
        ON
            cm.instance = i.id AND
            (cm.module = ? OR cm.module IS NULL)
        WHERE
            i.course = ? AND
            cm.id IS NULL
    ";

    $badinstances = $DB->get_records_sql($sql, array($module->id, $id));

    $deletefunc = $module->name.'_delete_instance';
    include_once($CFG->dirroot.'/mod/'.$module->name.'/lib.php');

    if ($badinstances) {
        $badmodinstances[$module->name] = $badinstances;
        if ($action == 'fixbadinstances') {
            mtrace("destroying instances of $module->name ");
            foreach ($badinstances as $binst) {
                try {
                    /*
                     * This may not be sufficiant, some course module have made their
                     * internal deletion much more complex, relying f.e. on cm.
                     */
                    mtrace("destroying instance $module->name $binst->id ");
                    $deletefunc($binst->id);
                    // If not concluant.
                    $DB->delete_records($module->name, array('id' => $binst->id));
                } catch (Exception $ex) {
                    $DB->delete_records($module->name, array('id' => $binst->id));
                }
            }

            // Refresh structures for further display
            $badinstances = $DB->get_records_sql($sql, array($module->id, $id));
            if ($badinstances) {
                $badmodinstances[$module->name] = $badinstances;
            }
        }
    } else {
        echo '<div class="good">Good instances : '.$module->name.'</div>';
    }
}

if (!empty($badmodinstances)) {
    foreach ($badmodinstances as $modname => $badinsts) {
        echo '<div class="error">Orphan '.$modname.' instances : <br/>'.implode(', ', array_keys($badinsts)).'</div>';
    }
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadinstances'));
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan instances');

} else {
    echo '<div class="good">No bad instances</div>';
}

echo '<center>';
echo $OUTPUT->single_button(new moodle_url('/course/view.php?id='.$course->id), 'Back to course');
echo '</center>';

echo $OUTPUT->footer();