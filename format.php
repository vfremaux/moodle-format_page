<?php

/**
 * Main hook from moodle into the course format
 *
 * @author Jeff Graham, Mark Nielsen
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @todo Swich to the use of $PAGE->user_allowed_editing()
 * @todo Next/Previous breaks when three columns are not printed - Perhaps they should not be part of the main table
 * @todo Core changes wish list:
 *           - Remove hard-coded left/right block position references
 *           - Provide a better way for formats to say, "Hey, backup these blocks" or open up the block instance backup routine and have the format backup its own blocks.
 *           - With the above two, we could have three columns and multiple independent pages that are compatible with core routines.
 *           - http://tracker.moodle.org/browse/MDL-10265 these would help with performance and control
 */

	defined('MOODLE_INTERNAL') || die();

    require_once($CFG->dirroot.'/course/format/page/page.class.php');
    require_once($CFG->dirroot.'/course/format/page/renderer.php');
    require_once($CFG->dirroot.'/course/format/page/pageitem.class.php');
    require_once($CFG->dirroot.'/course/format/page/lib.php');
    require_once($CFG->dirroot.'/course/format/page/locallib.php');
    require_once($CFG->dirroot.'/course/format/page/xlib.php');
    require_once($CFG->dirroot.'/blocks/moodleblock.class.php');

    $id     = optional_param('id', SITEID, PARAM_INT);    // Course ID
    $pageid = optional_param('page', 0, PARAM_INT);       // format_page record ID
    $action = optional_param('action', '', PARAM_ALPHA);  // What the user is doing

/// Set course display
    if ($pageid > 0) {
    	// changing page depending on context
        $pageid = course_page::set_current_page($course->id, $pageid);
    } else {
        if ($page = course_page::get_current_page($course->id)) {
            $displayid = $page->id;
        } else {
            $displayid = 0;
        }
        $pageid = course_page::set_current_page($course->id, $displayid);
    }

/// Check out the $pageid - set? valid? belongs to this course?
    if (!empty($pageid)) {
        if (empty($page) or $page->id != $pageid) {
            // Didn't get the page above or we got the wrong one...
            if (!$page = course_page::get($pageid)) {
                print_error('errorpageid', 'format_page');
            }
            $page->formatpage->id = $pageid;
        }
        // Ensure this page is in this course
        if ($page->courseid != $course->id) {
            print_error('invalidpageid', 'format_page', '', $pageid);
        }
    } else {
        // We don't have a page ID to work with (probably no pages yet in course)
        if (has_capability('format/page:editpages', $context)) {
            $action = 'editpage';
            $page = new course_page(null);
            if (empty($CFG->customscripts)){
            	print_error('errorflexpageinstall', 'format_page');
            }
        } else {
            // Nothing this person can do about it, error out
            $PAGE->set_title($SITE->name);
            $PAGE->set_heading($SITE->name);
            echo $OUTPUT->header();
            print_error('nopageswithcontent', 'format_page');
        }
    }
    
/// There are a couple processes that need some help via the session... take care of those.
    $action = page_handle_session_hacks($page, $course->id, $action);

    $editing = $PAGE->user_is_editing();
    
    if (!$editing && !($page->is_visible())){
        if ($pagenext = $page->get_next()){
            $page = $pagenext;
            $pageid = course_page::set_current_page($COURSE->id, $page->id);
        } elseif ($pageprevious = $page->get_previous()) {
            $page = $pageprevious;
            $pageid = course_page::set_current_page($COURSE->id, $page->id);
        } else {
        	if (!has_capability('format/page:editpages', $context)){
	        	$PAGE->set_title($SITE->fullname);
	        	$PAGE->set_heading($SITE->fullname);
	        	echo $OUTPUT->header();
	            echo "<link href=\"{$CFG->wwwroot}/theme/".$PAGE->theme->name."/page.css\" rel=\"stylesheet\" type=\"text/css\" />";
	            print_error('nopageswithcontent', 'format_page');
	        }
        }
    }

/// check if page has no override

	if (!$editing && $page->cmid){
		redirect($page->url_get_path($page->id));
	}

	$renderer = new format_page_renderer($page);

/// Handle format actions

    echo $OUTPUT->container_start('', 'actionform');
    $page->prepare_url_action($action, $renderer);
    echo $OUTPUT->container_end();

/// Make sure we can see this page
    if (!$page->is_visible() && !$editing) {
        echo $OUTPUT->notification(get_string('thispageisnotpublished', 'format_page'));
        echo $OUTPUT->footer();
        die;
    }

/// Log something more precise than course

    add_to_log($course->id, 'course', 'viewpage', "view.php?id=$course->id", "$course->id:$pageid");

	/// start of page ouptut
	echo $OUTPUT->box_start('', 'format-page-content');
	echo $OUTPUT->box_start('format-page-actionbar clearfix', 'format-page-actionbar');

