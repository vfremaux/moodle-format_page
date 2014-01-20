<?php

    /**
    * This custom action allows importing directly all stored files within a local course
    * directory as courses resources for higher productivity.
    */

    global $CFG, $COURSE;
    $path = optional_param('path', '', PARAM_TEXT);
    $collecttitles = optional_param('collecttitles', null, PARAM_TEXT); // result of title collection form
    echo $OUTPUT->heading(get_string('importresourcesfromfilestitle', 'format_page'));

    if (!empty($path)){
        if (empty($collecttitles)){
            $basepath = $CFG->dataroot.'/'.$COURSE->id.$path;
            $DIR = opendir($basepath);
            $filenamestr = get_string('filename', 'format_page');
            $resourcenamestr = get_string('resourcename', 'format_page');
            $descriptionstr = get_string('description');

            echo "<center>";
            echo "<div style=\"width:90%; min-height:250px\">";
            echo "<form name=\"importfilesasresources\" action=\"{$CFG->wwwroot}/course/view.php\" method=\"get\">";
            echo "<input type=\"hidden\" name=\"id\" value=\"{$COURSE->id}\" />";
            echo "<input type=\"hidden\" name=\"action\" value=\"importresourcesfromfiles\" />";
            echo "<input type=\"hidden\" name=\"path\" value=\"{$path}\" />";
            echo "<table width=\"100%\">";
            echo "<tr><td><b>$filenamestr</b></td>";
            echo "<td><b>$resourcenamestr</b></td></tr>";
            $i = 0;

            while($entry = readdir($DIR)){
                if (is_dir($basepath.'/'.$entry)) continue;
                if (preg_match('/^\./', $entry)) continue;

                echo $OUTPUT->box_start('commonbox');

                echo "<tr><td>$entry<input type=\"hidden\" name=\"file{$i}\" value=\"{$path}/{$entry}\" /></td>";
                echo "<td><input type=\"text\" name=\"resource{$i}\" size=\"50\" /></td></tr>";
                echo "<tr><td><b>$descriptionstr</b></td>";
                echo "<td><textarea name=\"description{$i}\" cols=\"50\" rows=\"4\" /></textarea></td></tr>";

                echo $OUTPUT->box_end();
                $i++;
            }

            echo "</table>";
            echo "<p><input type=\"submit\" name=\"collecttitles\" value=\"".get_string('submit').'" /> ';
            echo "<input type=\"button\" name=\"cancel_btn\" value=\"".get_string('cancel')."\" onclick=\"window.location.href = '{$CFG->wwwroot}/course/view.php?id={$COURSE->id}'; \" /></p>";
            echo "</form>";
            echo "</div>";
            echo "</center>";

        } else {
            // everything collected. We can perform.
            // 1. create a Moodle resource that uses the file
            // 2. create a Moodle course_module that attaches the resource to the course 
            // 3. create a page format page_item that puts the resource in the page
            echo $OUTPUT->box_start('commonbox');
            $fileparms = preg_grep('/^file/', array_keys($_GET));
            foreach($fileparms as $fileparm){
                preg_match('/file(\d+)/', $fileparm, $matches);
                $idnum = $matches[1];
                $filepath = required_param('file'.$idnum, PARAM_TEXT);
                $resourcename = required_param('resource'.$idnum, PARAM_TEXT);
                $description = required_param('description'.$idnum, PARAM_CLEANHTML);
                // use file name as default
                if (empty($resourcename)) $resourcename = basename($filepath);
                // create moodle resource

                $module = $DB->get_record('modules', array('name' => 'resource'));
                /// first get the course module the sharedresource is attached to
                /// complete a resource record
                $resource->course = $COURSE->id;
                $resource->type = 'file';
                $resource->name = $resourcename;
                $resource->summary = $description;
                $resource->reference = preg_replace('/^\//', '', $filepath); // discard first occasional /
                $resource->alltext = '';
                $resource->popup = 0;
                $resource->options = '';
                $resource->timemodified = time();
                $resourceid = $DB->insert_record('resource', $resource);

                $cm->course = $COURSE->id;
                $cm->module = $module->id;
                $cm->instance = $resourceid;
                $cm->section = 1;
                $cm->visible = 1;
                $cm->coursemodule = add_course_module($cm);
                add_mod_to_section($cm);
                // finish with a pageitem
                $page = page_get_current_page($COURSE->id);
                $pageitem->pageid = $page->id;
                $pageitem->cmid = $cm->coursemodule;
                $pageitem->blockinstance = 0;
                $pageitem->position = 'c';
                $pageitem->sortorder = $DB->get_field_select('format_page_items', 'MAX(sortorder)', " pageid = $pageitem->pageid AND position = 'c' ") + 1;
                $pageitem->visible = 1;

                $DB->insert_record('format_page_items', $pageitem);
                /// give some traces
                if (debugging()){
                    echo "Constructed ressource : {$resourceid}<br/>";
                }            
            }
            echo $OUTPUT->box_end();
            echo $OUTPUT->continue_button($CFG->wwwroot."/course/view.php?id={$COURSE->id}&amp;action=activities");
            echo $OUTPUT->footer();
            die;
        }
    } else {
        if (empty($path)){
            // get available dirs in course directory and provide a list
            // a recursive fucntion to scan dirs
            $paths = array();
            $paths['/'] = '/';

            function importresources_get_paths_rec($path, &$paths){
                global $COURSE, $CFG;
                $basepath = $CFG->dataroot.'/'.$COURSE->id.$path;

                if ($DIR = opendir(preg_replace('/\/$/', '', $basepath))){
                    while ($entry = readdir($DIR)){
                        if (preg_match('/^\./', $entry)) continue;
                        if (is_dir($basepath.$entry)){
                            $paths[$path.$entry] = $path.$entry;
                            importresources_get_paths_rec($path.$entry.'/', $paths);
                        }
                    }                
                }
            }
            importresources_get_paths_rec('/', $paths);

            echo $OUTPUT->box_start('commonbox');

            echo "<center>";
            echo "<div style=\"width:90%; height:250px\">";
            echo "<form name=\"importfilesasresources\" action=\"{$CFG->wwwroot}/course/view.php\" method=\"get\">";
            echo "<input type=\"hidden\" name=\"id\" value=\"{$COURSE->id}\" />";
            echo "<input type=\"hidden\" name=\"action\" value=\"importresourcesfromfiles\" />";
            print_string('choosepathtoimport', 'format_page');
            echo html_writer::select($paths, 'path');
            echo "<input type=\"submit\" name=\"go_btn\" value=\"".get_string('submit').'" />';
            echo " <input type=\"button\" name=\"cancel_btn\" value=\"".get_string('cancel')."\" onclick=\"document.location.href = '{$CFG->wwwroot}/course/view.php?id={$COURSE->id}'\" />";
            echo "</form>";
            echo "</div>";
            echo "</center>";

            echo $OUTPUT->box_end();
        }
    }

?>