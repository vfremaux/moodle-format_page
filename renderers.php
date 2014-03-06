<?php
/**
 * Page
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package format_page
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 *
 * Functions that render some part of the page format
 */

require_once($CFG->dirroot.'/course/format/page/locallib.php');

/**
 * Format Page Renderer
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 * @package format_page
 */
class format_page_renderer extends plugin_renderer_base {
	
	var $formatpage;
	
	protected $courserenderer;

	/**
	*
	*
	*/
	function __construct($formatpage){
		global $PAGE;
		
		$this->formatpage = $formatpage;
        $this->courserenderer = $PAGE->get_renderer('core', 'course');
		
		parent::__construct($PAGE, null);
	}
	
	function __call($name, $arguments){
		
		if (method_exists($this->formatpage, $name)){
			return $this->formatpage->$name($arguments);
		} else {
			echo "Method $name not implemented";
		}

	}

    /**
     * The javascript module used by the presentation layer
     *
     * @return array
     */
    public function get_js_module() {
        return array(
            'name'      => 'format_page',
            'fullpath'  => '/course/format/page/javascript.js',
            'requires'  => array(
                'base',
                'node',
                'event-custom',
                'json-parse',
                'querystring',
                'yui2-yahoo',
                'yui2-dom',
                'yui2-event',
                'yui2-element',
                'yui2-button',
                'yui2-container',
                'yui2-menu',
                'yui2-calendar',
            ),
            'strings' => array(
                array('savechanges'),
                array('cancel'),
                array('choosedots'),
                array('close', 'format_flexpage'),
                array('addpages', 'format_flexpage'),
                array('genericasyncfail', 'format_flexpage'),
                array('error', 'format_flexpage'),
                array('movepage', 'format_flexpage'),
                array('addactivities', 'format_flexpage'),
                array('formnamerequired', 'format_flexpage'),
                array('deletepage', 'format_flexpage'),
                array('deletemodwarn', 'format_flexpage'),
                array('continuedotdotdot', 'format_flexpage'),
                array('warning', 'format_flexpage'),
                array('actionbar', 'format_flexpage'),
                array('actionbar_help', 'format_flexpage'),
            )
        );
    }

	/**
	*
	*
	*
	*/
	static function default_width_styles(){
	}

    /**
     * Pads a page's name with spaces and a hyphen based on hierarchy depth or passed amount
     *
     * @param course_format_flexpage_model_page $page
     * @param null|int|boolean $length Shorten page name to this length (Pass true to use default length)
     * @param bool $link To link the page name or not
     * @param null|int $amount Amount of padding
     * @return string
     */
    public function pad_page_name($length = null, $link = false, $amount = null) {
		$name = format_string($this->formatpage->get_name(), true, $this->formatpage->courseid);

        if (!is_null($length)) {
            if ($length === true) {
                $length = 30;
            }
            $name = shorten_text($name, $length);
        }
        if ($link) {
            $name = html_writer::link($page->get_url(), $name);
        }
        if (is_null($amount)) {
            $amount = $page->get_page_depth();
        }
        if ($amount == 0) {
            return $name;
        }
        return str_repeat('&nbsp;&nbsp;', $amount).'-&nbsp;'.$name;
    }

