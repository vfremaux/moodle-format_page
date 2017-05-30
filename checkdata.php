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
require_once($CFG->dirroot.'/course/format/page/tests/testlib.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);
$action = optional_param('what', '', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('format/page:checkdata', $context);

// Set course display.

$url = new moodle_url('/course/format/page/checkdata.php', array('page' => $pageid, 'id' => $course->id));

$PAGE->set_url($url); // Defined here to avoid notices on errors etc.
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('pagedatacheck', 'format_page'));

// Start page content.

echo $OUTPUT->header();

echo $OUTPUT->heading('Orphan course modules / Bad course section ID');

list($emptysections, $regular, $modsnosection) = page_audit_check_cm_vs_sections($course, $action);

echo '<div class="checkdata-result good">Empty sections :<br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $emptysections).'</div></div>';
echo '<div class="checkdata-result good">Regular modules ('.count($regular).') :<br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $regular).'</div></div>';

if (!empty($modsnosection)) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixorphancms'));
    $fixbutton = $OUTPUT->single_button($buttonurl, 'Fix orphan course module (missing section)');
    echo '<div class="checkdata-result error">Orphan modules :<br/> ';
    echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $modsnosection).'</div></div>';
    echo '<br/>'.$fixbutton.'</div>';
    echo $OUTPUT->notification(get_string('removebadcmssectionmodules_help', 'format_page'));
}

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

echo '<div class="checkdata-result good"> Good modules : ';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', array_keys($good)).'</div></div>';

$fixbutton = '';
if (!empty($bad)) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadcms'));
    $fixbutton = $OUTPUT->single_button($buttonurl, 'Fix bad cms');
    echo '<div class="checkdata-result error"> Bad modules : ';
    echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $bad).'</div></div>';
    echo '<br>'.$fixbutton.'</div>';
    echo $OUTPUT->notification(get_string('removebadcmssectionmodules_help', 'format_page'));
}

$fixbutton = '';
if (!empty($outofcourse)) {
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixoutofcourse'));
    $fixbutton = $OUTPUT->single_button($buttonurl, 'Remove out of course');
    echo '<div class="checkdata-result outofcourse"> Out of course section modules : ';
    echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $outofcourse).'</div></div>';
    echo '<br/>'.$fixbutton.'</div>';
    echo $OUTPUT->notification(get_string('removeoutofcoursemodules_help', 'format_page'));
}
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
        echo '<div class="'.$class.'" style="display:inline-block">'.$seqmod.'</div> ';
    }
    echo '<br/>';
}

echo $OUTPUT->heading('Orphan sections vs. pages');

list($orphansections, $regular, $pagesnosection) = page_audit_check_page_vs_section($course, $action);

if (!empty($orphansections)) {
    echo '<div class="checkdata-result error">Orphan sections : <br/>';
    echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $orphansections).'</div></div>';
}

echo '<div class="checkdata-result good">Regular pages ('.count($regular).') :<br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $regular).'</div></div>';

if (!empty($pagesnosection)) {
    echo '<div class="checkdata-result error">Orphan pages :<br/>'.implode(', ', $pagesnosection).'</div>';
}

echo $OUTPUT->heading('Orphan page items / modules');

list($emptypages, $regular, $pageitemsnomodule) = page_audit_check_pageitem_vs_module($course, $action);

echo '<div class="checkdata-result good">Empty (no mods) page : <br/>'.implode(', ', $emptypages).'</div>';
echo '<div class="checkdata-result good">Regular cm page items ('.count($regular).') : <br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $regular).'</div></div>';

if (!empty($pageitemsnomodule)) {
    echo '<div class="checkdata-result error">Orphan cm page items : '.implode(', ', $pageitemsnomodule).'</div>';
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadmodpageitems'));
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan Module page items');
    echo $OUTPUT->notification(get_string('removeorphanfpimodules_help', 'format_page'));
}

echo $OUTPUT->heading('Modules with no page items (unpublished)');

$sql = "
    SELECT
        cm.id as modid,
        GROUP_CONCAT(DISTINCT fpi.id SEPARATOR ', ') as fpis
    FROM
        {course_modules} cm
    LEFT JOIN
        {format_page_items} fpi
    ON
        cm.id = fpi.cmid OR fpi.id IS NULL
    JOIN
        {format_page} fp
    ON
        fpi.pageid = fp.id
    WHERE
        fp.courseid = ? AND
        cm.course = ?
    GROUP BY
        cm.id
";
$allrecs = $DB->get_records_sql($sql, array($course->id, $course->id));

$modulesnopageitem = array();
$regular = array();
$emptypages = array();
if ($allrecs) {
    foreach ($allrecs as $rec) {
        if (empty($rec->modid)) {
            $modulesnopageitem[] = $rec->modid;
        } else {
            $regular[] = $rec->modid.'|fpi'.$rec->fpis;
        }
    }
}

echo '<div class="checkdata-result good">Published modules ('.count($regular).') : <br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $regular).'</div></div>';
echo '<div class="checkdata-result ">Unpublished modules : ';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $modulesnopageitem).'</div></div>';

echo $OUTPUT->notification(get_string('unpublishedmodules_help', 'format_page'), 'notifysuccess');

echo $OUTPUT->heading('Orphan page items / blocks');

list($emptypages, $regular, $pageitemsnoblock) = page_audit_check_pageitem_vs_block($course, $action);

echo '<div class="checkdata-result good">Pages without blocks : <br/>'.implode(', ', $emptypages).'</div>';
echo '<div class="checkdata-result good">Regular page items ('.count($regular).') : <br/>';
echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $regular).'</div></div>';

list($blocksnopageitem) = page_audit_check_block_vs_pageitem($course, $action);

if ($pageitemsnoblock) {
    echo '<div class="checkdata-result error">Orphan page items : <br/>';
    echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', $pageitemsnoblock).'</div></div>';
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadpageitems'));
    echo '<br/>';
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan page items');
    echo $OUTPUT->notification(get_string('removeorphanfpiblocks_help', 'format_page'));
}
if ($blocksnopageitem) {
    echo '<div class="checkdata-result error">Orphan blocks : <br/>'.implode(', ', $blocksnopageitem).'</div>';
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixorphanblocks'));
    echo '<br/>';
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan blocks');
    echo $OUTPUT->notification(get_string('removeorphanblocks_help', 'format_page'));
}

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
        echo '<div class="checkdata-result-module good">Good instances : '.$module->name.'</div>';
    }
}

if (!empty($badmodinstances)) {
    foreach ($badmodinstances as $modname => $badinsts) {
        echo '<div class="checkdata-result-module error">Orphan '.$modname.' instances : <br/>';
        echo '<div class="checkdata-item">'.implode('</div> <div class="checkdata-item">', array_keys($badinsts)).'</div></div>';
    }
    $buttonurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $course->id, 'what' => 'fixbadinstances'));
    echo $OUTPUT->single_button($buttonurl, 'Remove orphan instances');
    echo $OUTPUT->notification(get_string('removeorphaninstances_help', 'format_page'));
}

echo '<center>';
echo $OUTPUT->single_button(new moodle_url('/course/view.php?id='.$course->id), 'Back to course');
echo '</center>';

echo $OUTPUT->footer();