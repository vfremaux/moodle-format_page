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
 * Main hook from moodle into the course format
 *
 * @package format_page
 * @category format
 * @author Jeff Graham, Mark Nielsen
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @todo Swich to the use of $PAGE->user_allowed_editing()
 * @todo Next/Previous breaks when three columns are not printed - Perhaps they should not be part of the main table
 * @todo Core changes wish list:
 *           - Remove hard-coded left/right block position references
 *           - Provide a better way for formats to say, "Hey, backup these blocks" or open up the block instance
 *             backup routine and have the format backup its own blocks.
 *           - With the above two, we could have three columns and multiple independent pages that are compatible with core routines.
 *           - http://tracker.moodle.org/browse/MDL-10265 these would help with performance and control
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/pageitem.class.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');

/*
 * NOTE : We DO NOT resolve the page any more in format. Pagez resolution, prefork and
 * access checks should be perfomed in course/view.php additions. @see customscripts location
 */

// There are a couple processes that need some help via the session... take care of those.

$action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing.
$action = page_handle_session_hacks($page, $course->id, $action);

// Store page in session.

course_page::save_in_session();

$editing = $PAGE->user_is_editing();

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

// Handle format actions.

echo $OUTPUT->container_start('', 'actionform');
$page->prepare_url_action($action, $renderer);
echo $OUTPUT->container_end();

// Make sure we can see this page.

if (!$page->is_visible() && !$editing) {
    echo $OUTPUT->notification(get_string('thispageisnotpublished', 'format_page'));
    echo $OUTPUT->footer();
    die;
}

// Log something more precise than course.

// Event will take current course context.
$event = format_page\event\course_page_viewed::create_from_page($page);
$event->trigger();

// Start of page ouptut.

echo $OUTPUT->box_start('', 'format-page-content');
echo $OUTPUT->box_start('format-page-actionbar clearfix', 'format-page-actionbar');

// Finally, we can print the page.

if ($editing) {
    echo $renderer->print_editing_block($page);
} else {
    if (has_capability('format/page:discuss', $context)) {
        $renderer->print_tabs('discuss');
    }
}

$publishsignals = '';
if (($page->display != FORMAT_PAGE_DISP_PUBLISHED) && ($page->display != FORMAT_PAGE_DISP_PUBLIC)) {
    $publishsignals .= get_string('thispageisnotpublished', 'format_page');
}
if ($page->get_user_rules() && has_capability('format/page:editpages', $context)) {
    $publishsignals .= ' '.get_string('thispagehasuserrestrictions', 'format_page');
}
if (has_capability('format/page:editprotectedpages', $context) && $page->protected) {
    $publishsignals .= ' '.get_string('thispagehaseditprotection', 'format_page');
}

$modinfo = get_fast_modinfo($course);
// Can we view the section in question ?
$sectionnumber = $DB->get_field('course_sections', 'section', array('id' => $page->get_section()));
$sectioninfo = $modinfo->get_section_info($sectionnumber);
if ($sectioninfo) {
    $publishsignals .= $renderer->section_availability_message($sectioninfo, true);
}

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']));
$hassidepost = (empty($PAGE->layout_options['noblocks']));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));
$hastoppagenav = (empty($PAGE->layout_options['notoppagenav']));
$hasbottompagenav = (empty($PAGE->layout_options['nobottompagenav']));

$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$hasframe = !isset($PAGE->theme->settings->noframe) || !$PAGE->theme->settings->noframe;
$displaylogo = !isset($PAGE->theme->settings->displaylogo) || $PAGE->theme->settings->displaylogo;

$prevbutton = $renderer->previous_button();
$nextbutton = $renderer->next_button();

echo $OUTPUT->box_end();
if ($hastoppagenav) {
    if ($nextbutton || $prevbutton) {
        echo $renderer->page_navigation_buttons($publishsignals);
    }
} else {
    if (!empty($publishsignals)) {
        echo '<div class="page-publishing">'.$publishsignals.'</div>';
    }
}

echo '<div id="region-page-box">';
echo '<table id="region-page-table" width="100%">';
echo '<tr valign="top">';
if ($hassidepre) {
    $classes = 'page-block-region block-region tabular';
    echo '<td id="page-region-pre" class="'.$classes.' width="'.$renderer->get_width('side-pre').'">';
    echo '<div class="region-content">';
    echo $OUTPUT->blocks_for_region('side-pre');
    echo '</div>';
    echo '</td>';
}

$classes = 'page-block-region block-region tabular';
echo '<td id="page-region-main" class="'.$classes.'" width="'.$renderer->get_width('main').'">';
echo '<div class="region-content">';
echo $OUTPUT->blocks_for_region('main');
echo '</div>';
echo '</td>';

if ($hassidepost) {
    $classes = 'page-block-region block-region tabular';
    echo '<td id="page-region-post" class="'.$classes.'" width="'.$renderer->get_width('side-post').'">';
    echo '<div class="region-content">';
    echo $OUTPUT->blocks_for_region('side-post');
    echo '</div>';
    echo '</td>';
}
echo '</table>';
echo '</div>';

if ($hasbottompagenav) {
    if ($nextbutton || $prevbutton) {
        echo $renderer->page_navigation_buttons('', true);
    }
}

echo $OUTPUT->box_end();

course_page::save_in_session();

