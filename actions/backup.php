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
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This script is used to configure and execute the backup proccess in a learning path context.
 *
 * check if not obsolete
 */
require('../../../../config.php');

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');

$id = required_param('id', PARAM_INT); // Course ID.
$pageid = required_param('page', PARAM_INT); // Page ID.
$cancel = optional_param('cancel', '', PARAM_TEXT);
$confirm = optional_param('confirm', '', PARAM_TEXT);

$coursecontext = context_course::instance($id);

$url = new moodle_url('/course/format/page/actions/backup.php', array('id' => $id, 'page' => $pageid));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('format_page_action');

// Security.

require_login($id);

if (!has_capability('moodle/backup:backupcourse', $coursecontext)) {
    print_error('erroractionnotpermitted', 'format_page', $CFG->wwwroot.'/login/index.php');
}

// Check site.

if (!$site = get_site()) {
    print_error('errornosite');
}

// Get and check course.
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('coursemisconf');
}

if ($pageid) {
    $page = course_page::load($pageid);
}

$renderer = $PAGE->get_renderer('format_page');
$renderer->set_formatpage($page);

$strcoursebackup = get_string('quickbackup', 'format_page');

if ($confirm) {
    $bc = new backup_controller(backup::TYPE_1COURSE, $id, backup::FORMAT_MOODLE,
                                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

    try {

        // Build default settings for quick backup
        // Quick backup is intended for publishflow purpose.
    
        // Get default filename info from controller.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
    
        $settings = array(
            'users' => 0,
            'role_assignments' => 0,
            'user_files' => 0,
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'comments' => 1,
            'completion_information' => 0,
            'logs' => 0,
            'histories' => 0,
            'filename' => backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised)
        );

        foreach ($settings as $setting => $configsetting) {
            if ($bc->get_plan()->setting_exists($setting)) {
                $bc->get_plan()->get_setting($setting)->set_value($configsetting);
            }
        }

        $bc->set_status(backup::STATUS_AWAITING);

        $bc->execute_plan();
        $results = $bc->get_results();
        // convert user file in course file
        $file = $results['backup_destination'];

        $fs = get_file_storage();

        $filerec = new StdClass();
        $filerec->contextid = $coursecontext->id;
        $filerec->component = 'backup';
        $filerec->filearea = 'course';
        $filerec->itemid = 0;
        $filerec->filepath = $file->get_filepath();
        $filerec->filename = $file->get_filename();
        $fs->create_file_from_storedfile($filerec, $file);

        // Remove user scope original file.
        $file->delete();

        $outcome = true;

    }  catch (backup_exception $e) {
        $bc->log('backup_auto_failed_on_course', backup::LOG_WARNING, $course->shortname);
        $outcome = false;
    }
}

echo $OUTPUT->header();

echo '<div id="format-page-editing-block">';
echo $renderer->print_tabs('backup', true);
echo '</div>';
echo '<div id="region-main" class="page-block-region bootstrap block-region">';

// Print form.
echo $OUTPUT->heading(format_string("$strcoursebackup: $course->fullname ($course->shortname)"));
if ($confirm) {
    if ($outcome) {
        echo $OUTPUT->box_start('notification');
        print_string('backupsuccess', 'format_page');
        $backupsurl = new moodle_url('/backup/restorefile.php', array('contextid' => $coursecontext->id));
        echo $OUTPUT->single_button($backupsurl, get_string('gotorestore', 'format_page'));
    } else {
        echo $OUTPUT->box_start('notification');
        print_string('backupfailure', 'format_page');
    }
    echo $OUTPUT->box_end();
}
echo $OUTPUT->box_start();
$url->params(array('confirm' => 1));
echo $OUTPUT->single_button($url, get_string('confirmbackup', 'format_page'));
echo $OUTPUT->box_end();
echo '</div>';

echo $OUTPUT->footer();
