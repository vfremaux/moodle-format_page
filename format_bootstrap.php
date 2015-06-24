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
 * @author Jeff Graham, Mark Nielsen
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @todo Swich to the use of $PAGE->user_allowed_editing()
 * @todo Next/Previous breaks when three columns are not printed - Perhaps they should not be part of the main table
 * @todo Core changes wish list:
 *           - Remove hard-coded left/right block position references
 *           - Provide a better way for formats to say, "Hey, backup these blocks" or open up the block instance backup routine and have the format backup its own blocks.
 *           - With the above two, we could have three columns and multiple independent pages that are compatible with core routines.
 *           - http://tracker.moodle.org/browse/MDL-10265 these would help with performance and control
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/page.class.php');
require_once($CFG->dirroot.'/course/format/page/pageitem.class.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/xlib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');

$id     = optional_param('id', SITEID, PARAM_INT);    // Course ID
$pageid = optional_param('page', 0, PARAM_INT);       // format_page record ID
$action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing

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
    } elseif ($pageprevious = $page->get_previous()) {
        $page = $pageprevious;
        $pageid = course_page::set_current_page($COURSE->id, $page->id);
    } else {
        if (!has_capability('format/page:editpages', $context) && !has_capability('format/page:viewhiddenpages', $context)) {
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

// store page in session.

page_save_in_session();

// check if page has no override.

if (!$editing && $page->cmid) {
    redirect($page->url_get_path($page->id));
}

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

// add_to_log($course->id, 'course', 'viewpage', "view.php?id=$course->id", "$course->id:$pageid");

// Event will take current course context
$event = format_page\event\course_page_viewed::create_from_page($page);
$event->trigger();

// Start of page ouptut.

echo $OUTPUT->box_start('format-page-actionbar clearfix', 'format-page-actionbar');

// Finally, we can print the page.

if ($editing) {
    echo $OUTPUT->box_start('', 'format-page-editing-block');

    echo $renderer->print_tabs('layout', true);

    echo '<div class="container-fluid">';
    echo '<div class="row-fluid">';
    echo '<div class="span4">';
    print_string('navigation', 'format_page');
    echo '<br>';
    echo '<br>';
    print_string('setcurrentpage', 'format_page');
    echo '<br>';
    echo $renderer->print_jump_menu();
    echo '</div><div class="span4">';
    print_string('additem', 'format_page');
    echo '<br>';
    echo '<br>';
    echo $renderer->print_add_mods_form($COURSE, $page);
    echo '</div><div class="span4">';
    print_string('createitem', 'format_page');
    echo '<br>';
    echo '<br>';
    $modnames = get_module_types_names(false);

    $renderer->print_section_add_menus($COURSE, $pageid, $modnames, true, false, true);
    echo '</div></div><div class="row-fluid"></div>';

    echo $OUTPUT->box_end();
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
if ($page->get_group_rules() && has_capability('format/page:editpages', $context)) {
    $publishsignals .= ' '.get_string('thispagehasgrouprestrictions', 'format_page');
}
if (!$page->check_date()) {
    if ($page->relativeweek) {
        $publishsignals .= ' '.get_string('relativeweekmark', 'format_page', $page->relativeweek);
    } else {
        $a = new StdClass();
        $a->from = userdate($page->datefrom);
        $a->to = userdate($page->dateto);
        $publishsignals .= ' '.get_string('timerangemark', 'format_page', $a);
    }
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

// fix editing columns with size 0
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

$prevbutton = $renderer->previous_button();
$nextbutton = $renderer->next_button();

echo '<div id="page-region-top" class="page-region bootstrap row-fluid">';

if ($hastoppagenav) {
    if ($nextbutton || $prevbutton) {
            if (!empty($publishsignals)) {
                $leftspan = $midspan = $rightspan = 'span4';
            } else {
                $leftspan = $rightspan = 'span6';
                $midspan = '';
            }
    ?>
        <div class="region-content bootstrap row-fluid">
            <div class="page-nav-prev <?php echo $leftspan; ?>">
            <?php echo $renderer->previous_button(); ?>
            </div>
            <?php
            if (!empty($publishsignals)) {
                echo "<div class=\"page-publishing {$midspan}\">$publishsignals</div>";
            }
            ?>
            <div class="page-nav-next <?php echo $rightspan; ?>">
            <?php
                echo $renderer->next_button();
            ?>
            </div>
        </div>
<?php 
    }
} else {
    if (!empty($publishsignals)) {
        echo '<div class="page-publishing span12">'.$publishsignals.'</div>';
    }
}
?>
</div>

<div id="region-page-box" class="row-fluid">
        <?php if ($hassidepre) { ?>
        <div id="region-pre" <?php echo $prewidthstyle ?> class="page-block-region bootstrap block-region span<?php echo $prewidthspan ?> <?php echo @$classes['prewidthspan'] ?> desktop-first-column">
                <div class="region-content">
                    <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                </div>
        </div>
        <?php } ?>

        <?php if ($hassidepre) { ?>
        <div id="region-main" <?php echo $mainwidthstyle ?> class="page-block-region bootstrap block-region span<?php echo $mainwidthspan ?> <?php echo @$classes['mainwidthspan'] ?>">
                <div class="region-content">
                    <?php echo $OUTPUT->blocks_for_region('main') ?>
                </div>
        </div>
        <?php } ?>

        <?php if ($hassidepost) { ?>
        <div id="region-post" <?php echo $postwidthstyle ?> class="page-block-region bootstrap block-region span<?php echo $postwidthspan ?> <?php echo @$classes['postwidthspan'] ?>">
                <div class="region-content">
                    <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                </div>
        </div>
        <?php } ?>
</div>

<div id="page-region-bottom" class="page-region bootstrap row-fluid">

<?php 
    if ($hasbottompagenav) {
        if ($nextbutton || $prevbutton) {
?>
        <div class="region-content bootstrap row-fluid">
            <div class="page-nav-prev span6">
            <?php
                echo $renderer->previous_button();
            ?>
            </div>
            <div class="page-nav-next span6">
            <?php
                echo $renderer->next_button();
            ?>
            </div>
        </div>
<?php
        }
    }

    echo '</div>';

page_save_in_session();

