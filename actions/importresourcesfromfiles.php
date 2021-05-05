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
 * This custom action allows importing directly all stored files within a local course
 * directory as courses resources for higher productivity.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $COURSE;

$path = optional_param('path', '', PARAM_TEXT);
$collecttitles = optional_param('collecttitles', null, PARAM_TEXT); // Result of title collection form.
echo $OUTPUT->heading(get_string('importresourcesfromfilestitle', 'format_page'));

$renderer = $PAGE->get_renderer('format_page');

if (!empty($path)) {
    if (empty($collecttitles)) {
        echo $renderer->import_file_from_dir_form($path);
    } else {
        /*
         * everything collected. We can perform.
         * 1. create a Moodle resource that uses the file
         * 2. create a Moodle course_module that attaches the resource to the course
         * 3. create a page format page_item that puts the resource in the page
         */
        echo $OUTPUT->box_start('commonbox');
        $fileparms = preg_grep('/^file/', array_keys($_GET));
        foreach ($fileparms as $fileparm) {
            preg_match('/file(\d+)/', $fileparm, $matches);
            $idnum = $matches[1];
            $filepath = required_param('file'.$idnum, PARAM_TEXT);
            $resourcename = required_param('resource'.$idnum, PARAM_TEXT);
            $description = required_param('description'.$idnum, PARAM_CLEANHTML);
            // Use file name as default.
            if (empty($resourcename)) {
                $resourcename = basename($filepath);
            }
            // Create moodle resource.

            $module = $DB->get_record('modules', array('name' => 'resource'));
            // First get the course module the sharedresource is attached to complete a resource record.
            $resource->course = $COURSE->id;
            $resource->type = 'file';
            $resource->name = $resourcename;
            $resource->summary = $description;
            $resource->reference = preg_replace('/^\//', '', $filepath); // Discard first occasional.
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
            // Finish with a pageitem.
            $page = page_get_current_page($COURSE->id);
            $pageitem->pageid = $page->id;
            $pageitem->cmid = $cm->coursemodule;
            $pageitem->blockinstance = 0;
            $pageitem->position = 'c';
            $select = " pageid = ? AND position = 'c' ";
            $params = array($pageitem->pageid);
            $pageitem->sortorder = $DB->get_field_select('format_page_items', 'MAX(sortorder)', $select, $params) + 1;
            $pageitem->visible = 1;

            $DB->insert_record('format_page_items', $pageitem);
            // Give some traces.
            if (debugging()) {
                echo "Constructed ressource : {$resourceid}<br/>";
            }
        }
        echo $OUTPUT->box_end();
        $manageurl = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'action' => 'activities'));
        echo $OUTPUT->continue_button($manageurl);
        echo $OUTPUT->footer();
        die;
    }
} else {
    if (empty($path)) {
        // Get available dirs in course directory and provide a list a recursive fucntion to scan dirs.
        $paths = array();
        $paths['/'] = '/';

        function importresources_get_paths_rec($path, &$paths) {
            global $COURSE, $CFG;

            $basepath = $CFG->dataroot.'/'.$COURSE->id.$path;

            if ($dir = opendir(preg_replace('/\/$/', '', $basepath))) {
                while ($entry = readdir($dir)) {
                    if (preg_match('/^\./', $entry)) {
                        continue;
                    }
                    if (is_dir($basepath.$entry)) {
                        $paths[$path.$entry] = $path.$entry;
                        importresources_get_paths_rec($path.$entry.'/', $paths);
                    }
                }
                closedir($dir);
            }
        }

        importresources_get_paths_rec('/', $paths);

        echo $OUTPUT->box_start('commonbox');
        echo $renderer->import_file_form($paths);
        echo $OUTPUT->box_end();
    }
}
