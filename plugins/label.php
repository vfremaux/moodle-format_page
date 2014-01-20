<?php
/**
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux (valery.fremaux@gmail.com)
 * @version $Id: label.php,v 1.2 2011-04-15 20:14:39 vf Exp $
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
function label_set_instance(&$block) {
    global $CFG;

    $block->title = get_string('modulename', 'label');

    $options = new stdClass;
    $options->noclean = true;

    $block->content->text = format_text($block->moduleinstance->intro, FORMAT_HTML, $options);

    return true;
}

?>