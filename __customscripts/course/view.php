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
 *  Display the course home page.
 *
 * @author Valery Fremaux
 * @package format_page
 * @category format
 */

// Customscript type : CUSTOM_SCRIPT_REPLACEMENT

    if (function_exists('debug_catch_users')) {
        debug_catch_users();
    }

    // require_once('../config.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->libdir.'/conditionlib.php');
    require_once($CFG->libdir.'/completionlib.php');
    require_once($CFG->dirroot.'/course/format/page/lib.php');
    require_once($CFG->dirroot.'/course/format/page/blocklib.php');

    $CFG->blockmanagerclass = 'page_enabled_block_manager';

    $id          = optional_param('id', 0, PARAM_INT);
    $name        = optional_param('name', '', PARAM_RAW);
    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $hide        = optional_param('hide', 0, PARAM_INT);
    $show        = optional_param('show', 0, PARAM_INT);
    $idnumber    = optional_param('idnumber', '', PARAM_RAW);
    $sectionid   = optional_param('sectionid', 0, PARAM_INT);
    $section     = optional_param('section', 0, PARAM_INT);
    $move        = optional_param('move', 0, PARAM_INT);
    $marker      = optional_param('marker',-1 , PARAM_INT);
    $switchrole  = optional_param('switchrole',-1, PARAM_INT); // Deprecated, use course/switchrole.php instead.
    $modchooser  = optional_param('modchooser', -1, PARAM_BOOL);
    $return      = optional_param('return', 0, PARAM_LOCALURL);

    $params = array();
    if (!empty($name)) {
        $params = array('shortname' => $name);
    } else if (!empty($idnumber)) {
        $params = array('idnumber' => $idnumber);
    } else if (!empty($id)) {
        $params = array('id' => $id);
    } else {
        print_error('unspecifycourseid', 'error');
    }

    global $JQUERY;
    $JQUERY = true;
    $PAGE->requires->jquery();

    $course = $DB->get_record('course', $params, '*', MUST_EXIST);

    $urlparams = array('id' => $course->id);

    // Sectionid should get priority over section number
    if ($sectionid) {
        $section = $DB->get_field('course_sections', 'section', array('id' => $sectionid, 'course' => $course->id), MUST_EXIST);
    }
    if ($section) {
        $urlparams['section'] = $section;
    }

    $PAGE->set_url('/course/view.php', $urlparams); // Defined here to avoid notices on errors etc

    // Prevent caching of this page to stop confusion when changing page after making AJAX changes.
    $PAGE->set_cacheable(false);

    context_helper::preload_course($course->id);
    $context = context_course::instance($course->id, MUST_EXIST);
    $PAGE->set_context($context);

    // Remove any switched roles before checking login
    if ($switchrole == 0 && confirm_sesskey()) {
        role_switch($switchrole, $context);
    }

    // PATCH+ : Page format.
    // full public pages can be viewed without any login.
    // some restrictions will apply to navigability

    if (!course_page::check_page_public_accessibility($course)) {
        require_login($course);
    } else {
        // we must anyway push this definition or the current course context is not established
        $COURSE = $course;
        $PAGE->set_course($COURSE);
    }
    // PATCH-.

    // Switchrole - sanity check in cost-order...
    $reset_user_allowed_editing = false;
    if ($switchrole > 0 && confirm_sesskey() &&
        has_capability('moodle/role:switchroles', $context)) {
        // is this role assignable in this context?
        // inquiring minds want to know...
        $aroles = get_switchable_roles($context);
        if (is_array($aroles) && isset($aroles[$switchrole])) {
            role_switch($switchrole, $context);
            // Double check that this role is allowed here.
            require_login($course);
        }
        // Reset course page state - this prevents some weird problems ;-)
        $USER->activitycopy = false;
        $USER->activitycopycourse = NULL;
        unset($USER->activitycopyname);
        unset($SESSION->modform);
        $USER->editing = 0;
        $reset_user_allowed_editing = true;
    }

    /* 
     * If course is hosted on an external server, redirect to corresponding
     * url with appropriate authentication attached as parameter
     */
    if (file_exists($CFG->dirroot .'/course/externservercourse.php')) {
        include $CFG->dirroot .'/course/externservercourse.php';
        if (function_exists('extern_server_course')) {
            if ($extern_url = extern_server_course($course)) {
                redirect($extern_url);
            }
        }
    }

    require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

    if ($section and $section > 0) {

        // Get section details and check it exists.
        $modinfo = get_fast_modinfo($course);
        $coursesections = $modinfo->get_section_info($section, MUST_EXIST);

        // Check user is allowed to see it.
        if (!$coursesections->uservisible) {
            // Note: We actually already know they don't have this capability
            // or uservisible would have been true; this is just to get the
            // correct error message shown.
            require_capability('moodle/course:viewhiddensections', $context);
        }
    }

    // Fix course format if it is no longer installed
    $course->format = course_get_format($course)->get_format();

    $PAGE->set_pagelayout('course');
    $PAGE->set_pagetype('course-view-' . $course->format);
    // PATCH+ : add page format support.
    if ($course->format == 'page') {
        $PAGE->set_pagelayout('format_page');
        $page = course_page::get_current_page($COURSE->id);
        if ($page) {
            // Course could be empty.
            $PAGE->navbar->add($page->get_name());

            // check if page has no override.
            if (empty($USER->editing) && $page->cmid) {
                $params = array();
                redirect($page->url_get_path($params, true));
            }
        }
    } else {
        $PAGE->set_pagelayout('course');
    }
    // PATCH-.

    $PAGE->set_other_editing_capability('moodle/course:update');
    $PAGE->set_other_editing_capability('moodle/course:manageactivities');
    $PAGE->set_other_editing_capability('moodle/course:activityvisibility');
    if (course_format_uses_sections($course->format)) {
        $PAGE->set_other_editing_capability('moodle/course:sectionvisibility');
        $PAGE->set_other_editing_capability('moodle/course:movesections');
    }

    // Preload course format renderer before output starts.
    // This is a little hacky but necessary since
    // format.php is not included until after output starts
    if (file_exists($CFG->dirroot.'/course/format/'.$course->format.'/renderer.php')) {
        require_once($CFG->dirroot.'/course/format/'.$course->format.'/renderer.php');
        if (class_exists('format_'.$course->format.'_renderer')) {
            // call get_renderer only if renderer is defined in format plugin
            // otherwise an exception would be thrown
            $PAGE->get_renderer('format_'. $course->format);
        }
    }

    if ($reset_user_allowed_editing) {
        // ugly hack
        unset($PAGE->_user_allowed_editing);
    }

    if (!isset($USER->editing)) {
        $USER->editing = 0;
    }

    if ($PAGE->user_allowed_editing()) {

        // PATCH+ : Add course format support.
        if ($COURSE->format == 'page') {
            // If we have no pages in page format, force editing the first one.
            if (!$page && !($page = course_page::get_default_page($COURSE->id))) {
                redirect(new moodle_url('/course/format/page/actions/editpage.php', array('id' => $COURSE->id, 'page' => 0)));
            }
        }
        // PATCH-.

        if (($edit == 1) and confirm_sesskey()) {
            $USER->editing = 1;
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot .'/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
                redirect($url);
            }
        } else if (($edit == 0) and confirm_sesskey()) {
            $USER->editing = 0;
            if(!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                $USER->activitycopy       = false;
                $USER->activitycopycourse = NULL;
            }
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot .'/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                redirect($PAGE->url);
            }
        }
        if (($modchooser == 1) && confirm_sesskey()) {
            set_user_preference('usemodchooser', $modchooser);
        } else if (($modchooser == 0) && confirm_sesskey()) {
            set_user_preference('usemodchooser', $modchooser);
        }

        if (has_capability('moodle/course:sectionvisibility', $context)) {
            if ($hide && confirm_sesskey()) {
                set_section_visible($course->id, $hide, '0');
                redirect($PAGE->url);
            }

            if ($show && confirm_sesskey()) {
                set_section_visible($course->id, $show, '1');
                redirect($PAGE->url);
            }
        }

        if (!empty($section) && !empty($move) &&
                has_capability('moodle/course:movesections', $context) && confirm_sesskey()) {
            $destsection = $section + $move;
            if (move_section_to($course, $section, $destsection)) {
                if ($course->id == SITEID) {
                    redirect($CFG->wwwroot . '/?redirect=0');
                } else {
                    redirect(course_get_url($course));
                }
            } else {
                echo $OUTPUT->notification('An error occurred while moving a section');
            }
        }
    } else {
        $USER->editing = 0;
    }

    $SESSION->fromdiscussion = $PAGE->url->out(false);


    if ($course->id == SITEID) {
        // This course is not a real course.
        redirect($CFG->wwwroot .'/');
    }

    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $PAGE->requires->string_for_js('completion-title-manual-y', 'completion');
        $PAGE->requires->string_for_js('completion-title-manual-n', 'completion');
        $PAGE->requires->string_for_js('completion-alt-manual-y', 'completion');
        $PAGE->requires->string_for_js('completion-alt-manual-n', 'completion');

        $PAGE->requires->js_init_call('M.core_completion.init');
    }

    // We are currently keeping the button here from 1.x to help new teachers figure out
    // what to do, even though the link also appears in the course admin block.  It also
    // means you can back out of a situation where you removed the admin block. :)
    if ($PAGE->user_allowed_editing()) {
        $buttons = $OUTPUT->edit_button($PAGE->url);
        $PAGE->set_button($buttons);
    }

    // If viewing a section, make the title more specific
    if ($section and $section > 0 and course_format_uses_sections($course->format)) {
        $sectionname = get_string('sectionname', "format_$course->format");
        $sectiontitle = get_section_name($course, $section);
        $PAGE->set_title(get_string('coursesectiontitle', 'moodle', array('course' => $course->fullname, 'sectiontitle' => $sectiontitle, 'sectionname' => $sectionname)));
    } else {
        $PAGE->set_title(get_string('coursetitle', 'moodle', array('course' => $course->fullname)));
    }

    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();

    if ($completion->is_enabled()) {
        // This value tracks whether there has been a dynamic change to the page.
        // It is used so that if a user does this - (a) set some tickmarks, (b)
        // go to another page, (c) clicks Back button - the page will
        // automatically reload. Otherwise it would start with the wrong tick
        // values.
        echo html_writer::start_tag('form', array('action'=>'.', 'method'=>'get'));
        echo html_writer::start_tag('div');
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'id'=>'completion_dynamic_change', 'name'=>'completion_dynamic_change', 'value'=>'0'));
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('form');
    }

    // Course wrapper start.
    echo html_writer::start_tag('div', array('class'=>'course-content'));

    // make sure that section 0 exists (this function will create one if it is missing)
    course_create_sections_if_missing($course, 0);

    // get information about course modules and existing module types
    // format.php in course formats may rely on presence of these variables
    $modinfo = get_fast_modinfo($course);
    $modnames = get_module_types_names();
    $modnamesplural = get_module_types_names(true);
    $modnamesused = $modinfo->get_used_module_names();
    $mods = $modinfo->get_cms();
    $sections = $modinfo->get_section_info_all();

    // CAUTION, hacky fundamental variable definition to follow!
    // Note that because of the way course formats are constructed though
    // inclusion we pass parameters around this way..
    $displaysection = $section;

    // Include the actual course format.
    require($CFG->dirroot .'/course/format/'. $course->format .'/format.php');
    // Content wrapper end.

    echo html_writer::end_tag('div');

    // Trigger course viewed event.
    // We don't trust $context here. Course format inclusion above executes in the global space. We can't assume
    // anything after that point.
    $eventdata = array('context' => context_course::instance($course->id));
    if (!empty($section) && (int)$section == $section) {
        $eventdata['other'] = array('coursesectionid' => $section);
        if ($COURSE->format == 'page') {
            if (!is_null($page)) {
                $eventdata['other']['pageid'] = $page->id;
            }
        }
    }
    $event = \core\event\course_viewed::create($eventdata);
    $event->trigger();

    // Include course AJAX
    include_course_ajax($course, $modnamesused);

    echo $OUTPUT->footer();

    die; // We must Die as customscript.