/// Finally, we can print the page
    // echo "editing $editing ";
    if ($editing) {
        echo $OUTPUT->box_start('', 'format-page-editing-block');        

        echo $renderer->print_tabs('layout', true);

        echo '<table width="100%"><tr><th class="format-page-tools-caption">';
        print_string('navigation', 'format_page');
        echo '</th><th class="format-page-tools-caption">';
        print_string('additem', 'format_page');
        echo '</th><th class="format-page-tools-caption">';
        print_string('createitem', 'format_page');
        echo '</th></tr><tr><td class="format-page-action-cell">';
        print_string('setcurrentpage', 'format_page');
        echo $renderer->print_jump_menu();
        echo '</td><td class="format-page-action-cell">';
        echo $renderer->print_add_mods_form($COURSE, $page);    
        echo '</td><td class="format-page-action-cell">';

        // get_all_mods($COURSE->id, $mods, $modnames, $modnamesplural, $modnamesused);

        // $modcache = get_fast_modinfo($COURSE->id);
		$modnames = get_module_types_names(false);

        $renderer->print_section_add_menus($COURSE, $pageid, $modnames, true, false, true);
        echo "</td></tr></table>";
        echo $OUTPUT->box_end();
    } else {
    	if (has_capability('format/page:discuss', $context)){
	        $renderer->print_tabs('discuss');
	    }
    }

	$publishsignals = '';
	if (($page->display != FORMAT_PAGE_DISP_PUBLISHED) && ($page->display != FORMAT_PAGE_DISP_PUBLIC)){
		$publishsignals .= get_string('thispageisnotpublished', 'format_page');
	}
	if ($page->get_user_rules() && has_capability('format/page:editpages', $context)){
		$publishsignals .= ' '.get_string('thispagehasuserrestrictions', 'format_page');
	}
	if ($page->get_group_rules() && has_capability('format/page:editpages', $context)){
		$publishsignals .= ' '.get_string('thispagehasgrouprestrictions', 'format_page');
	}
	if (!empty($publishsignals)){
		echo "<div class=\"page-publishing\">$publishsignals</div>";
	}
	/**	
	$hasmain = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('main', $OUTPUT));
	if ($hasmain){
		echo $OUTPUT->blocks_for_region('main');
	}
	*/
	
	//
	$hasheading = ($PAGE->heading);
	$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
	$hasfooter = (empty($PAGE->layout_options['nofooter']));
	$hassidepre = (empty($PAGE->layout_options['noblocks']));
	$hassidepost = (empty($PAGE->layout_options['noblocks']));
	$haslogininfo = (empty($PAGE->layout_options['nologininfo']));
	$hastoppagenav = (empty($PAGE->layout_options['notoppagenav']));
	$hasbottompagenav = (empty($PAGE->layout_options['nobottompagenav']));
	
	$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
	$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));
	
	$custommenu = $OUTPUT->custom_menu();
	$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));
	
	$hasframe = !isset($PAGE->theme->settings->noframe) || !$PAGE->theme->settings->noframe;
	$displaylogo = !isset($PAGE->theme->settings->displaylogo) || $PAGE->theme->settings->displaylogo;

	$prevbutton = $renderer->previous_button();
	$nextbutton = $renderer->next_button();
	//

	echo $OUTPUT->box_end();
	if ($hastoppagenav) { 
		if ($nextbutton || $prevbutton){
		?>
        <div id="page-region-top" class="page-region">
            <div class="region-content">
                <div class="page-nav-prev">
                <?php echo $renderer->previous_button(); ?>
                </div>
                <div class="page-nav-next">
                <?php
                    echo $renderer->next_button();
                ?>
                </div>
            </div>
        </div>
    <?php 
    		}
    	} 
    ?>

        <div id="region-page-box">
        <table id="region-page-table" width="100%">
        	<tr valign="top">
                <?php if ($hassidepre) { ?>
                <td id="page-region-pre" class="page-block-region" width="<?php echo $renderer->get_width('side-pre'); ?>">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </td>
                <?php } ?>

                <td id="page-region-main" class="page-block-region" width="<?php echo $renderer->get_width('main'); ?>">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('main') ?>
                    </div>
                </td>
		
                <?php if ($hassidepost) { ?>
                <td id="page-region-post" class="page-block-region" width="<?php echo $renderer->get_width('side-post'); ?>">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </td>
                <?php } ?>
            </table>
        </div>

    <?php 
    	if ($hasbottompagenav) { 
			if ($nextbutton || $prevbutton){
    ?>
        <div id="page-region-bottom" class="page-region">
            <div class="region-content">
                <div class="page-nav-prev">
                <?php
                    echo $renderer->previous_button();
                ?>
            	</div>
                <div class="page-nav-next">
                <?php
                    echo $renderer->next_button();
                ?>
                </div>
            </div>
        </div>
    <?php
    		} 
    	}

	echo $OUTPUT->box_end();

    page_save_in_session();
?>
