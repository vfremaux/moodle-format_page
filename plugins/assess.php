<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: assess.php,v 1.2 2011-04-15 20:14:38 vf Exp $
 * @package format_page
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
function assess_set_instance(&$block) {
    global $CFG;

    require_once($CFG->dirroot.'/mod/assess/lib.php');

    // Not generalized - works for now
    if (in_array($block->moduleinstance->type, array('mywork', 'progress')) and has_capability('mod/assess:viewreport', context_module::instance($block->cm->id))) {
        $type = assess_type_instance($block->moduleinstance->type);
        $type->set_navposition('right');
        $content = $type->make_report($block->cm->id, $block->moduleinstance->id, $block->baseurl);
    } else {
        // Make a regular assessment
        $content  = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/assess/css.php?a='.$block->moduleinstance->id.'" /> ';
        $content .= assess_print_assessment($block->cm->id, $block->baseurl, true);
    }
    // Get any notifications generated while creating the assessment
    ob_start();
    assess_print_messages();
    $content = ob_get_contents() . $content; // Append messages to top
    ob_end_clean();

    $block->content->text = $content;

    return true;
}

?>