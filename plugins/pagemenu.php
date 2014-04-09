<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: pagemenu.php,v 1.2 2011-04-15 20:14:39 vf Exp $
 * @package pagemenu
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
function pagemenu_set_instance(&$block) {
    global $CFG;

    if ($block->moduleinstance->displayname) {
        $block->hideheader = false;
        $block->title = $block->moduleinstance->name;
    } else {
        $block->hideheader = true;
    }

    if (has_capability('mod/pagemenu:view', context_module::instance($block->cm->id))) {
        require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');
        $block->content->text = pagemenu_build_menu($block->moduleinstance->id, false , true);
    }

    return true;
}

