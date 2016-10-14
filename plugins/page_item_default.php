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
 * @author Mark Nielsen
 * @author for Moodle 2 Valery Fremaux (valery.fremaux@gmail.com)
 * @package format_page
 **/
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
function page_item_default_set_instance(&$block) {

    $modinfo = get_fast_modinfo($block->course);

    $mod = $modinfo->get_cm($block->config->cmid);

    // Get module icon.
    $name = format_string($block->moduleinstance->name);
    $alt  = get_string('modulename', $block->module->name);
    $alt  = s($alt);

    $block->content->text  = '<img src="'.$mod->get_icon_url().'" alt="'.$alt.'" class="icon" />';
    $moduleurl = new moodle_url('/mod/'.$block->module->name.'/view.php', array('id' => $block->cm->id));
    $block->content->text .= '<a title="'.$alt.'" href="'.$moduleurl.'">'.$name.'</a>';

    return true;
}
