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
 * @author Valery Fremaux (valery.fremaux@gmail.com) for moodle 2
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require('../../../../config.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/page/page.class.php');

$id = required_param('id', PARAM_INT);
$pageid = optional_param('page', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$context = context_course::instance($course->id);

require_login($course);
require_capability('format/page:editpages', $context);

$PAGE->set_url('/course/view.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc
$PAGE->set_pagelayout('format_page_action');
$PAGE->set_context($context);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->requires->css('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.css');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxcommon.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/dhtmlxtree.js');
$PAGE->requires->js('/course/format/page/js/dhtmlxTree/codebase/ext/dhtmlxtree_start.js');

// Default location of form.
$formfile = $CFG->dirroot.'/course/format/page/actions/editpage_form.php';
require_once($formfile);

$returnaction = optional_param('returnaction', '', PARAM_ALPHA);

// Defaultpage is used as default context for building URLs.
if ($pageid) {
    if ($returnaction) {
        $currenttab = $returnaction;
    } else {
        $currenttab = 'settings';
    }
    $defaultpage = course_page::load($pageid);
    $page = $defaultpage;
    
    // Security : check page is not protected
    if ($page->protected && !has_capability('format/page:editprotectedpage', $context)) {
        print_error('erroreditnotallowed', 'format_page');
    }
} else {
    require_capability('format/page:addpages', $context);
    $currenttab = 'addpage';
    $defaultpage = course_page::get_default_page($course->id);
}

// Find possible parents for the edited page.
if ($defaultpage && $parents = $defaultpage->get_possible_parents($course->id, $pageid == 0)) {
    $possibleparents = array(0 => get_string('none'));
    foreach ($parents as $parent) {
        $possibleparents[$parent->id] = $parent->get_name();
    }
} else {
    $possibleparents = array();
}

// Get global templates.
$templates = course_page::get_global_templates();

$mform = new format_page_editpage_form(new moodle_url('/course/format/page/actions/editpage.php'), array('pageid' => $pageid, 'parents' => $possibleparents, 'globaltemplates' => $templates));

// Form controller.
if ($mform->is_cancelled()) {
    if ($returnaction) {
        // Return back to a specific action.
        redirect($defaultpage->url_build('action', $returnaction));
    } else {
        if (empty($defaultpage)) {
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        }
        redirect($defaultpage->url_build());
    }

} elseif ($data = $mform->get_data()) {

    if (!empty($data->addtemplate)) {

        // New page may not be in turn a global template.
        $overrides = array('globaltemplate' => 0, 'parent' => $data->templateinparent);

        $templatepage = course_page::get($data->usetemplate);
        $newpageid = $templatepage->copy_page($data->usetemplate, true, $overrides);

        // Update the changed params.
        $pagerec = $DB->get_record('format_page', array('id' => $newpageid));
        $pagerec->nameone = $data->extnameone;
        $pagerec->nametwo = $data->extnametwo;
        if ($data->parent) {
            $pagerec->parent = $data->parent;
        } else {
            $pagerec->parent = 0;
        }
        $DB->update_record('format_page', $pagerec);

        rebuild_course_cache($COURSE->id);

        if (empty($defaultpage)) {
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        }
        redirect($defaultpage->url_build('page', $newpageid));
    }

    // Save/update routine.
    $pagerec = new StdClass;
    $pagerec->nameone             = $data->nameone;
    $pagerec->nametwo             = $data->nametwo;
    $pagerec->courseid            = $COURSE->id;
    $pagerec->display             = 0 + @$data->display;
    $pagerec->displaymenu         = 0 + @$data->displaymenu;
    if (format_page_is_bootstrapped()) {
        $pagerec->bsprefleftwidth       = (@$data->prefleftwidth == '*') ? '*' : ''.@$data->prefleftwidth ;
        $pagerec->bsprefcenterwidth     = (@$data->prefcenterwidth == '*') ? '*' : ''.@$data->prefcenterwidth ;
        $pagerec->bsprefrightwidth      = (@$data->prefrightwidth == '*') ? '*' : ''.@$data->prefrightwidth ;
    } else {
        $pagerec->prefleftwidth       = (@$data->prefleftwidth == '*') ? '*' : ''.@$data->prefleftwidth ;
        $pagerec->prefcenterwidth     = (@$data->prefcenterwidth == '*') ? '*' : ''.@$data->prefcenterwidth ;
        $pagerec->prefrightwidth      = (@$data->prefrightwidth == '*') ? '*' : ''.@$data->prefrightwidth ;
    }
    $pagerec->template            = $data->template;
    $pagerec->globaltemplate      = $data->globaltemplate;
    $pagerec->showbuttons         = $data->showbuttons;
    $pagerec->parent              = $data->parent;
    $pagerec->cmid                = 0 + @$data->cmid; // there are no mdules in course
    $pagerec->lockingcmid         = 0 + @$data->lockingcmid; // there are no mdules in course
    $pagerec->lockingscore        = 0 + @$data->lockingscore; // there are no mdules in course
    $pagerec->datefrom            = 0 + @$data->datefrom; // there are no mdules in course
    $pagerec->dateto              = 0 + @$data->dateto; // there are no mdules in course
    $pagerec->relativeweek        = 0 + @$data->relativeweek; // there are no mdules in course

    // There can only be one!
    if ($pagerec->template) {
        // Only one template page allowed.
        $DB->set_field('format_page', 'template', 0, array('courseid' => $pagerec->courseid));
    }

    if ($pageid) {

        $old = course_page::get($data->page);
        $hasmoved = ($old->parent != $pagerec->parent);
        $pagerec->section = $old->section;

        // Updating existing record.
        $pagerec->id = $data->page;

        if ($hasmoved) {
            // Moving - re-assign sortorder.
            $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);

            // Remove from old parent location.
            course_page::remove_from_ordering($pagerec->id);
        }

        $page->set_formatpage($pagerec);

        $page->save(); // Save once.
        if ($hasmoved) {
            $page->delete_section();
            $page->insert_in_sections();
            $page->save();
        } else {
            $page->update_section();
        }
    } else {
        // Creating new.
        $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);
        $pagerec->section = 0;
        $page = new course_page($pagerec);
        $page->insert_in_sections();
        $page->save();
    }

    // Apply some settings to all pages.
    if (!empty($data->displayapplytoall)) {
        if (!empty($data->display)) {
            $DB->set_field('format_page', 'display', $data->display, array('courseid' => $COURSE->id));
        }
    }

    if (!empty($data->displaymenuapplytoall)) {
        if (!empty($data->displaymenu)) {
            $DB->set_field('format_page', 'displaymenu', $data->displaymenu, array('courseid' => $COURSE->id));
        }    
    }

    if (!empty($data->prefleftwidthapplytoall)) {
        if (!empty($data->prefleftwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefleftwidth', $data->prefleftwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefleftwidth', $data->prefleftwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->prefcenterwidthapplytoall)) {
        if (!empty($data->prefcenterwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefcenterwidth', $data->prefcenterwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefcenterwidth', $data->prefcenterwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->prefrightwidthapplytoall)){
        if (!empty($data->prefrightwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefrightwidth', $data->prefrightwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefrightwidth', $data->prefrightwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->showbuttonsapplytoall)) {
        $DB->set_field('format_page', 'showbuttons', $data->showbuttons, array('courseid' => $COURSE->id));
    }

    if ($returnaction) {
        // Return back to a specific action.
        redirect($page->url_build('page', $page->id, 'action', $returnaction));
    } else {
        // Default, view the page.
        redirect($page->url_build('page', $page->id));
    }
}

// No controller action.

/*
 * Set up data to be sent to the form
 * Might come from a page or page template record
 */
$toform = new stdClass;

if ($pageid) {
    $toform = $page->get_formatpage();
    $toform->page = $page->id;
} elseif ($template = $DB->get_record('format_page', array('template' => 1, 'courseid' => $course->id), 'bsprefleftwidth, bsprefcenterwidth, bsprefrightwidth, prefleftwidth, prefcenterwidth, prefrightwidth, showbuttons, display, courseid, cmid')) {
    $template->cmid = 0;
    $toform = $template;
    $page = new course_page($template);
    $toform->page = 0;
    $toform->nameone = ''; // Do not copy template page names.
    $toform->nametwo = ''; // Do not copy template page names.
} else {
    $page = new course_page(null);
    $toform->page = 0;
}

// Done here on purpose.
$toform->id = $course->id;
$toform->returnaction = $returnaction;

// Cleanup disappeared course modules.
if (@$toform->cmid && !$DB->record_exists('course_modules', array('id' => $toform->cmid))) {
    $toform->cmid = 0;
}
if (@$toform->lockingcmid && !$DB->record_exists('course_modules', array('id' => $toform->lockingcmid))) {
    $toform->lockingcmid = 0;
    $toform->lockingscore = 0;
}

if (format_page_is_bootstrapped()) {
    // Transfer width values from bootstrap to standard for the form
    $toform->prefleftwidth = (isset($toform->bsprefleftwidth)) ? $toform->bsprefleftwidth : 3;
    $toform->prefcenterwidth = (isset($toform->bsprefcenterwidth)) ? $toform->bsprefcenterwidth : 6;
    $toform->prefrightwidth = (isset($toform->bsprefrightwidth)) ? $toform->bsprefrightwidth : 3;
}

$mform->set_data($toform);

// Start producing page.

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'page-actionform');
$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

echo $renderer->print_tabs($currenttab, true);
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

