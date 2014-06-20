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
 * Page reorganisation service
 * 
 * @package format_page
 * @author Jeff Graham, Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/renderers.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

// Security.

require_login($course);
require_capability('format/page:managepages', $context);

// Set course display.

if ($pageid > 0) {
    // Changing page depending on context.
    $pageid = course_page::set_current_page($course->id, $pageid);
} else {
    if ($page = course_page::get_current_page($course->id)) {
        $displayid = $page->id;
    } else {
        $displayid = 0;
    }
    $pageid = course_page::set_current_page($course->id, $displayid);
}

$url = $CFG->wwwroot.'/course/format/page/actions/moving.php?id='.$course->id;

$PAGE->set_url($url); // Defined here to avoid notices on errors etc
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');

$renderer = new format_page_renderer($page);

if ($service = optional_param('service', '', PARAM_TEXT)) {
    include 'moving.dhtmlxcontroller.php';
}

$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxcommon.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/ext/dhtmlxtree_start.js', true);
$PAGE->requires->js('/course/format/page/js/dhtmlxDataProcessor/codebase/dhtmlxdataprocessor.js', true);

echo $OUTPUT->header();

echo $renderer->print_tabs('manage', true);

// Starts page content here.

echo '<table width="100%"><tr valign="top"><td width="50%">';    

echo $OUTPUT->box_start();
echo "<div id=\"pagestree\" ></div>";
$OUTPUT->box_end();

echo '</td><td>';

echo $OUTPUT->box_start();
print_string('reorder_help', 'format_page');
$OUTPUT->box_end();

echo '</td></tr></table>';
echo '<center>';
$opts['id'] = $course->id;
echo $OUTPUT->single_button(new moodle_url($CFG->wwwroot.'/course/format/page/actions/manage.php?id=', $opts), get_string('manage', 'format_page'), 'get');
echo '</center>';
?>
<script type="text/Javascript">
    tree = new dhtmlXTreeObject('pagestree', '100%', '100%', 0);
    tree.setImagePath("<?php echo $CFG->wwwroot ?>/course/format/page/js/dhtmlxTree/codebase/imgs/csh_yellowbooks/"); 
    tree.loadXML('<?php echo $url."&service=load" ?>');
    tree.enableDragAndDrop(true, true);

    var serverProcessorURL = "<?php echo $url.'&service=dhtmlxprocess' ?>";
    pagePositionProcessor = new dataProcessor(serverProcessorURL);
    pagePositionProcessor.init(tree);     
</script>

<?php
echo $OUTPUT->footer();

// local functions.

function feed_tree_rec($page) {
    $filtered = str_replace('&', '&amp;', $page->nametwo);
    $filtered = str_replace('"', '\'\'', $filtered);
    
    if (!empty($page->childs)) {
        echo '<item child="1" text="'.$filtered.'" open="1" id="'.$page->id.'" >'."\n";
        foreach ($page->childs as $child) {
            feed_tree_rec($child);
        }
        echo '</item>'."\n";
    } else {
        echo '<item child="0" text="'.$filtered.'" open="1" id="'.$page->id.'" />'."\n";
    }
}

function page_xml_tree($course) {
    $allpages = course_page::get_all_pages($course->id, 'nested');

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    echo '<tree id="0">'."\n";

    if (!empty($allpages)) {
        foreach($allpages as $page) {
            $filtered = str_replace('&', '&amp;', $page->nametwo);
            $filtered = str_replace('"', '\'\'', $filtered);

            if (!empty($page->childs)) {
                echo '<item child="1" text="'.$filtered.'" open="1" id="'.$page->id.'">'."\n";
                foreach ($page->childs as $child) {
                    feed_tree_rec($child);
                }
                echo '</item>'."\n";
            } else {
                echo '<item child="0" text="'.$filtered.'" open="1" id="'.$page->id.'" />'."\n";
            }
        }
    }
    echo '</tree>';
}

function page_send_dhtmlx_answer($action, $iid, $oid) {
    switch($action)c{
        case 'updated':
            $actionstr = 'update';
        default:
            $actionstr = 'updated';
    }

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    echo '<data>';
    echo "<action type=\"$actionstr\" sid=\"$iid\" tid=\"$oid\" />";
    echo "</data>";
}