	/**
	 * Padds a string with spaces and a hyphen
	 *
	 * @param string $string The string to be padded
	 * @param int $amount The amount of padding to add (if zero, then no padding)
	 * @return string
	 **/
	function pad_string($string, $amount) {
	    if ($amount == 0) {
	        return $string;
	    } else {
	        return str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $amount).'-&nbsp;'.$string;
	    }
	}

    /**
     * Generates a help icon with specific class wrapped around it
     *
     * @param string $identifier
     * @param string $component
     * @param bool $showlabel
     * @return string
     */
    public function help_icon($identifier, $component = 'format_page', $showlabel = true) {
        $help = html_writer::tag('span', $this->help_icon($identifier, $component), array('class' => 'format_page_helpicon'));

        if ($showlabel) {
            $help = get_string($identifier, $component)."&nbsp;$help";
        }
        return $help;
    }

    /**
     * render the tabs for the format page type
     *
     * @param object $page the current page
     * @param string $currenttab Tab to highlight
     * @return void
     **/
    function print_tabs($currenttab = 'layout', $editing = false) {
        global $COURSE, $CFG, $USER, $DB, $OUTPUT;

        $context = context_course::instance($COURSE->id);
        $tabs = $row = $inactive = $active = array();
        
        $page = $this->formatpage;
        
        if (has_capability('format/page:viewpagesettings', $context) && $editing) {
	        $row[] = new tabobject('view', $page->url_build(), get_string('editpage', 'format_page'));
	    }
		if (has_capability('format/page:addpages', $context) && $editing){
	        $row[] = new tabobject('addpage', $page->url_build('action', 'addpage'), get_string('addpage', 'format_page'));
	    }
		if (has_capability('format/page:managepages', $context) && $editing){
        	$row[] = new tabobject('manage', $page->url_build('action', 'manage'), get_string('manage', 'format_page'));
    	}
		if (has_capability('format/page:discuss', $context)){
            $discuss = $DB->get_record('format_page_discussion', array('pageid' => $page->id));
            $userdiscuss = $DB->get_record('format_page_discussion_user', array('userid' => $USER->id, 'pageid' => $page->id));
            $discusstext = get_string('discuss', 'format_page');
            if ($discuss && $userdiscuss && $discuss->lastmodified > $userdiscuss->lastread){
                $discusstext .= '(*)';
            }
            if (!empty($discuss->discussion)){
                $discusstext = '<b>'.$discusstext.'</b>';
            }
            $row[] = new tabobject('discussion', $page->url_build('action', 'discussion'), $discusstext, get_string('discuss', 'format_page'));
		}
        if (has_capability('moodle/course:manageactivities', $context) && $editing) {
	        $row[] = new tabobject('activities', $page->url_build('action', 'activities'), get_string('managemods', 'format_page'));
	    }
		if (!empty($CFG->pageindividualisationfeature)){
	        $row[] = new tabobject('individualize', $page->url_build('action', 'individualize'), get_string('individualize', 'format_page'));
		}
		if ($DB->record_exists('block', array('name' => 'publishflow'))){
	        if (has_capability('format/page:quickbackup', $context)) {
	            $row[] = new tabobject('backup', $page->url_build('action', 'backup'), get_string('quickbackup', 'format_page'));
	        }
	    }
        $tabs[] = $row;

        if (in_array($currenttab, array('layout', 'settings', 'view'))) {
            $active[] = 'view';

            $row = array();
            $row[] = new tabobject('layout', $page->url_build(), get_string('layout', 'format_page'));
            $row[] = new tabobject('settings', $page->url_build('action', 'editpage'), get_string('settings', 'format_page'));
            $tabs[] = $row;
        }

        if ($currenttab == 'layout'){
        } elseif ($currenttab == 'activities'){
			if ($DB->record_exists('modules', array('name' => 'sharedresource'))){
	            $convertallstr = get_string('convertall', 'sharedresource');
	            $tabs[1][] = new tabobject('convertall', "/mod/sharedresource/admin_convertall.php?course={$COURSE->id}", $convertallstr);
	            $convertbacktitle = get_string('convertback', 'sharedresource');
	            $convertbackstr = $convertbacktitle . $OUTPUT->help_icon('convert', 'sharedresource', false);
	            $tabs[1][] = new tabobject('convertback', "/mod/sharedresource/admin_convertback.php?course={$COURSE->id}", $convertbackstr, $convertbacktitle);
			}
            $cleanuptitle = get_string('cleanup', 'format_page');
            $cleanupstr = $cleanuptitle . $OUTPUT->help_icon('cleanup', 'format_page', false);
            $tabs[1][] = new tabobject('cleanup', $page->url_build('action', 'cleanup'), $cleanupstr, $cleanuptitle);
        }

        return print_tabs($tabs, $currenttab, $inactive, $active, true);
    }

	/**
	 * Prints a menu for jumping from page to page
	 *
	 * @return void
	 **/
	function print_jump_menu() {
		global $OUTPUT, $COURSE;
			
		$str = '';
	    if ($pages = course_page::get_all_pages($COURSE->id, 'flat')) {
	        $current = $this->formatpage->get_formatpage();

			$selected = '';
	        $urls = array();
	        foreach ($pages as $page) {
	        	$pageurl = $this->formatpage->url_build('page', $page->id);
	            $urls[$pageurl] = $page->name_menu($this, 28);
	            if ($this->formatpage->id == $page->id){
	            	$selected = $pageurl;
	            }
	        }
	        $str = $OUTPUT->box_start('centerpara pagejump');	        
	        $str .= $OUTPUT->url_select($urls, $selected, array('' => get_string('choosepagetoedit', 'format_page')));
	        $str .= $OUTPUT->box_end();
	    }
	    
	    return $str;
	}

	/**
	 * This function displays the controls to add modules and blocks to a page
	 *
	 * @param object $page A fully populated page object
	 * @param object $course A fully populated course object
	 * @uses $USER;
	 * @uses $CFG;
	 */
	function print_add_mods_form($course, $coursepage) {
	    global $USER, $CFG, $PAGE, $DB, $OUTPUT;
	
	    $str = $OUTPUT->box_start('centerpara addpageitems');
	
	    // Add drop down to add blocks
	    if ($blocks = $DB->get_records('block', array('visible' => '1'), 'name')) {

			// Use standard block_add_ui now	
			/*
	        $options = array();
	        $commonurl = $CFG->wwwroot.'/course/format/page/view.php?id='.$course->id.'&page='.$this->formatpage->id.'&blockaction=add&sesskey='.sesskey().'&blockid=';
            $urls = array();
	        foreach($blocks as $b) {
	            if (in_array($b->name, array('page_module'))) {
	                continue;
	            }
	            if (!blocks_name_allowed_in_format($b->name, 'course-view-page')) {
	                continue;
	            }
	            $blockobject = block_instance($b->name);
	            // if ($blockobject !== false && $blockobject->user_can_addto($PAGE)) {
	            if ($blockobject !== false) {
	                $urls[$commonurl.$b->id] = $blockobject->get_title();
	            }
	        }
	        asort($urls);
	        $str .= '<span class="addblock">';
	        $str .= $OUTPUT->url_select($urls, '', array('' => get_string('addblock', 'format_page')));
	        $str .= '</span>&nbsp;';
	        */
	        $bc = format_page_block_add_block_ui($PAGE, $OUTPUT, $coursepage);
	        $str .= $bc->content;
	    }
	
	    // Add drop down to add existing module instances
	    if ($modules = course_page::get_modules('name+IDNumber')) {
	        // From our modules object we can build an existing module menu using separators

	        $commonurl = '/course/format/page/action.php?id='.$course->id.'&page='.$this->formatpage->id.'&action=addmod&sesskey='.sesskey().'&instance=';

	        $urls = array();
	        $i = 0;
	        foreach ($modules as $modplural => $instances) {	
	            asort($instances);
	            
	            foreach($instances as $cmid => $name) {
	                $urls[$i][$modplural][$commonurl.$cmid] = shorten_text($name, 60);
	            }	
	            $i++;
	        }
	        
	        $str .= '<span class="addexistingmodule">';
	        $str .= $OUTPUT->url_select($urls, '', array('' => get_string('addexistingmodule', 'format_page')));
	        $str .= '</span>';
	    }
	    $str .= $OUTPUT->box_end();
	    
	    return $str;
	}

	function course_section_add_cm_control($course, $section, $sectionreturnignored = null, $optionsignored = null){
		return $this->courserenderer->course_section_add_cm_control($course, $section);
	}

	/**
	* A derivated function from course/lib.php that prints the menus to 
	* add activities and resources. It will add an additional signal to 
	* notify whether the call for adding resource or activities is 
	* performed from within a context that can receive immediately
	* the new item in (such as page format in-page).
	* @see course/lib.php print_section_add_menus();
	*/
	function print_section_add_menus($course, $section, $modnames, $vertical=false, $return=false, $insertonreturn = false) {
	    global $CFG, $OUTPUT;
	
	    // check to see if user can add menus
	    if (!has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_COURSE, $course->id))) {
	        return false;
	    }

	    $insertsignal = ($insertonreturn) ? "&insertinpage=1" : '';
	
	    $urlbase = "/course/format/page/mod.php?id={$course->id}&section={$section}&sesskey=".sesskey()."{$insertsignal}&add=";
	
	    $resources = array();
	    $activities = array();
	
	    foreach($modnames as $modname => $modnamestr) {
	        if (!course_allowed_module($course, $modname)) {
	            continue;
	        }
	
	        $libfile = "$CFG->dirroot/mod/$modname/lib.php";
	        if (!file_exists($libfile)) {
	            continue;
	        }
	        include_once($libfile);
	        $gettypesfunc =  $modname.'_get_types';
	        if (function_exists($gettypesfunc)) {
	            // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
	            if ($types = $gettypesfunc()) {
	                $menu = array();
	                $atype = null;
	                $groupname = null;
	                foreach($types as $type) {
	                    if ($type->typestr === '--') {
	                        continue;
	                    }
	                    if (strpos($type->typestr, '--') === 0) {
	                        $groupname = str_replace('--', '', $type->typestr);
	                        continue;
	                    }
	                    $type->type = str_replace('&amp;', '&', $type->type);
	                    if ($type->modclass == MOD_CLASS_RESOURCE) {
	                        $atype = MOD_CLASS_RESOURCE;
	                    }
	                    $menu[$urlbase.$type->type] = $type->typestr;
	                }
	                if (!is_null($groupname)) {
	                    if ($atype == MOD_CLASS_RESOURCE) {
	                        $resources[] = array($groupname=>$menu);
	                    } else {
	                        $activities[] = array($groupname=>$menu);
	                    }
	                } else {
	                    if ($atype == MOD_CLASS_RESOURCE) {
	                        $resources = array_merge($resources, $menu);
	                    } else {
	                        $activities = array_merge($activities, $menu);
	                    }
	                }
	            }
	        } else {
	            $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
	            if ($archetype == MOD_ARCHETYPE_RESOURCE) {
	                $resources[$urlbase.$modname] = $modnamestr;
	            } else {
	                // all other archetypes are considered activity
	                $activities[$urlbase.$modname] = $modnamestr;
	            }
	        }
	    }
	
	    $straddactivity = get_string('addactivity');
	    $straddresource = get_string('addresource');
	
	    $output  = '<div class="section_add_menus">';
	
	    if (!$vertical) {
	        $output .= '<div class="horizontal">';
	    }
	
	    if (!empty($resources)) {
	        $select = new url_select($resources, '', array('' => $straddresource), "ressection$section");
	        $select->set_help_icon('resources');
	        $output .= $OUTPUT->render($select);
	    }
	
	    if (!empty($activities)) {
	        $select = new url_select($activities, '', array('' => $straddactivity), "section$section");
	        $select->set_help_icon('activities');
	        $output .= $OUTPUT->render($select);
	    }
	
	    if (!$vertical) {
	        $output .= '</div>';
	    }
	
	    $output .= '</div>';
	
	    if ($return) {
	        return $output;
	    } else {
	        echo $output;
	    }
	}

	/**
	* prints the previous button as a link or an image
	*
	*/
	function previous_button(){
		global $OUTPUT, $CFG;

		$button = '';		
		$missingconditonstr = get_string('missingcondition', 'format_page');
		if ($prevpage = $this->formatpage->get_previous()){
	        if ($this->formatpage->showbuttons & FORMAT_PAGE_BUTTON_PREV) {
	        	if (!$prevpage->check_activity_lock()){
		        	if (empty($CFG->format_page_nav_graphics)){
			            $button = '<span class="disabled-page">'.get_string('previous', 'format_page', $prevpage->get_name()).'</span>';
			        } else {
			            $button = '<img class="disabled-page" src="'.$OUTPUT->pix_url('prev_button_disabled', 'theme').'"  title="'.$missingconditonstr.'" />';
			        }
	        	} else {
		        	if (empty($CFG->format_page_nav_graphics)){
			            $button = '<a href="'.$this->formatpage->url_build('page', $prevpage->id, 'aspage', true).'">'.get_string('previous', 'format_page', $prevpage->get_name()).'</a>';
			        } else {
			            $button = '<a href="'.$this->formatpage->url_build('page', $prevpage->id, 'aspage', true).'" title="'.get_string('previous', 'format_page', $prevpage->get_name()).'" ><img src="'.$OUTPUT->pix_url('prev_button', 'theme').'" /></a>';
			        }
			    }
	        }
        }
    	return $button;
    }

	/**
	* prints the "next" button as a link or an image
	*
	*/
	function next_button(){
		global $OUTPUT, $CFG;
		
		$button = '';
		$missingconditonstr = get_string('missingcondition', 'format_page');
		if ($nextpage = $this->get_next()){
	        if ($this->formatpage->showbuttons & FORMAT_PAGE_BUTTON_NEXT) {
	        	if (!$nextpage->check_activity_lock()){
		        	if (empty($CFG->format_page_nav_graphics)){
			            $button = '<span class="disabled-page">'.get_string('next', 'format_page', $nextpage->get_name()).'</span>';
			        } else {
			            $button = '<img src="'.$OUTPUT->pix_url('next_button_disabled', 'theme').'" class="disabled-page" title="'.$missingconditonstr.'" />';
			        }
	        	} else {
		        	if (empty($CFG->format_page_nav_graphics)){
			            $button = '<a href="'.$this->formatpage->url_build('page', $nextpage->id, 'aspage', true).'">'.get_string('next', 'format_page', $nextpage->get_name()).'</a>';
			        } else {
			            $button = '<a href="'.$this->formatpage->url_build('page', $nextpage->id, 'aspage', true).'" title="'.get_string('next', 'format_page', $nextpage->get_name()).'" ><img src="'.$OUTPUT->pix_url('next_button', 'theme').'" /></a>';
			        }
			    }
	        }
	    }
        return $button;
    }

    public function print_cm($course, cm_info $mod, $displayoptions = array()) {

        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2a) The 'showavailability' option is not set (if that is set,
        //     we need to display the activity so we can show
        //     availability info)
        // or
        // 2b) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible &&
            (empty($mod->showavailability) || empty($mod->availableinfo))) {
            return $output;
        }
        
        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }
        $output .= html_writer::start_tag('div', array('class' => $indentclasses));

        // Start the div for the activity title, excluding the edit icons.
        $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));

        // Display the link to the module (or do nothing if module has no url)
        $output .= $this->print_cm_name($mod, $displayoptions);

        // Module can put text after the link (e.g. forum unread)
        $output .= $mod->get_after_link();

        // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
        $output .= html_writer::end_tag('div'); // .activityinstance

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->print_cm_text($mod, $displayoptions);
        $url = $mod->get_url();
        if (empty($url)) {
            $output .= $contentpart;
        }

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // show availability info (if module is not available)
        $output .= $this->print_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses
        return $output;
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function print_cm_name(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        
        return $this->courserenderer->course_section_cm_name($mod, $displayoptions);
    }

    /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function print_cm_text(cm_info &$mod, $displayoptions = array()) {

        return $this->courserenderer->course_section_cm_text($mod, $displayoptions);
    }
    
    public function print_cm_completion(&$course, &$completioninfo, &$mod, $displayoptions){

        return $this->courserenderer->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
    }

    public function print_cm_availability(&$mod, $displayoptions){

        return $this->courserenderer->course_section_cm_availability($mod, $displayoptions);
    }

    /**
     * Render page availability information
     *
     * @param course_format_page_model_page[] $pages
     * @return string
     */
    public function page_available_info(array $pages) {
        return '';
    }

    /**
     * Add pages UI
     *
     * @param moodle_url $url
     * @param array $pageoptions An array of available pages
     * @param array $moveoptions Page move options
     * @param array $copyoptions An array of copy page options
     * @return string
     */
    public function add_pages(moodle_url $url, array $pageoptions, array $moveoptions, array $copyoptions) {
    }

    /**
     * Move page UI
     *
     * @param course_format_flexpage_model_page $page
     * @param moodle_url $url
     * @param array $pageoptions An array of available pages
     * @param array $moveoptions Page move options
     * @return string
     */
    public function move_page(course_format_flexpage_model_page $page, moodle_url $url, array $pageoptions, array $moveoptions) {
    }

    /**
     * Add activity UI
     *
     * @param moodle_url $url
     * @param array $activities Available activities to add, grouped by a group name
     * @return string
     */
    public function add_activity(moodle_url $url, array $activities) {
    }

    /**
     * Add existing activity UI
     *
     * @param moodle_url $url
     * @param array $activities  An array of existing activities, grouped by a group name
     * @return string
     */
    public function add_existing_activity(moodle_url $url, array $activities) {
    }

    /**
     * Add block UI
     *
     * @param moodle_url $url
     * @param array $blocks List of available blocks to add
     * @return string
     */
    public function add_block(moodle_url $url, array $blocks) {
    }

    /**
     * Manage pages UI
     *
     * @param moodle_url $url
     * @param course_format_flexpage_model_page[] $pages
     * @param course_format_flexpage_lib_menu_action[] $actions Actions to take on pages
     * @return string
     */
    public function manage_pages(moodle_url $url, array $pages, array $actions) {
        global $CFG, $PAGE;

    }

    /**
     * Condition UI
     *
     * @param course_format_flexpage_model_page $page
     * @param string $conditionclass
     * @return string
     */
    public function page_conditions($page, $conditionclass) {
        $conditions = $page->get_conditions();
        if (!is_null($conditions)) {
            $conditions = $conditions->get_conditions($conditionclass);
        }

        // Render a blank one if none exist
        if (empty($conditions)) {
            $conditions = array(null);
        }
        $condbox  = new course_format_flexpage_lib_box(array('class' => 'format_flexpage_conditions'));
        $condcell = new course_format_flexpage_lib_box_cell();
        $condcell->set_attributes(array('id' => $conditionclass.'s'));
        $condadd = html_writer::tag('button', '+', array('type' => 'button', 'value' => '+', 'id' => $conditionclass.'_add_button'));

        foreach ($conditions as $condition) {
            $condcell->append_contents(
                $this->$conditionclass($condition)
            );
        }
        $condbox->add_new_row()->add_cell($condcell)
                               ->add_new_cell($condadd, array('class' => 'format_page_add_button'));

        return $this->render($condbox);
    }

    /**
     * Grade condition specific UI
     *
     * @param condition_grade|null $condition
     * @return string
     */
    public function condition_grade(condition_grade $condition = null) {
        global $CFG, $COURSE;

        require_once($CFG->libdir.'/gradelib.php');

        // Static so we only build it once...
        static $gradeoptions = null;

        if (is_null($condition)) {
            $gradeitemid = 0;
            $min = '';
            $max = '';
        } else {
            $gradeitemid = $condition->get_gradeitemid();
            $min = rtrim(rtrim($condition->get_min(),'0'),'.');
            $max = rtrim(rtrim($condition->get_max(),'0'),'.');
        }
        if (is_null($gradeoptions)) {
            $gradeoptions = array();
            if ($items = grade_item::fetch_all(array('courseid'=> $COURSE->id))) {
                foreach($items as $id => $item) {
                    $gradeoptions[$id] = $item->get_name();
                }
            }
            asort($gradeoptions);
            $gradeoptions = array(0 => get_string('none', 'condition')) + $gradeoptions;
        }
        $elements = html_writer::select($gradeoptions, 'gradeitemids[]', $gradeitemid, false).
                    ' '.get_string('grade_atleast','condition').' '.
                    html_writer::empty_tag('input', array('name' => 'mins[]', 'size' => 3, 'type' => 'text', 'value' => $min)).
                    '% '.get_string('grade_upto','condition').' '.
                    html_writer::empty_tag('input', array('name' => 'maxes[]', 'size' => 3, 'type' => 'text', 'value' => $max)).
                    '%';

        return html_writer::tag('div', $elements, array('class' => 'format_flexpage_condition_grade'));
    }

    /**
     * Completion condition specific UI
     *
     * @param condition_completion|null $condition
     * @return string
     */
    public function condition_completion(condition_completion $condition = null) {
        global $COURSE;

        static $completionoptions = null;

        if (is_null($condition)) {
            $cmid = 0;
            $requiredcompletion = '';
        } else {
            $cmid = $condition->get_cmid();
            $requiredcompletion = $condition->get_requiredcompletion();
        }
        if (is_null($completionoptions)) {
            $completionoptions = array();
            $modinfo = get_fast_modinfo($COURSE);
            foreach($modinfo->get_cms() as $id => $cm) {
                if ($cm->completion) {
                    $completionoptions[$id] = $cm->name;
                }
            }
            asort($completionoptions);
            $completionoptions = array(0 => get_string('none', 'condition')) + $completionoptions;
        }
        $completionvalues=array(
            COMPLETION_COMPLETE      => get_string('completion_complete','condition'),
            COMPLETION_INCOMPLETE    => get_string('completion_incomplete','condition'),
            COMPLETION_COMPLETE_PASS => get_string('completion_pass','condition'),
            COMPLETION_COMPLETE_FAIL => get_string('completion_fail','condition'),
        );
        $elements = html_writer::select($completionoptions, 'cmids[]', $cmid, false).'&nbsp;'.
                    html_writer::select($completionvalues, 'requiredcompletions[]', $requiredcompletion, false);

        return html_writer::tag('div', $elements, array('class' => 'format_flexpage_condition_completion'));
    }

    /**
     * // TODO : implement the optional use of graphical buttons
     * @param string $type Basically next or previous
     * @param null|course_format_flexpage_model_page $page
     * @param null|string $label
     * @return string
     */
    public function navigation_link($type, $page = null, $label = null) {
        if ($page) {
            if (is_null($label)) {
                $label = get_string("{$type}page", 'format_page', format_string($page->get_name()));
            }
            return html_writer::link($page->get_url(), $label, array('id' => "format_page_{$type}_page"));
        }
        return '';
    }

    
    public function get_width($region){    	
    	switch($region){
    		case 'main' :
	    		return $this->formatpage->prefcenterwidth;
    		case 'side-pre' :
	    		return $this->formatpage->prefleftwidth;
    		case 'side-post' :
	    		return $this->formatpage->prefrightwidth;
	    	default:
	            throw new coding_exception('Unknwon region '.$region.' in format_page page');
    	}     	
    }
}

