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
 * @author Mark Nielsen, Jeff Graham
 * @version $Id: __resource.php,v 1.2 2011-04-15 20:14:38 vf Exp $
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
function resource_set_instance(&$block) {
    global $CFG;

    $resource = $block->moduleinstance;

    require_once($CFG->dirroot.'/mod/resource/lib.php');
    require_once($CFG->dirroot.'/mod/resource/type/'.$resource->type.'/resource.class.php');

    $resclass    = 'resource_'.$resource->type;
    $resourceobj = new $resclass($block->cm->id);

    switch ($resource->type) {
        case 'directory':
            ob_start();
            $resourceobj->display(true);
            $output = ob_get_contents();
            ob_end_clean();

            $block->content->text = str_replace('view.php', $CFG->wwwroot.'/mod/resource/view.php', $output);
            break;
        case 'html':
            if (empty($resource->popup)) {
                $options          = new StdClass;
                $options->noclean = true;

                $block->content->text = format_text($resource->alltext, FORMAT_HTML, $options, $block->course->id);
            }
            break;
        case 'text':
            if (empty($resource->popup)) {
                $options          = new StdClass;
                $options->noclean = true;

                $block->content->text = format_text($resource->alltext, $resource->reference, $options, $block->course->id);

                // Do not see $CFG->resourcetrimlength in resource module - keeping code if we need to bring this feature back
                // $shortentext   = shorten_text($alltext, $CFG->resourcetrimlength);
                // $shortentext   = mb_ereg_replace('\.\.\.', '', $shortentext); // remove ellipsis from shorten_text, this should be optional for shorten_text
                // $remainingtext = mb_substr($alltext, mb_strlen($shortentext, 'UTF-8'), mb_strlen($alltext, 'UTF-8'), 'UTF-8');
                // $linkstr       = '<a onclick="elementToggleHide(findParentNode(this, \'SPAN\', \'resourcewrapper\'), true); return false;" href="#" title="%1$s">%1$s</a>';
                //
                // $block->content->text  = $shortentext;
                // $block->content->text .= '<span id="pageres'.$block->cm->id.'" class="resourcewrapper">';
                // $block->content->text .= '<span class="remainingtext">'.$remainingtext.'</span>';
                // $block->content->text .= '<span class="showresource">'.sprintf($linkstr, get_string('showresource', 'format_page')).'</span>';
                // $block->content->text .= '<span class="hideresource">'.sprintf($linkstr, get_string('hideresource', 'format_page')).'</span>';
                // $block->content->text .= "</span><script type=\"text/javascript\">\n<!--\nelementCookieHide('pageres{$block->cm->id}');\n//-->\n</script>";
            }
            break;
        default:
            // Check to see if the resource has the display_embedded method
            if (method_exists($resourceobj, 'display_embedded')) {
                $block->content->text = $resourceobj->display_embedded();
            }
            break;
    }

    if (empty($block->content->text)) {
        // Not set yet, so last resort, run default page item display
        require_once($CFG->dirroot.'/course/format/page/plugins/page_item_default.php');
        page_item_default_set_instance($block);
    }

    return true;
}
