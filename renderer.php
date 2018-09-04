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
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Functions that render some part of the page format
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/locallib.php');
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Format Page Renderer
 *
 * @author Mark Nielsen
 * @author for Moodle 2 Valery Fremaux
 * @package format_page
 */
class format_page_renderer extends format_section_renderer_base {

    public $formatpage;

    protected $courserenderer;

    protected $thumfiles;

    /**
     * constructor
     *
     */
    public function __construct($formatpage = null) {
        global $PAGE;

        $this->formatpage = $formatpage;
        $this->courserenderer = $PAGE->get_renderer('core', 'course');

        parent::__construct($PAGE, null);
    }

    /**
     * Usefull when renderer is built from the the $PAGE->core get_renderer() function
     */
    public function set_formatpage($formatpage) {
        $this->formatpage = $formatpage;
    }

    public function __call($name, $arguments) {

        if (method_exists($this->formatpage, $name)) {
            return $this->formatpage->$name($arguments);
        } else {
            echo "Method $name not implemented";
        }
    }

    /**
     * The javascript module used by the presentation layer
     * @TOD0 : deprecated since YUI3
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
     * Returns default widths for layout elements
     */
    public static function default_width_styles() {
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
            $name = html_writer::link($this->formatpage->get_url(), $name);
        }
        if (is_null($amount)) {
            $amount = $this->formatpage->get_page_depth();
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
     */
    public function pad_string($string, $amount) {
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
     * @param string $currenttab Tab to highlight
     * @return void
     */
    function print_tabs($currenttab = 'layout', $editing = false) {
        global $COURSE, $CFG, $USER, $DB, $OUTPUT;

        $context = context_course::instance($COURSE->id);
        $tabs = $row = $inactive = $active = array();

        $page = $this->formatpage;

        if (has_capability('format/page:viewpagesettings', $context) && $editing) {
            $row[] = new tabobject('view', $page->url_build(), get_string('editpage', 'format_page'));
        }

        if (has_capability('format/page:addpages', $context) && $editing) {
            $row[] = new tabobject('addpage', $page->url_build('action', 'addpage'), get_string('addpage', 'format_page'));
        }
        if (has_capability('format/page:managepages', $context) && $editing) {
            $row[] = new tabobject('manage', $page->url_build('action', 'manage'), get_string('manage', 'format_page'));
        }
        if (has_capability('format/page:discuss', $context)) {
            $discuss = $DB->get_record('format_page_discussion', array('pageid' => $page->id));
            $userdiscuss = $DB->get_record('format_page_discussion_user', array('userid' => $USER->id, 'pageid' => $page->id));
            $discusstext = get_string('discuss', 'format_page');

            if ($discuss && $userdiscuss && $discuss->lastmodified > $userdiscuss->lastread) {
                $discusstext .= '(*)';
            }

            if (!empty($discuss->discussion)) {
                $discusstext = '<b>'.$discusstext.'</b>';
            }

            $row[] = new tabobject('discussion', $page->url_build('action', 'discussion'), $discusstext, get_string('discuss', 'format_page'));
        }

        if (has_capability('moodle/course:manageactivities', $context) && $editing) {
            $row[] = new tabobject('activities', $page->url_build('action', 'activities'), get_string('managemods', 'format_page'));
        }

        $blockconfig = get_config('block_page_module');
        if (!empty($blockconfig->pageindividualisationfeature)) {
            $row[] = new tabobject('individualize', $page->url_build('action', 'individualize'), get_string('individualize', 'format_page'));
        }

        if ($DB->record_exists('block', array('name' => 'publishflow'))) {
            if (has_capability('format/page:quickbackup', $context)) {
                $row[] = new tabobject('backup', $page->url_build('action', 'backup', 'page', $page->id), get_string('quickbackup', 'format_page'));
            }
        }
        $tabs[] = $row;

        if (in_array($currenttab, array('layout', 'settings', 'view'))) {
            $active[] = 'view';

            $row = array();
            $row[] = new tabobject('layout', $page->url_build(), get_string('layout', 'format_page'));

            if (!$page->protected || has_capability('format/page:editprotectedpages', $context)) {
                $row[] = new tabobject('settings', $page->url_build('action', 'editpage'), get_string('settings', 'format_page'));

                $sectionid = $DB->get_field('course_sections', 'id', array('course' => $page->courseid, 'section' => $page->section));
                if (!empty($CFG->enableavailability)) {
                    $editsectionurl = new moodle_url('/course/editsection.php', array('id' => $sectionid, 'sr' => $sectionid));
                    $row[] = new tabobject('availability', $editsectionurl, get_string('availability', 'format_page'));
                }
            }
            $tabs[] = $row;
        }

        if ($currenttab == 'activities') {
            if ($DB->record_exists('modules', array('name' => 'sharedresource'))) {
                $convertallstr = get_string('convertall', 'sharedresource');
                $tabs[1][] = new tabobject('convertall', "/mod/sharedresource/admin_convertall.php?course={$COURSE->id}", $convertallstr);
                $convertbacktitle = get_string('convertback', 'sharedresource');
                $convertbackstr = $convertbacktitle . $this->output->help_icon('convert', 'sharedresource', false);
                $tabs[1][] = new tabobject('convertback', "/mod/sharedresource/admin_convertback.php?course={$COURSE->id}", $convertbackstr, $convertbacktitle);
            }
            $cleanuptitle = get_string('cleanup', 'format_page');
            $cleanupstr = $cleanuptitle . $this->output->help_icon('cleanup', 'format_page', false);
            $tabs[1][] = new tabobject('cleanup', $page->url_build('action', 'cleanup'), $cleanupstr, $cleanuptitle);
        }

        return print_tabs($tabs, $currenttab, $inactive, $active, true);
    }

    function print_editing_block($page) {
        global $COURSE;

        $context = context_course::instance($COURSE->id);

        $str = '';
        $str .= $this->output->box_start('', 'format-page-editing-block');

        $str .= $this->print_tabs('layout', true);

        $str .= '<div class="container-fluid">';
        $str .= '<div class="row-fluid">';
        $str .= '<div class="span4 col-md-4">';
        /*
        $str .= '<div class="colheads">';
        $str .= get_string('navigation', 'format_page');
        $str .= '</div>';
        $str .= '<br>';
        */
        $str .= get_string('setcurrentpage', 'format_page');
        $str .= $this->print_jump_menu();
        $str .= '</div>';
        if (!$page->protected || has_capability('format/page:editprotectedpages', $context)) {
            $str .= '<div class="span4 col-md-4">';
            /*
            $str .= '<div class="colheads">';
            $str .= get_string('additem', 'format_page');
            $str .= '</div>';
            $str .= '<br>';
            */
            $str .=  $this->print_add_mods_form($COURSE, $page);
            $str .=  '</div>';

            $str .= '<div class="span4 col-md-4">';
            /*
            $str .= '<div class="colheads">';
            $str .= get_string('createitem', 'format_page');
            $str .=  '</div>';
            $str .= '<br>';
            */
            /*
            // Hide edition button ? notsure it is consistant
            $str .= '<STYLE>.breadcrumb-button{display:none}</STYLE>';
            */
            $modnames = get_module_types_names(false);

            $str .= $this->print_section_add_menus($COURSE, $page->id, $modnames, true, true);
            $str .= '</div>';
        }
        $str .= '</div><div class="row-fluid"></div>';

        $str .= $this->output->box_end();

        return $str;
    }

    /**
     * Prints a menu for jumping from page to page
     *
     * @return void
     */
    function print_jump_menu() {
        global $COURSE;

        $str = '';
        if ($pages = course_page::get_all_pages($COURSE->id, 'flat')) {

            $selected = '';
            $urls = array();
            foreach ($pages as $page) {
                $pageurl = ''.$this->formatpage->url_build('page', $page->id); // Need convert to string.
                $urls[$pageurl] = $page->name_menu($this, 28);
                if ($this->formatpage->id == $page->id) {
                    $selected = $pageurl;
                }
            }
            $str = $this->output->box_start('centerpara pagejump-spanned');
            $str .= $this->output->url_select($urls, $selected, array('' => get_string('choosepagetoedit', 'format_page')));
            $str .= $this->output->box_end();
        }

        return $str;
    }

    /**
     * This function displays the controls to add modules and blocks to a page
     *
     * @param object $course A fully populated course object
     * @uses $USER;
     * @uses $CFG;
     */
    function print_add_mods_form($course, $coursepage) {
        global $DB, $PAGE;

        $str = $this->output->box_start('centerpara addpageitems');

        // Add drop down to add blocks.
        if ($blocks = $DB->get_records('block', array('visible' => '1'), 'name')) {
            $bc = format_page_block_add_block_ui($PAGE, $this->output, $coursepage);
            $str .= $bc->content;
        }

        // Add drop down to add existing module instances.
        if ($modules = course_page::get_modules('name+IDNumber', $all = true)) {
            // From our modules object we can build an existing module menu using separators.

            $commonurl = '/course/format/page/action.php?id='.$course->id.'&page='.$this->formatpage->id.'&action=addmod&sesskey='.sesskey().'&instance=';

            $urls = array();
            $i = 0;
            foreach ($modules as $modplural => $instances) {
                asort($instances);

                foreach ($instances as $cmid => $name) {
                    $urls[$i][$modplural][$commonurl.$cmid] = shorten_text($name, 60);
                }
                $i++;
            }

            $str .= '<span class="addexistingmodule">';
            $select = new url_select($urls, '', array('' => get_string('addexistingmodule', 'format_page')));
            $select->set_help_icon('existingmods', 'format_page');
            $str .= $this->output->render($select);
            $str .= '</span>';
        }
        $str .= $this->output->box_end();

        return $str;
    }

    function course_section_add_cm_control($course, $section, $sectionreturnignored = null, $optionsignored = null) {
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
    function print_section_add_menus($course, $section, $modnames, $vertical = false, $insertonreturn = false) {
        global $CFG;

        // Check to see if user can add menus.
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
            return '';
        }

        $insertsignal = ($insertonreturn) ? "&insertinpage=1" : '';

        $urlbase = "/course/format/page/mod.php?id={$course->id}&section={$section}&sesskey=".sesskey()."{$insertsignal}&add=";

        $resources = array();
        $activities = array();

        // User Equipement additions if installed.
        if (is_dir($CFG->dirroot.'/local/userequipment')) {
            include_once($CFG->dirroot.'/local/userequipment/lib.php');
            $ueconfig = get_config('local_userequipment');
            $uemanager = get_ue_manager();
        }

        foreach ($modnames as $modname => $modnamestr) {

            if (!course_allowed_module($course, $modname)) {
                continue;
            }

            // User Equipement additions if installed.
            if (!empty($ueconfig->enabled)) {
                if (!$uemanager->check_user_equipment('mod', $modname)) {
                    continue;
                }
            }

            $libfile = "$CFG->dirroot/mod/$modname/lib.php";
            if (!file_exists($libfile)) {
                continue;
            }


            include_once($libfile);
            $gettypesfunc =  $modname.'_get_shortcuts';

            $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            if (function_exists($gettypesfunc)) {
                // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
                $defaultitem = new stdClass();

                $defaulturlbase = new moodle_url('/course/mod.php', array('id' => $course->id, 'sesskey' => sesskey()));
                $defaultitem->link = new moodle_url($defaulturlbase, array('add' => $modname));

                if ($types = $gettypesfunc($defaultitem)) {
                    $menu = array();
                    $groupname = null;
                    if (is_array($types)) {
                        foreach ($types as $type) {
                            $type->typestr = isset($type->title) ? $type->title : get_string('pluginname', $modname);
                            if ($type->typestr === '--') {
                                continue;
                            }
                            if (strpos($type->typestr, '--') === 0) {
                                $groupname = str_replace('--', '', $type->typestr);
                                continue;
                            }
                            $type->type = isset($type->type) ? $type->type : $type->link->get_param('add');
                            $type->type = str_replace('&amp;', '&', $type->type);
                            $menu[$urlbase.$type->type] = $type->typestr;
                        }
                    }
                    if (!is_null($groupname)) {
                        if ($archetype == MOD_CLASS_RESOURCE) {
                            $resources[] = array($groupname=>$menu);
                        } else {
                            $activities[] = array($groupname=>$menu);
                        }
                    } else {
                        if ($archetype == MOD_CLASS_RESOURCE) {
                            $resources = array_merge($resources, $menu);
                        } else {
                            $activities = array_merge($activities, $menu);
                        }
                    }
                }
            } else {
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $resources[$urlbase.$modname] = $modnamestr;
                } else {
                    // All other archetypes are considered activity.
                    $activities[$urlbase.$modname] = $modnamestr;
                }
            }
        }

        $straddactivity = get_string('addactivity');
        $straddresource = get_string('addresource');

        $str = '<div class="section_add_menus">';
        if (!$vertical) {
            $str .= '<div class="horizontal">';
        }

        if (!empty($resources)) {
            $select = new url_select($resources, '', array('' => $straddresource), "ressection$section");
            $select->set_help_icon('resources');
            $str .= $this->output->render($select);
        }

        if (!empty($activities)) {
            $select = new url_select($activities, '', array('' => $straddactivity), "section$section");
            $select->set_help_icon('activities');
            $str .= $this->output->render($select);
        }

        if (!$vertical) {
            $str .= '</div>';
        }

        $str .= '</div>';
        return $str;
    }

    /**
     * prints the previous button as a link or an image
     *
     */
    public function previous_button() {
        global $CFG;

        $config = get_config('format_page');

        $button = '';
        $missingconditionstr = get_string('missingcondition', 'format_page');
        if ($prevpage = $this->formatpage->get_previous()) {
            if ($this->formatpage->showbuttons & FORMAT_PAGE_BUTTON_PREV) {
                if (!$prevpage->check_activity_lock()) {
                    if (empty($config->navgraphics)) {
                        $button = '<span class="disabled-page">'.get_string('previous', 'format_page', $prevpage->get_name()).'</span>';
                    } else {
                        $imgurl = $this->get_image_url('prev_button_disabled');
                        $button = '<img class="disabled-page" src="'.$imgurl.'"  title="'.$missingconditionstr.'" />';
                    }
                } else {
                    $prevurl = $prevpage->url_build('page', $prevpage->id, 'aspage', true);
                    if (empty($config->navgraphics)) {
                        $button = '<a href="'.$prevurl.'">'.get_string('previous', 'format_page', $prevpage->get_name()).'</a>';
                    } else {
                        $pix = '<img src="'.$this->get_image_url('prev_button').'" />';
                        $title = get_string('previous', 'format_page', $prevpage->get_name());
                        $button = '<a href="'.$prevurl.'" title="'.$title.'" >'.$pix.'</a>';
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
    public function next_button() {
        global $CFG;

        $config = get_config('format_page');

        $button = '';
        $missingconditonstr = get_string('missingcondition', 'format_page');
        if ($nextpage = $this->get_next()) {
            if ($this->formatpage->showbuttons & FORMAT_PAGE_BUTTON_NEXT) {
                if (!$nextpage->check_activity_lock()) {
                    if (empty($config->navgraphics)) {
                        $button = '<span class="disabled-page">'.get_string('next', 'format_page', $nextpage->get_name()).'</span>';
                    } else {
                        $imgurl = $this->get_image_url('next_button_disabled');
                        $button = '<img src="'.$imgurl.'" class="disabled-page" title="'.$missingconditonstr.'" />';
                    }
                } else {
                    $params = array('page' => $nextpage->id, 'id' => $nextpage->courseid);
                    $nexturl = new moodle_url('/course/view.php', $params);
                    if (empty($config->navgraphics)) {
                        $button = '<a href="'.$nexturl.'">'.get_string('next', 'format_page', $nextpage->get_name()).'</a>';
                    } else {
                        $pix = '<img src="'.$this->get_image_url('next_button').'" />';
                        $title = get_string('next', 'format_page', $nextpage->get_name());
                        $button = '<a href="'.$nexturl.'" title="'.$title.'" >'.$pix.'</a>';
                    }
                }
            }
        }
        return $button;
    }

    public function print_cm($course, cm_info $mod, $displayoptions = array()) {

        $output = '';
        /*
         * We return empty string (because course module will not be displayed at all)
         * if:
         * 1) The activity is not visible to users
         * and
         * 2a) The 'showavailability' option is not set (if that is set,
         *     we need to display the activity so we can show
         *     availability info)
         * or
         * 2b) The 'availableinfo' is empty, i.e. the activity was
         *     hidden in a way that leaves no info, such as using the
         *     eye icon.
         */
        if (!$mod->uservisible &&
            (empty($mod->availableinfo))) {
            return $output;
        }

        // Start the div for the activity title, excluding the edit icons.
        $thumb = null;
        if (method_exists($this->courserenderer, 'course_section_cm_thumb')) {
            $thumb = $this->courserenderer->course_section_cm_thumb($mod);
        }

        if ($thumb) {
            $output .= html_writer::start_tag('div', array('class' => 'cm-name'));
            $output .= $thumb;
            $output .= html_writer::start_tag('div', array('class' => 'cm-label'));
            $cmname = $this->courserenderer->course_section_cm_name_for_thumb($mod, $displayoptions);
        } else {
            // Display the link to the module (or do nothing if module has no url).
            $cmname = $this->courserenderer->course_section_cm_name($mod, $displayoptions);
        }

        if (!empty($cmname)) {
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;

            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        /*
         * If there is content but NO link (eg label), then display the
         * content here (BEFORE any icons). In this case cons must be
         * displayed after the content so that it makes more sense visually
         * and for accessibility reasons, e.g. if you have a one-line label
         * it should work similarly (at least in terms of ordering) to an
         * activity.
         */
        $contentpart = $this->print_cm_text($mod, $displayoptions);
        if (method_exists($this->courserenderer, 'get_thumbfiles')) {
            if (!empty($this->courserenderer->get_thumbfiles()[$mod->id])) {
                // Remove the thumb that has already been displayed.
                $pattern = '/<img.*?'.$this->courserenderer->get_thumbfiles()[$mod->id]->get_filename().'".*?>/';
                $contentpart = preg_replace($pattern, '', $contentpart);
            }
        }
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        /*
         * If there is content AND a link, then display the content here
         * (AFTER any icons). Otherwise it was displayed before
         */
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // Show availability info (if module is not available).
        $output .= $this->print_cm_availability($mod, $displayoptions);

        if ($thumb) {
            $output .= html_writer::end_tag('div'); // Close cm-label.
            $output .= html_writer::end_tag('div'); // Close cm-name.
        }

        // $output .= html_writer::end_tag('div'); // $indentclasses
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
        $text = $this->courserenderer->course_section_cm_text($mod, $displayoptions);
        return $text;
    }

    public function print_cm_completion(&$course, &$completioninfo, &$mod, $displayoptions) {
        if (!preg_match('/label$/', $mod->modname)) {
            return $this->courserenderer->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
        }
    }

    public function print_cm_availability(&$mod, $displayoptions) {
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

        // Render a blank one if none exist.
        if (empty($conditions)) {
            $conditions = array(null);
        }
        $condbox = new course_format_flexpage_lib_box(array('class' => 'format_flexpage_conditions'));
        $condcell = new course_format_flexpage_lib_box_cell();
        $condcell->set_attributes(array('id' => $conditionclass.'s'));
        $condadd = html_writer::tag('button', '+', array('type' => 'button', 'value' => '+', 'id' => $conditionclass.'_add_button'));

        foreach ($conditions as $condition) {
            $condcell->append_contents($this->$conditionclass($condition));
        }
        $condbox->add_new_row()->add_cell($condcell)->add_new_cell($condadd, array('class' => 'format_page_add_button'));

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
            $min = rtrim(rtrim($condition->get_min(), '0'), '.');
            $max = rtrim(rtrim($condition->get_max(), '0'), '.');
        }
        if (is_null($gradeoptions)) {
            $gradeoptions = array();
            if ($items = grade_item::fetch_all(array('courseid' => $COURSE->id))) {
                foreach ($items as $id => $item) {
                    $gradeoptions[$id] = $item->get_name();
                }
            }
            asort($gradeoptions);
            $gradeoptions = array(0 => get_string('none', 'condition')) + $gradeoptions;
        }
        $elements = html_writer::select($gradeoptions, 'gradeitemids[]', $gradeitemid, false).
                ' '.get_string('grade_atleast', 'condition').' '.
                html_writer::empty_tag('input', array('name' => 'mins[]', 'size' => 3, 'type' => 'text', 'value' => $min)).
                '% '.get_string('grade_upto', 'condition').' '.
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
            foreach ($modinfo->get_cms() as $id => $cm) {
                if ($cm->completion) {
                    $completionoptions[$id] = $cm->name;
                }
            }
            asort($completionoptions);
            $completionoptions = array(0 => get_string('none', 'condition')) + $completionoptions;
        }
        $completionvalues = array(
            COMPLETION_COMPLETE => get_string('completion_complete', 'condition'),
            COMPLETION_INCOMPLETE => get_string('completion_incomplete', 'condition'),
            COMPLETION_COMPLETE_PASS => get_string('completion_pass', 'condition'),
            COMPLETION_COMPLETE_FAIL => get_string('completion_fail', 'condition'),
        );
        $elements = html_writer::select($completionoptions, 'cmids[]', $cmid, false).'&nbsp;'.
                html_writer::select($completionvalues, 'requiredcompletions[]', $requiredcompletion, false);

        return html_writer::tag('div', $elements, array('class' => 'format_flexpage_condition_completion'));
    }

    /**
     *
     */
    public function page_navigation_buttons($publishsignals = '', $bottom = false) {
        global $COURSE;

        $prev = $this->previous_button();
        $next = $this->next_button();

        $str = '';

        if (!empty($publishsignals) || !empty($bottom)) {
            if (empty($prev) && empty($next)) {
                $mid = 12;
            } else {
                $left = 4;
                $mid = 4;
                $right = 4;

                $str .= '<div class="region-content bootstrap row">';
                $str .= '<div class="page-nav-prev span'.$left.' col-'.$left.'">';
                $str .= $prev;
                $str .= '</div>';
                if (!empty($publishsignals)) {
                    $str .= '<div class="page-publishing span'.$mid.' col-'.$mid.'">'.$publishsignals.'</div>';
                }
                if (!empty($bottom)) {
                    $context = context_course::instance($COURSE->id);
                    if (has_capability('format/page:checkdata', $context)) {
                        $checkurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $COURSE->id));
                        $str .= '<div class="page-checkdata span'.$mid.' col-'.$mid.'">';
                        $str .= '<a class="btn" href="'.$checkurl.'" target="_blank">'.get_string('checkdata', 'format_page').'</a>';
                        $str .= '</div>';
                    }
                }
                $str .= '<div class="page-nav-next span'.$right.' col-'.$right.'">';
                $str .= $next;
                $str .= '</div>';
                $str .= '</div>';
                return $str;
            }
        } else {
            if (empty($prev) && empty($next)) {
                return;
            } else if (!empty($prev) && !empty($next)) {
                $left = 6;
                $right = 6;
            } else {
                $left = 12; // One of
                $right = 12; // One of
            }
        }

        $str .= '<div class="region-content bootstrap row-fluid">';
        if (!empty($prev)) {
            $str .= '<div class="page-nav-prev span'.$left.' col-'.$left.'">';
            $str .= $prev;
            $str .= '</div>';
        }
        if (!empty($publishsignals)) {
            $str .= '<div class="page-publishing span'.$mid.' col-'.$mid.'">'.$publishsignals.'</div>';
        }
        if (!empty($bottom)) {
            $context = context_course::instance($COURSE->id);
            if (has_capability('format/page:checkdata', $context)) {
                $checkurl = new moodle_url('/course/format/page/checkdata.php', array('id' => $COURSE->id));
                $str .= '<a class="btn" href="'.$checkurl.'" target="_blank">'.get_string('checkdata', 'format_page').'</a>';
            }
        }
        if (!empty($next)) {
            $str .= '<div class="page-nav-next span'.$right.' col-'.$right.'">';
            $str .= $next;
            $str .= '</div>';
        }
        $str .= '</div>';

        return $str;
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

    public function get_width($region) {

        $bootstrap = format_page_is_bootstrapped();

        if ($bootstrap) {
            if (!is_numeric($this->formatpage->bsprefcenterwidth)) {
                $this->formatpage->bsprefcenterwidth = 6;
            }
            if (!is_numeric($this->formatpage->bsprefleftwidth)) {
                $this->formatpage->bsprefleftwidth = 3;
            }
            if (!is_numeric($this->formatpage->bsprefrightwidth)) {
                $this->formatpage->bsprefrightwidth = 3;
            }
        }

        switch ($region) {
            case 'main':
                return ($bootstrap) ? $this->formatpage->bsprefcenterwidth : $this->formatpage->prefcenterwidth;
            case 'side-pre':
                return ($bootstrap) ? $this->formatpage->bsprefleftwidth : $this->formatpage->prefleftwidth;
            case 'side-post':
                return ($bootstrap) ? $this->formatpage->bsprefrightwidth : $this->formatpage->prefrightwidth;
            default:
                throw new coding_exception('Unknwon region '.$region.' in format_page page');
        }
    }

    public function section_availability_message($section, $canseehidden) {
        return parent::section_availability_message($section, $canseehidden);
    }

    public function start_section_list() {}

    public function end_section_list() {}

    public function page_title() {
        return $this->formatpage->nameone;
    }

    /**
     * 
     * @param type $pageid
     * @param type $course
     * @return string
     */
    public function add_members_form($pageid, &$course, &$potentialmembersselector, &$pagemembersselector) {

        $actionurl = new moodle_url('/course/format/page/actions/assignusers.php');
        $output = '<div id="addmembersform">';
        $output .= '<form id="assignform" method="post" action="'.$actionurl.'">';
        $output .= '<div>';
        $output .= '<input type="hidden" name="id" value="'.$course->id.'" />';
        $output .= '<input type="hidden" name="pageid" value="'.$pageid.'" />';
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .= '<div class="container-fluid">';
        $output .= '<div class="row-fluid">';
        $output .= '<div id="existingcell" class="span4">';
        $output .= '<p>';
        $output .= '<label for="removeselect">'.get_string('pagemembers', 'format_page').'</label>';
        $output .= '</p>';
        $output .= $pagemembersselector->display(true);
        $output .= '</div>';
        $output .= '<div id="buttonscell" class="span4">';
        $output .= '<p class="arrow_button">';
        $output .= '<input name="add" id="add" type="submit" value="'.$this->output->larrow().'&nbsp;'.get_string('add').'" title="'.get_string('add').'" /><br />';
        $output .= '<input name="remove" id="remove" type="submit" value="'.get_string('remove').'&nbsp;'.$this->output->rarrow().'" title="'.get_string('remove').'" />';
        $output .= '</p>';
        $output .= '</div>';
        $output .= '<div id="potentialcell" class="span4">';
        $output .= '<p>';
        $output .= '<label for="addselect">'.get_string('potentialmembers', 'format_page').'</label>';
        $output .= '</p>';
        $output .= $potentialmembersselector->display(true);
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * 
     * @global type $CFG
     * @global type $COURSE
     * @param type $path
     * @return string
     */
    public function import_file_from_dir_form($path) {
        global $CFG, $COURSE;

        $basepath = $CFG->dataroot.'/'.$COURSE->id.$path;
        $dir = opendir($basepath);

        $output = '<center>';
        $output .= '<div id="importfilesasresources-div">';
        $importurl = new moodle_url('/course/view.php');
        $output .= '<form name="importfilesasresources" action="'.$importurl.'" method="get">';
        $output .= '<input type="hidden" name="id" value="'.$COURSE->id.'" />';
        $output .= '<input type="hidden" name="action" value="importresourcesfromfiles" />';
        $output .= '<input type="hidden" name="path" value="'.$path.'" />';
        $output .= '<table width="100%">';
        $output .= '<tr><td><b>'.get_string('filename', 'format_page').'</b></td>';
        $output .= '<td><b>'.get_string('resourcename', 'format_page').'</b></td></tr>';
        $i = 0;

        while ($entry = readdir($dir)) {
            if (is_dir($basepath.'/'.$entry)) {
                continue;
            }
            if (preg_match('/^\./', $entry)) {
                continue;
            }

            $output .= $this->output->box_start('commonbox');

            $output .= '<tr><td>'.$entry.'<input type="hidden" name="file'.$i.'" value="'.$path.'/'.$entry.'" /></td>';
            $output .= '<td><input type="text" name="resource'.$i.'" size="50" /></td></tr>';
            $output .= '<tr><td><b>'.get_string('description').'</b></td>';
            $output .= '<td><textarea name="description'.$i.'" cols="50" rows="4" /></textarea></td></tr>';

            $output .= $this->output->box_end();
            $i++;
        }
        closedir($dir);

        $output .= '</table>';
        $output .= '<p><input type="submit" name="collecttitles" value="'.get_string('submit').'" />';
        $jshandler = 'window.location.href = '.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.';';
        $output .= '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="'.$jshandler.'" /></p>';
        $output .= '</form>';
        $output .= '</div>';
        $output .= '</center>';
        return $output;
    }

    /**
     * @global type $CFG
     * @global type $COURSE
     * @param type $paths
     * @return string
     */
    public function import_file_form($paths) {
        global $CFG, $COURSE;

        $output = '<center>';
        $output .= '<div id="importfilesasresources-div">';
        $output .= '<form name="importfilesasresources" action="'.$CFG->wwwroot.'/course/view.php" method="get">';
        $output .= '<input type="hidden" name="id" value="'.$COURSE->id.'" />';
        $output .= '<input type="hidden" name="action" value="importresourcesfromfiles" />';
        $output .= get_string('choosepathtoimport', 'format_page');
        $output .= html_writer::select($paths, 'path');
        $output .= '<input type="submit" name="go_btn" value="' . get_string('submit') . '" />';
        $jshandler = 'document.location.href = '.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
        $output .= '<input type="button" name="cancel_btn" value="' . get_string('cancel') . '" onclick="'.$jshandler.'" />';
        $output .= '</form>';
        $output .= '</div>';
        $output .= '</center>';

        return $output;
    }

    /**
     * 
     * @global type $COURSE
     * @param type $pageid
     * @return type
     */
    public function search_activities_button($pageid) {
        global $COURSE;

        $output = get_string('search') . '&nbsp;:';
        $jshandler = 'reload_activity_list(\'' . $COURSE->id . '\',\'' . $pageid . '\', this)';
        $output .= ' <input type="text" name="cmfilter" onchange="'.$jshandler.'" />';
        return $output;
    }

    protected function get_image_url($imgname) {
        global $PAGE;

        $fs = get_file_storage();

        $context = context_system::instance();

        $haslocalfile = false;
        $frec = new StdClass;
        $frec->contextid = $context->id;
        $frec->component = 'format_page';
        $frec->filearea = 'pagerendererimages';
        $frec->filename = $imgname.'.svg';
        if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
            $frec->contextid = $context->id;
            $frec->component = 'format_page';
            $frec->filearea = 'pagerendererimages';
            $frec->filename = $imgname.'.png';
            if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                $frec->contextid = $context->id;
                $frec->component = 'format_page';
                $frec->filearea = 'pagerendererimages';
                $frec->filename = $imgname.'.jpg';
                if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                    $frec->contextid = $context->id;
                    $frec->component = 'format_page';
                    $frec->filearea = 'pagerendererimages';
                    $frec->filename = $imgname.'.gif';
                    if ($fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                        $haslocalfile = true;
                    }
                } else {
                    $haslocalfile = true;
                }
            } else {
                $haslocalfile = true;
            }
        } else {
            $haslocalfile = true;
        }

        if ($haslocalfile) {
            $fileurl = moodle_url::make_pluginfile_url($frec->contextid, $frec->component, $frec->filearea, 0, '/',
                                                    $frec->filename, false);
            return $fileurl;
        }

        if ($PAGE->theme->resolve_image_location($imgname, 'theme', true)) {
            $imgurl = $this->output->image_url($imgname, 'theme');
        } else {
            return $this->output->image_url($imgname, 'format_page');
        }

        return $imgurl;
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
        global $COURSE;

        $pagerenderer = $this->page->get_renderer('format_page');

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
        if (isset($bc->completion)) {
            $completion = $pagerenderer->print_cm_completion($COURSE, $bc->completioncompletioninfo, $bc->completion->mod, array());
        }

        $output = '';
        if ($title || $controlshtml) {
            $output .= html_writer::tag('div', html_writer::tag('div', html_writer::tag('div', '', array('class' => 'block_action')).$title.$controlshtml.' '.$completion, array('class' => 'title')), array('class' => 'header'));
        }

        return $output;
    }

    public function assigngroup_form($page) {
        global $COURSE;

        $str = '';

        $str .= '<div id="addgroupsform">';
        $formurl = new moodle_url('/course/format/page/actions/assigngroups.php', array('page' => $page->id));
        $str .= '<form id="assignform" method="post" action="'.$formurl.'">';
        $str .= '<div>';
        $str .= '<input type="hidden" name="id" value="'.$COURSE->id.'" />';
        $str .= '<input type="hidden" name="pageid" value="'.$page->id.'" />';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        $str .= '<table class="generaltable generalbox pagemanagementtable boxaligncenter" summary="">';
        $str .= '<tr>';
        $str .= '  <td id="existingcell">';
        $str .= '<p>';
        $str .= '<label for="removeselect">'.print_string('pagegroups', 'format_page').'</label>';
        $str .= '</p>';
        $str .= $pagegroupsselector->display();
        $str .= '</td>';
        $str .= '<td id="buttonscell">';
        $str .= '<p class="arrow_button">';
        $addstr = $this->output->larrow().'&nbsp;'.get_string('add');
        $str .= '<input name="add" id="add" type="submit" value="'.$addstr.'" title="'.get_string('add').'" /><br />';
        $removestr = get_string('remove').'&nbsp;'.$this->output->rarrow();
        $str .= '<input name="remove" id="remove" type="submit" value="'.$removestr.'" title="'.get_string('remove').'" />';
        $str .= '</p>';
        $str .= '</td>';
        $str .= '<td id="potentialcell">';
        $str .= '<p>';
        $str .= '<label for="addselect">'.get_string('potentialgroups', 'format_page').'</label>';
        $str .= '</p>';
        $str .= $potentialgroupsselector->display();
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</div>';
        $str .= '</form>';
        $str .= '</div>';
    }

}
