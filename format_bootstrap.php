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
 * @category mod
 * @author Valery Fremaux
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/course/format/page/classes/pageitem.class.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');

$id     = optional_param('id', SITEID, PARAM_INT);    // Course ID.
$pageid = optional_param('page', 0, PARAM_INT);       // format_page record ID.
$action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing.

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

// Check out the $pageid - set? valid? belongs to this course?

if (!empty($pageid)) {
    if (empty($page) or $page->id != $pageid) {
        // Didn't get the page above or we got the wrong one...
        if (!$page = course_page::get($pageid)) {
            print_error('errorpageid', 'format_page');
        }
        $page->formatpage->id = $pageid;
    }
    // Ensure this page is in this course.
    if ($page->courseid != $course->id) {
        print_error('invalidpageid', 'format_page', '', $pageid);
    }
} else {
    // We don't have a page ID to work with (probably no pages yet in course).
    if (has_capability('format/page:editpages', $context)) {
        $action = 'editpage';
        $page = new course_page(null);
        if (empty($CFG->customscripts)) {
            print_error('errorflexpageinstall', 'format_page');
        }
    } else {
        // Nothing this person can do about it, error out.
        $PAGE->set_title($SITE->name);
        $PAGE->set_heading($SITE->name);
        echo $OUTPUT->box_start('notifyproblem');
        echo $OUTPUT->notification(get_string('nopageswithcontent', 'format_page'));
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        die;
    }
}

// There are a couple processes that need some help via the session... take care of those.

$action = page_handle_session_hacks($page, $course->id, $action);

$editing = $PAGE->user_is_editing();

if (!$editing && !($page->is_visible())) {
    if ($pagenext = $page->get_next()) {
        $page = $pagenext;
        $pageid = course_page::set_current_page($COURSE->id, $page->id);
    } else if ($pageprevious = $page->get_previous()) {
        $page = $pageprevious;
        $pageid = course_page::set_current_page($COURSE->id, $page->id);
    } else {
        if (!has_capability('format/page:editpages', $context) &&
                !has_capability('format/page:viewhiddenpages', $context)) {
            $PAGE->set_title($SITE->fullname);
            $PAGE->set_heading($SITE->fullname);
            echo $OUTPUT->box_start('notifyproblem');
            echo $OUTPUT->notification(get_string('nopageswithcontent', 'format_page'));
            echo $OUTPUT->box_end();
            echo $OUTPUT->footer();
            die;
        }
    }
}

// Store page in session.
course_page::save_in_session();

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

echo $OUTPUT->box_start('format-page-actionbar clearfix', 'format-page-actionbar');

// Finally, we can print the page.

if ($editing) {
    echo $renderer->print_editing_block($page);
} else {
    if (has_capability('format/page:discuss', $context)) {
        $renderer->print_tabs('discuss');
    }
}
echo $OUTPUT->box_end();

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

$prewidthstyle = '';
$postwidthstyle = '';
$mainwidthstyle = '';
$prewidthspan = $renderer->get_width('side-pre');
$postwidthspan = $renderer->get_width('side-post');
$mainwidthspan = $renderer->get_width('main');

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = $editing || ($prewidthspan > 0);
$hasmain = $editing || ($mainwidthspan > 0);
$hassidepost = $editing || ($postwidthspan > 0);

// Fix editing columns with size 0.

if ($editing) {
    $classes = format_page_fix_editing_width($prewidthspan, $mainwidthspan, $postwidthspan);
}

$haslogininfo = (empty($PAGE->layout_options['nologininfo']));
$hastoppagenav = (empty($PAGE->layout_options['notoppagenav']));
$hasbottompagenav = (empty($PAGE->layout_options['nobottompagenav']));

$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$hasframe = !isset($PAGE->theme->settings->noframe) || !$PAGE->theme->settings->noframe;
$displaylogo = !isset($PAGE->theme->settings->displaylogo) || $PAGE->theme->settings->displaylogo;

echo '<div id="page-region-top" class="page-region bootstrap row-fluid">';

if ($hastoppagenav) {
    echo $renderer->page_navigation_buttons($publishsignals);
} else {
    if (!empty($publishsignals)) {
        echo '<div class="page-publishing span12 col-md-12">'.$publishsignals.'</div>';
    }
}

echo '</div>';

echo '<div id="region-page-box" class="row-fluid">';
if ($hassidepre) {
    $classes = 'page-block-region bootstrap block-region span'.$prewidthspan.' col-md-'.$prewidthspan;
    $classes .= ' '.@$classes['prewidthspan'].' desktop-first-column';
    echo '<div id="region-pre" '.$prewidthstyle.' class="'.$classes.'">';
    echo '<div class="region-content">';
    echo $OUTPUT->blocks_for_region('side-pre');
    echo '</div>';
    echo '</div>';
}

if ($hassidepre) {
    $classes = 'page-block-region bootstrap block-region span'.$mainwidthspan.' col-md-'.$mainwidthspan;
    $classes .= ' '.@$classes['mainwidthspan'];
    echo '<div id="region-main" '.$mainwidthstyle.' class="'.$classes.'">';
    echo '<div class="region-content">';
    echo $OUTPUT->blocks_for_region('main');
    echo '</div>';
    echo '</div>';
}

if ($hassidepost) {
    $classes = 'page-block-region bootstrap block-region span'.$postwidthspan.' col-md-'.$postwidthspan;
    $classes .= ' '.@$classes['postwidthspan'];
    echo '<div id="region-post" '.$postwidthstyle.' class="'.$classes.'">';
    echo '<div class="region-content">';
    echo $OUTPUT->blocks_for_region('side-post');
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '<div id="page-region-bottom" class="page-region bootstrap row-fluid">';

if ($hasbottompagenav) {
    echo $renderer->page_navigation_buttons('');
}

echo '</div>';
course_page::save_in_session();

