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

use \format\page\course_page;

/*
 * NOTE : We DO NOT resolve the page any more in format. Pagez resolution, prefork and
 * access checks should be perfomed in course/view.php additions. @see customscripts location
 */

// There are a couple processes that need some help via the session... take care of those.
$action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing.
$action = page_handle_session_hacks($page, $course->id, $action);

// Store page in session.
course_page::save_in_session();

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

$editing = $PAGE->user_is_editing();

// Handle format actions.

echo $OUTPUT->container_start('', 'actionform');
$page->prepare_url_action($action, $renderer);
echo $OUTPUT->container_end();

// Make sure we can see this page.
$template = new StdClass;

if (!$page->is_visible() && !$editing) {
    $template->visible = false;
    if ($CFG->forcelogin && ($page->display == FORMAT_PAGE_DISP_PUBLIC)) {
        $template->notification = $OUTPUT->notification(get_string('thispageisblockedforcelogin', 'format_page'));
    } else {
        switch ($page->display) {
            case FORMAT_PAGE_DISP_HIDDEN : {
                $template->notification = $OUTPUT->notification(get_string('thispageisnotpublished', 'format_page'));
                break;
            }
            case FORMAT_PAGE_DISP_PROTECTED : {
                $template->notification = $OUTPUT->notification(get_string('thispageisprotected', 'format_page'));
                break;
            }
            case FORMAT_PAGE_DISP_DEEPHIDDEN : {
                $template->notification = $OUTPUT->notification(get_string('thispageisdeephidden', 'format_page'));
                break;
            }

        }
    }
    echo $OUTPUT->render_from_template('format_page/page', $template);
    echo $OUTPUT->footer();
    die;
}

$template->visible = true;
$template->sectionid = $page->get_section_id();

// Log something more precise than course.
// Event will take current course context.
$event = format_page\event\course_page_viewed::create_from_page($page);
$event->trigger();

// Start of page ouptut.

// Finally, we can print the page.

$editing = $PAGE->user_is_editing();

if ($editing) {
    $template->editingblock = $renderer->print_editing_block($page);
} else {
    if (has_capability('format/page:discuss', $context)) {
        $template->tabs = $renderer->print_tabs('discuss');
    }
}

$template->publishsignals = '';

if (($page->display != FORMAT_PAGE_DISP_PUBLISHED) && ($page->display != FORMAT_PAGE_DISP_PUBLIC)) {
    $template->publishsignals .= get_string('thispageisnotpublished', 'format_page');
}
if ($page->get_user_rules() && has_capability('format/page:editpages', $context)) {
    $template->publishsignals .= ' '.get_string('thispagehasuserrestrictions', 'format_page');
}
if (has_capability('format/page:editprotectedpages', $context) && $page->protected) {
    $template->publishsignals .= ' '.get_string('thispagehaseditprotection', 'format_page');
}

$modinfo = get_fast_modinfo($course);
// Can we view the section in question ?
$pagesection = $DB->get_record('course_sections', array('id' => $page->get_section_id()));
$sectioninfo = $modinfo->get_section_info($pagesection->section);
if ($sectioninfo) {
    $template->publishsignals .= $renderer->section_availability_message($sectioninfo, true);
}

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

if ($hastoppagenav) {
    $template->topnavbuttons = $renderer->page_navigation_buttons($page);
}

$commonclasses = ''; // Unused yet.

if ($hassidepre) {
    $template->preclasses = 'page-block-region bootstrap block-region page-col-'.$prewidthspan;
    $template->preclasses .= ' '.@$classes['prewidthspan'].' desktop-first-column';
    $template->preclasses .= ' '.$commonclasses;
    $template->hassidepre = true;
    $template->sidepreregionblocks = $OUTPUT->blocks_for_region('side-pre');
}

if ($hasmain) {
    $template->mainclasses = 'page-block-region bootstrap block-region page-col-'.$mainwidthspan;
    $template->mainclasses .= ' '.@$classes['mainwidthspan'];
    $template->mainclasses .= ' '.$commonclasses;
    $template->hasmain = true;
    $template->mainregionblocks = $OUTPUT->blocks_for_region('main');
}

if ($hassidepost) {
    $template->postclasses = 'page-block-region bootstrap block-region page-col-'.$postwidthspan;
    $template->postclasses .= ' '.@$classes['postwidthspan'];
    $template->postclasses .= ' '.$commonclasses;
    $template->hassidepost = true;
    $template->sidepostregionblocks = $OUTPUT->blocks_for_region('side-post');
}

if ($hasbottompagenav) {
    $template->bottomnavbuttons = $renderer->page_navigation_buttons($page, '', true);
}

if (empty($template->topnavbuttons) && empty($template->publishsignals)) {
    $template->emptyclass = 'is-empty';
}

if (is_dir($CFG->dirroot.'/local/userequipment')) {
    global $uemodchooserloaded;
    $uemodchooserloaded = false;
    $ueconfig = get_config('local_userequipment');
    if (!empty($ueconfig->useenhancedmodchooser)) {
        $PAGE->requires->js_call_amd('local_userequipment/activitychooser', 'init');
        if (!$uemodchooserloaded) {
            $uerenderer = $PAGE->get_renderer('local_userequipment');
            $template->useenhancedmodchooser = true;
            $template->activitychoosermodal = $uerenderer->render_modchooser();
            $uemodchooserloaded = true;
        }
    }
}

echo $OUTPUT->render_from_template('format_page/page', $template);

course_page::save_in_session();

