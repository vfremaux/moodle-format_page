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
 * Page Item Definition
 *
 * @author Valery Fremaux
 * @package format_page
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Add content to a block instance. This
 * method should fail gracefully.  Do not
 * call something like error()
 *
 * @param object $block Passed by refernce: this is the block instance object
 *                      Course Module Record is $block->cm
 *                      Module Record is $block->module
 *                      Module Instance Record is $block->moduleinstance
 *                      Course Record is $block->course
 *
 * @return boolean If an error occures, just return false and 
 *                 optionally set error message to $block->content->text
 *                 Otherwise keep $block->content->text empty on errors
 **/
function choice_set_instance(&$block) {
    global $CFG, $USER, $OUTPUT, $COURSE, $DB, $PAGE;

    require_once($CFG->dirroot.'/mod/choice/lib.php');

    $timenow = time();
    $current = false;
    $cm = $block->cm;

    if (!$choice = choice_get_choice($cm->instance)) {
        print_error('invalidcoursemodule');
    }

    $groupmode = groups_get_activity_groupmode($cm);

    $str = '';

    if ($groupmode) {
        groups_get_activity_group($cm, true);
        $choiceurl = new moodle_url('/mod/choice/view.php', array('id' => $cm->id));
        $str .= groups_print_activity_menu($cm, $choiceurl, true);
    }
    $allresponses = choice_get_response_data($choice, $cm, $groupmode, false);   // Big function, approx 6 SQL calls per user

    /*
     * If user has already made a selection, and they are not allowed to update it or
     * if choice is not open, show their selected answer.
     */
    $params = array('choiceid' => $choice->id, 'userid' => $USER->id);
    if (isloggedin() &&
            ($current = $DB->get_record('choice_answers', $params)) &&
                    (empty($choice->allowupdate) || ($timenow > $choice->timeclose)) ) {
        $str .= $OUTPUT->box(get_string('yourselection', 'choice', userdate($choice->timeopen)).": ".format_string(choice_get_option_text($choice, $current->optionid)), 'generalbox', 'yourselection');
    } else {

        // Print the form.
        $choiceopen = true;
        if ($choice->timeclose != 0) {
            if ($choice->timeopen > $timenow ) {
                $str .= $OUTPUT->box(get_string('notopenyet', 'choice', userdate($choice->timeopen)), 'generalbox notopenyet');
            } else if ($timenow > $choice->timeclose) {
                $str .= $OUTPUT->box(get_string('expired', 'choice', userdate($choice->timeclose)), 'generalbox expired');
                $choiceopen = false;
            }
        } else if ( (!$current or $choice->allowupdate) and $choiceopen) {
            // They haven't made their choice yet or updates allowed and choice is open.
            $options = choice_prepare_options($choice, $USER, $cm, $allresponses);
            $options['hascapability'] = true;
            $renderer = $PAGE->get_renderer('mod_choice');
            $str .= $renderer->display_options($options, $cm->id, $choice->display);
            $choiceformshown = true;
        } else {
            $choiceformshown = false;
        }

        if (!$choiceformshown) {
    
            if (isguestuser()) {
                // Guest account.
                $str .= $OUTPUT->confirm(get_string('noguestchoose', 'choice').'<br /><br />'.get_string('liketologin'),
                             get_login_url(), new moodle_url('/course/view.php', array('id' => $COURSE->id)));
            }
        }
    }

    $block->content->text = $str;

    return true;
}
