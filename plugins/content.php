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

defined('MOODLE_INTERNAL') || die();

/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: content.php,v 1.2 2011-04-15 20:14:39 vf Exp $
 * @package format_page
 **/

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
 * @return boolean If an error occurs, just return false and
 *                 optionally set error message to $block->content->text
 *                 Otherwise keep $block->content->text empty on errors
 **/
function content_set_instance($block) {
    global $CFG;

    require_once($CFG->dirroot.'/mod/content/locallib.php');

    $module = content_module_factory($block->cm->id);

    if (!$text = $module->pageitem($block)) {
        // Run the default.
        require_once($CFG->dirroot.'/course/format/page/plugins/page_item_default.php');

        return page_item_default_set_instance($block);
    }

    return true;
}
