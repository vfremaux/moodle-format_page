<?php 
    //This script is used to configure and execute the backup proccess in a learning path context.

    global $SESSION;

    require_once ($CFG->dirroot.'/config.php');
    require_once ($CFG->dirroot.'/backup/lib.php');
    require_once ($CFG->dirroot.'/backup/backuplib.php');
    require_once ($CFG->libdir.'/blocklib.php');
    require_once ($CFG->libdir.'/adminlib.php');

    $id         = optional_param('id', null, PARAM_INT);       // course id
    $cancel     = optional_param('cancel');
    $launch     = optional_param('launch');

    if (!empty($id)) {
        require_login($id);
        if (!has_capability('moodle/site:backup', context_course::instance($id))) {
            print_error('erroractionnotpermitted', 'format_page', $CFG->wwwroot.'/login/index.php');
        }
    }

    //Check site
    if (!$site = get_site()) {
        print_error('errornosite');
    }

    //Check necessary functions exists. Thanks to gregb@crowncollege.edu
    backup_required_functions();

    //Check backup_version
    if ($id) {
        $linkto = "backup.php?id=".$id.((!empty($to)) ? '&amp;to='.$to : '');
    } else {
        $linkto = "backup.php";
    }
    upgrade_backup_db($linkto);

    //Get strings
    if (empty($to)) {
        $strcoursebackup = get_string('coursebackup');
    } else {
        $strcoursebackup = get_string('importdata');
    }
    $stradministration = get_string('administration');

    //Get and check course
    if (! $course = $DB->get_record('course', array('id' => $id))) {
        print_error('coursemisconf');
    }

    $PAGE->print_tabs('backup');

    //Print form
    echo $OUTPUT->container_start('emptyleftspace');
    echo $OUTPUT->heading(format_string("$strcoursebackup: $course->fullname ($course->shortname)"));
    $OUTPUT->box_start('center');

    //Call the form, depending the step we are
    if (empty($launch)) {

        // if we're at the start, clear the cache of prefs        
        unset($SESSION->backupprefs[$course->id]);

// TODO use form api 

// START BACKUP FORM //
?>
<form id="form1" method="post" action="/course/view.php?action=backup">
<table cellpadding="5" width="100%">
</table>
<?php
    $backup_unique_code = time();
    $backup_name = backup_get_zipfile_name($course, $backup_unique_code);
?>
<div style="text-align:center;margin-left:auto;margin-right:auto">
<input type="hidden" name="backup_course_file" value="1">

<input type="hidden" name="id"     value="<?php  p($id) ?>" />
<input type="hidden" name="to"     value="<?php p($to) ?>" />
<input type="hidden" name="backup_unique_code" value="<?php p($backup_unique_code); ?>" />
<input type="hidden" name="backup_name" value="<?php p($backup_name); ?>" />
<input type="hidden" name="launch" value="check" />
<input type="submit" value="<?php  print_string('continue') ?>" />
<input type="submit" name="cancel" value="<?php  print_string('cancel') ?>" />
</div>
</form>
<?php
// END BACKUP FORM //

    } else if ($launch == 'check') {


    $backupprefs = new StdClass;
    $count = 0;
    backup_fetch_prefs_from_request($backupprefs, $count, $course);

    if ($count == 0) {
        notice('No backupable modules are installed!');
    }

    $sql = "
        DELETE FROM 
            {backup_ids} 
        WHERE 
            backup_code = '{$backupprefs->backup_unique_code}'
    ";
    if (!$DB->execute($sql)){
        print_error('errordeletebackupids', 'format_page');
    }
?>
<form id="form" method="post" action="/course/view.php?action=backup">
<table cellpadding="5" style="text-align:center;margin-left:auto;margin-right:auto">
<?php
    if (empty($to)) {
        //Now print the Backup Name tr
        echo "<tr>";
        echo "<td align=\"right\"><b>";
        echo get_string("name").":";
        echo "</b></td><td>";
        //Add as text field
        echo "<input type=\"text\" name=\"backup_name\" size=\"40\" value=\"" . $backupprefs->backup_name . "\" />";
        echo "</td></tr>";

        //Line
        echo "<tr><td colspan=\"2\"><hr /></td></tr>";

        //Now print the To Do list
        echo "<tr>";
        echo "<td colspan=\"2\" align=\"center\"><b>";

    }
?>
</table>
<div style="text-align:center;margin-left:auto;margin-right:auto">
<input type="hidden" name="to"     value="<?php p($to) ?>" />
<input type="hidden" name="id"     value="<?php  p($id) ?>" />
<input type="hidden" name="launch" value="execute" />
<input type="submit" value="<?php  print_string('continue') ?>" />
<input type="submit" name="cancel" value="<?php  print_string('cancel') ?>" />
</div>
</form>
<?php

        //include_once("backup_check.html");
    } else if ($launch == 'execute') {
        global $preferences;
        global $SESSION;
        // force preference values
        $SESSION->backupprefs[$course->id] = local_backup_generate_preferences($course);        
        // disable debug output for cleaner report
        $safedebug = @$CFG->debug;
        $CFG->debug = 0;
        include_once($CFG->dirroot.'/backup/backup_execute.html');
        @$CFG->debug = $safedebug;
    }

    print_simple_box_end();
    echo $OUTPUT->container_end();
    echo $OUTPUT->container_end();
    echo $OUTPUT->footer($course);
    die;

?>
