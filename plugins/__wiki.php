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
 * Wiki Definition
 *
 * @author Jason Hardin
 * @version $Id: __wiki.php,v 1.3 2011-07-05 22:03:23 vf Exp $
 * @package wiki
 **/

/**
 * Add content to a block instance. This
 * method should fail gracefully.  Do not
 * call something like error()
 *
 * @param object $block Passed by reference: this is the block instance object
 *                      Course Module Record is $block->cm
 *                      Module Record is $block->module
 *                      Module Instance Record is $block->moduleinstance
 *                      Course Record is $block->course
 *
 * @return boolean If an error occures, just return false and 
 *                 optionally set error message to $block->content->text
 *                 Otherwise keep $block->content->text empty on errors
 **/
function wiki_set_instance(&$block) {
    global $CFG, $WS, $COURSE, $USER, $regex, $nowikitext, $tocheaders;

    // Commented this out since wiki:view doesn't exist...yet
    //if (has_capability('mod/wiki:view', context_module::instance($block->cm->id))) {

        // This variable determine if we need all dfwiki libraries.
        $full_wiki = true;

        require_once($CFG->dirroot.'/mod/wiki/lib.php');
        require_once($CFG->dirroot.'/mod/wiki/wikistorage.class.php');
        require_once($CFG->dirroot.'/mod/wiki/weblib.php');

        // WS contains all global variables
        $WS = new storage();

        // Function to load all necessary data needed in WS
        $WS->recover_variables();
        $WS->set_info($block->cm->id);

        // Setup the module
        wiki_setup_content($WS);

        ob_start();
        wiki_print_content($WS);
        wiki_print_teacher_selection($WS->cm, $WS->dfwiki);  // Select the teacher
        $block->content->text = ob_get_contents();
        ob_end_clean();
    //}
    return true;
}
