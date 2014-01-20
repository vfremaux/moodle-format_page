<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: page_item_default.php,v 1.2 2011-04-15 20:14:39 vf Exp $
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
 * @return boolean If an error occures, just return false and 
 *                 optionally set error message to $block->content->text
 *                 Otherwise keep $block->content->text empty on errors
 **/
function page_item_default_set_instance(&$block) {
    global $CFG;

    $modinfo = get_fast_modinfo($block->course);
    
    $mod = $modinfo->get_cm($block->config->cmid);

    // Get module icon
    $name = format_string($block->moduleinstance->name);
    $alt  = get_string('modulename', $block->module->name);
    $alt  = s($alt);

    $block->content->text  = "<img src=\"".$mod->get_icon_url()."\" alt=\"$alt\" class=\"icon\" />";
    $block->content->text .= "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/{$block->module->name}/view.php?id={$block->cm->id}\">$name</a>";

    return true;
}

?>