/**
 * A core renderer override to change blocks final rendering with 
 * course module completion when a special page module.
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 * @package format_page
 */
class format_page_core_renderer extends core_renderer {
	
    /**
     * Produces a header for a block
     * this is overriden to add the completion information
     *
     * @param block_contents $bc
     * @return string
     */
    protected function block_header(block_contents $bc) {
    	global $PAGE;
    	
    	$pagerenderer = $PAGE->get_renderer('format_page');

        $title = '';
        if ($bc->title) {
            $attributes = array();
            if ($bc->blockinstanceid) {
                $attributes['id'] = 'instance-'.$bc->blockinstanceid.'-header';
            }
            $title = html_writer::tag('h2', $bc->title, $attributes);
        }

        $controlshtml = $this->block_controls($bc->controls);

		$completion = '';
        if (isset($bc->completion)){
        	$completion = $pagerenderer->print_cm_completion($COURSE, $bc->completioncompletioninfo, $bc->completion->mod, array());
        }

        $output = '';
        if ($title || $controlshtml) {
            $output .= html_writer::tag('div', html_writer::tag('div', html_writer::tag('div', '', array('class'=>'block_action')). $title . $controlshtml.' '.$completion, array('class' => 'title')), array('class' => 'header'));
        }
        
        return $output;
    }
	
}