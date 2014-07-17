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

require_once($CFG->dirroot.'/course/format/page/blocklib.php');
require_once($CFG->dirroot.'/course/format/page/cli/fixlib.php');

/**
 * Objectivates format_page page instance with all necessary methods
 * to get related information
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 * @version $Id: pagelib.php,v 1.12 2012-07-30 15:02:46 vf Exp $
 * @package format_page
 */

// Status settings for display options.
define('FORMAT_PAGE_DISP_HIDDEN', 0);  // Publish page (show when editing turned off).
define('FORMAT_PAGE_DISP_PUBLISHED', 1);  // Publish page (show when editing turned off).
define('FORMAT_PAGE_DISP_PROTECTED', 2);  // Protected page (only for capability enabled people).
define('FORMAT_PAGE_DISP_PUBLIC', 3);  // Publish page (show when editing turned off).

// Display constants for previous & next buttons.
define('FORMAT_PAGE_BUTTON_NEXT', 1);
define('FORMAT_PAGE_BUTTON_PREV', 2);
define('FORMAT_PAGE_BUTTON_BOTH', 3);

define('FORMAT_PAGE_SHOW_TOP_NAV', 1);
define('FORMAT_PAGE_SHOW_BOTTOM_NAV', 2);

require_once($CFG->dirroot.'/course/lib.php'); // Needed for some blocks.

/**
 * Class that models the behavior of a format page
 *
 * @package format_page
 */
class course_page {

    /**
     * Full format_page db record
     *
     * @var object
     **/
    protected $formatpage = null;

    /**
     * format_page_item record ID
     *
     * @var string
     */
    public $pageitemid = 0;
    
    /**
    * for page tracking
    * 1 if page has one log track for the user
    */
    public $accessed = 0;

    /**
    * for page tracking
    * 1 if all subpage tree has been completely accessed 
    */
    public $complete = 0;

    /**
     * depth of page in course hierarchy
     *
     * @var string
     **/
    protected $pagedepth = null;

    /**
     * previous page visible in navigation. This may change against $USER situation
     * Is loaded once when trying to get prev page information
     *
     * @var string
     */
    public $prevpage = null;

    /**
     * next page visible in navigation. This may change against $USER situation.
     * Is loaded once when trying to get next page information
     *
     * @var string
     */
    public $nextpage = null;

    /**
    * the page childs. this array is fed by instance method get_children, or
    * on page structure builder @see self::get_all_pages().
    */
    public $childs = null;
    
    /**
    * The parent page, if computed.
    *
    */
    public $parentpage = null;

    /**
    *
    *
    */
    public static function load($pageid) {
        global $DB;

        $formatpagerec = $DB->get_record('format_page', array('id' => $pageid));
        if ($formatpagerec) {
            return new course_page($formatpagerec);
        } else {
            return new course_page(null);
        }
    }

    /**
     *
     *
     */
    function __construct($formatpagerec) {
        if ($formatpagerec) {
            $this->formatpage = $formatpagerec;
        } else {
            $this->formatpage = course_page::instance();
        }
    }

    /**
     * wraps a magic getter to internal fields
     *
     */
    function __get($fieldname){

        // Real field overseeds.
        if (isset($this->$fieldname)) {
            return $this->$fieldname;
        }
        if (isset($this->formatpage->$fieldname)) {
            return $this->formatpage->$fieldname;
        } else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldname.' in format_page object');
        }
    }

    /**
     * wraps a magic getter to internal fields
     *
     */
    public function __set($fieldname, $value) {

        // Real field overseeds.
        if (isset($this->$fieldname)) {
            $this->$fieldname = $value;
        }

        if (isset($this->formatpage->$fieldname)) {
            $magicmethodname = 'magic_set_'.$fieldname;
            if (method_exists('format_page', $magicmethodname)) {
                // Allows override with checked setters if needed.
                $this->$magicmethodname($value);
            } else {
                $this->formatpage->$fieldname = $value;
            }
        } else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldname.' in format_page object');
        }
    }

    /**
     *
     *
     */
    public function save() {
        global $DB;

        if (!is_object($this->formatpage)) {
            return 0;
        }

        if (@$this->formatpage->id) {
            return $DB->update_record('format_page', $this->formatpage);
        } else {
            $this->formatpage->id = $DB->insert_record('format_page', $this->formatpage);
            return $this->formatpage->id;
        }
    }

    /**
     * Local method - set the member formatpage
     *
     * @return void
     **/
    public function set_formatpage($formatpage) {
        $this->formatpage = $formatpage;
    }

    /**
     * Local method - set the member pageitemid
     * This is very important as it is used in URL
     * construction.
     *
     * @return void
     */
    public function set_pageitemid($pageitemid) {
        $this->pageitemid = $pageitemid;
    }

    /**
     * Local method - returns the current
     * format_page. If page is not initialized, 
     * we try to get in order : 
     * - The current page stored in user's session
     * - The first accessible page
     *
     * @return object
     */
    public function get_formatpage() {
        if ($this->formatpage == null) {
            global $CFG, $COURSE;

            require_once($CFG->dirroot.'/course/format/page/lib.php');
            
            if ($currentpage = course_page::get_current_page($COURSE->id)) {
                $this->formatpage = $currentpage;
            } else {
                $this->formatpage     = new stdClass;
                $this->formatpage->id = 0;
            }
        }
        return $this->formatpage;
    }

    /**
     * Gets the name of a page
     *
     * @param object $page Full page format page
     * @return string
     */
    public function get_name() {
        if (!empty($this->nametwo)) {
            $name = $this->nametwo;
        } else {
            $name = $this->nameone;
        }
        return format_string($name);
    }
    
    /**
     * Gets the name of a page
     *
     * @param object $page Full page format page
     * @return string
     */
    public function get_menuname() {
        $name = $this->nametwo;
        return format_string($name);
    }
    
    /**
     * Computes the depth of the current page and stores it
     *
     */
    public function get_page_depth() {
        if (is_null($this->pagedepth)) {
            $this->pagedepth = 0;
            // todo get depth
            $parentpage = $this;
            while ($parentpage = $parentpage->get_parent()) {
                $this->pagedepth++;
            }
        }
        return $this->pagedepth;
    }

    /**
     * Computes the depth of the current page and stores it
     * @param int $depth
     */
    public function set_depth($depth) {
        if ($depth < 0) {
            throw new coding_exception('Page depth cannot be negative');
        }
        $this->depth = $depth;
    }

    public function has_children() {
        return (!empty($this->childs));
    }

    public function get_children() {
        global $DB;

        if (is_null($this->childs)) {

            // As first try try to get childs in cache - faster.
            if ($allpages = course_page::get_all_pages($this->courseid, 'flat')) {
                if (isset($allpages[$this->formatpage->id])) {
                    $this->childs = & $allpages[$this->formatpage->id]->childs;
                }
            }

            // If cache not build, get children from DB - slower and more memory costfull.
            if (is_null($this->childs)) {
                if ($childrenrecs = $DB->get_records('format_page', array('parent' => $this->formatpage->id), 'sortorder')) {
                    foreach ($childrenrecs as $ch) {
                        $this->childs[$ch->id] = new course_page($ch);
                    }
                }
            }
        }
        return $this->childs;
    }

    /**
    * Get parent page
    *
    */
    public function get_parent($getid = false) {
        global $DB;

        if ($getid) {
            // Fastest.
            return $this->formatpage->parent;
        }

        if ($this->formatpage->parent) {
            if (is_null($this->parentpage)) {
                // Some caching effect.
                $parentrec = $DB->get_record('format_page', array('id' => $this->formatpage->parent));
                $this->parentpage = new course_page($parentrec);
            }
            return $this->parentpage;
        }
        return null;
    }

    /**
     * Get the parents of the passed page
     *
     * @param int $pageid ID of the page to find parents
     * @param int $courseid ID of the course that the page belongs to
     * @return array
     **/
    function get_parents() {
        global $COURSE;

        $parents = array();

        if ($allpages = course_page::get_all_pages($COURSE->id, 'flat')) {
            $pageid = $this->formatpage->id;
            while ($pageid != 0 and !empty($allpages[$pageid])) {
                $parents[$pageid] = $allpages[$pageid];
                $pageid = 0 + @$allpages[$pageid]->parentpage->id;
            }
            // Flip array around so top lvl parent is first.
            $parents = array_reverse($parents, true);
        }
        return $parents;
    }

    /**
     * Gets all possible page parents for the given page. This essentially excludes
     * all its owned children to avoid circular references
     * @param int $pageid ID of the page to find parents for (0 is fine)
     * @param int $courseid ID of the course that the page belongs to
     * @return mixed
     */
    function get_possible_parents($courseid, $allpages) {
        if ($parents = course_page::get_all_pages($courseid, 'flat')) {

            if (!$allpages) {
                unset($parents[$this->id]); // Discard self.
    
                if ($children = $this->get_children()) {
                    $this->filter_children($parents, $children);
                }
            }
        } else {
            $parents = false;
        }
        return $parents;
    }

    /**
     * Utility recursion for removing subbranches. Give a structure and 
     * an array of children to remove. Will recusively dig into branches to remove
     * all subs.
     */
    protected function filter_children(&$flatstructure, $children) {
        foreach($children as $c) {
            if ($subchilds = $c->get_children()) {
                $this->filter_children($flatstructure, $subchilds);
            }
            unset($flatstructure[$c->id]);
        }
    }

    /**
     * Get next visible page using page cache if available
     *
     */
    public function get_next($returnid = false) {
        global $COURSE;

        if (is_null($this->nextpage)) {
            if (!$allpages = self::get_all_pages($COURSE->id, 'flat')) {
                return null;
            }
            
            $allkeys = array_keys($allpages);
            // run to current page location
            if (!empty($allkeys)) {
                // Should never but on the first new page.
                $found = -1;
                $i = 0;
                foreach ($allkeys as $key) {
                    if ($key == $this->formatpage->id) {
                        $found = $i;
                        break;
                    }
                    $i++;
                }
                
                // We have the pos we can explore forth.
                if ($found >= 0) {
                    $found++;
                    while ($found < count($allkeys)) {
                        $page = $allpages[$allkeys[$found]];
                        if ($page->is_visible()) {
                            $this->nextpage = $page;
                            return $page;
                        }
                        $found++;
                    }
                }
            }
        }
        if ($returnid) {
            if (!is_null($this->nextpage)) {
                $this->nextpage->id;
            }
            return 0;
        }
        return $this->nextpage;
    }

    /**
    * Get previous visible page using page cache if available
    *
    */
    public function get_previous($returnid = false) {
        global $COURSE;

        if (is_null($this->prevpage)) {
            if (!$allpages = self::get_all_pages($COURSE->id, 'flat')) {
                return null;
            }
            $allkeys = array_keys($allpages);
            // Run to current page location.
            if (!empty($allkeys)) {
                // Should never but on the first new page.
                $found = -1;
                $i = 0;
                foreach ($allkeys as $key) {
                    if ($key == $this->formatpage->id) {
                        $found = $i;
                        break;
                    }
                    $i++;
                }

                // We have the pos we can explore forth.
                if ($found > 0) {
                    $found--;
                    while ($found >= 0) {
                        $page = $allpages[$allkeys[$found]];
                        if ($page->is_visible()) {
                            $this->prevpage = $page;
                            return $page;
                        }
                        $found--;
                    }
                }
            }
        }
        if ($returnid) {
            if (!is_null($this->prevpage)) {
                $this->prevpage->id;
            }
            return 0;
        }
        return $this->prevpage;
    }

    /**
     *
     *
     */
    public function is_visible($bypass = true) {
        global $COURSE;

        $visible = true;

        $context = context_course::instance($COURSE->id);

        if (($this->formatpage->display == FORMAT_PAGE_DISP_PUBLIC)) {
            return $visible;
        }

        if (($this->formatpage->display == FORMAT_PAGE_DISP_PUBLISHED) && isloggedin() && has_capability('format/page:viewpublishedpages', $context)) {
            $result = $this->check_user_access();
            $result = $result || $this->check_group_access();

            $result = $result && $this->check_date(true);
            return $result;
        } else {
            $result = false;
        }
        if (($this->formatpage->display == FORMAT_PAGE_DISP_PROTECTED) && has_capability('format/page:viewhiddenpages', $context)) {
            return $bypass;
        }
        if (($this->formatpage->display == FORMAT_PAGE_DISP_HIDDEN) && has_capability('format/page:editpages', $context)) {
            return $bypass;
        }
        return false;
    }

    /**
    *
    *
    */
    public function is_module_visible($cm, $bypass = true) {
        global $DB;

        if ($publishedpages = $DB->get_records('format_page_items', array('cmid' => $cm->id))) {
            foreach ($publishedpages as $p) {
                if ($page->is_visible($bypass)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_user_rules() {
        global $DB;

        return $DB->get_records('format_page_access', array('pageid' => $this->id, 'policy' => 'user'));
    }

    public function get_group_rules() {
        global $DB;

        return $DB->get_records('format_page_access', array('pageid' => $this->id, 'policy' => 'group'));
    }

    public function get_profile_rules() {
        global $DB;

        return $DB->get_records('format_page_access', array('pageid' => $this->id, 'policy' => 'profile'));
    }

    /**
     * checks user access with user policy records
     *
     */
    function check_user_access() {
        global $DB, $USER, $COURSE;

        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('format/page:viewhiddenpages', $coursecontext)) {
            return true;
        }

        if (!$userclauses = $this->get_user_rules()) {
            // If no user registered, let go everyone through.
            return true;
        }

        foreach ($userclauses as $ua) {
            if ($ua->arg1int == $USER->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks group access with group policy records
     *
     */
    public function check_group_access() {
        global $DB, $USER, $COURSE;

        if (!$groupclauses = $DB->get_group_rules()) {
            // If no user registered, let go everyone through.
            return true;
        }

        // Allgroup capable can view all even if group resricted.
        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('moodle/site:accessallgroups', $coursecontext, $USER->id)) {
            return true;
        }

        // Get all user groups in all groupings.
        $usergroupids = array();
        if ($usergroupings = groups_get_user_groups($COURSE->id, $USER->id)) {
            foreach ($usergroupings as $pgpid => $gpg) {
                $usergroupids = array_merge($usergroupids, array_keys($gpg));
            }
        }

        foreach ($groupclauses as $ua) {
            if (in_array($ua->arg1int, $usergroupids)) {
                return true;
            }
        }

        return false;
    }

    function check_date($bypass = false) {
        global $COURSE;

        $now = time();

        $coursecontext = context_course::instance($COURSE->id);
        if ($bypass && has_capability('format/page:viewhiddenpages', $coursecontext)) {
            return true;
        }

        if ($this->relativeweek) {
            if ($now > $COURSE->startdate + $this->relativeweek * DAYSECS * 7) {
                return true;
            }
        } else {
            if (!$this->datefrom && !$this->dateto) {
                return true;
            }
        }

        return (($this->datefrom && $now > $this->datefrom) && ($this->dateto && $now < $this->dateto));
    }

    /**
     * Preps a page name for being added to a menu dropdown
     *
     * @param string $name Page name
     * @param int $amount Amount of padding (Page depth for example)
     * @param int $length Can shorten the name so the dropdown does not get too wide (Pass NULL avoid shortening)
     * @return string
     **/
    public function name_menu($renderer, $length = 28) {
        $name = format_string($this->nameone);
        if ($length !== NULL) {
            $name = shorten_text($name, $length);
        }
        if ($renderer) {
            return $renderer->pad_string($name, $this->get_page_depth());
        } else {
            return $name;
        }
    }

    /**
     * Override - this is a three column format // obsolete, deferred to block manager and page layout
     *
     * @return array
     **/

    /**
     * Override - we like center because... well we do! // obsolete, deferred to block manager and page layout
     *
     * @return char
     **/

    /**
     * Override - since we have three columns
     * we need to take that into account here
     * DEPRECATED
     *
     * @param object $instance Block instance
     * @param int $move Move constant (BLOCK_MOVE_RIGHT or BLOCK_MOVE_LEFT). This is the direction that we are moving
     * @return char
     **/
    public function blocks_move_position(&$instance, $move) {
        if ($instance->position == BLOCK_POS_LEFT and $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_CENTER;
        } elseif ($instance->position == BLOCK_POS_RIGHT and $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_CENTER;
        } elseif ($instance->position == BLOCK_POS_CENTER and $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        } elseif ($instance->position == BLOCK_POS_CENTER and $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        }
        return $instance->position;
    }

    /**
     * Override - If pageitemid is set, then
     * return path to format.php (this is to handle
     * blockactions.  If we are at the site, then
     * path to index.php and our default is
     * course/view.php
     *
     * @return string
     **/
    public function url_get_path(&$params, $forceaspage = false) {
        global $CFG, $COURSE, $DB;

        $pageaction = @$params['action'];
        $action = optional_param('action', '', PARAM_TEXT);
        $aspage = optional_param('aspage', $forceaspage, PARAM_INT);
        if ($this->cmid && empty($action) && $aspage) {
            // We should not be in management screens.
            $cm = $DB->get_record('course_modules', array('id' => $this->cmid));
            $mod = $DB->get_record('modules', array('id' => $cm->module));
            unset($params['id']);
            return $CFG->wwwroot.'/mod/'.$mod->name.'/view.php?id='.$cm->id.'&aspage='.$this->id;
        }
        if ($pageaction == 'addpage') {
            return $CFG->wwwroot.'/course/format/page/actions/editpage.php?id='.$COURSE->id;
        } elseif (!empty($pageaction)) {
            // All non actions implemented pages use course/view controller.
            if (!file_exists($CFG->dirroot."/course/format/page/actions/{$pageaction}.php")) {
                return $CFG->wwwroot.'/course/format/page/action.php';
            }
            return $CFG->wwwroot."/course/format/page/actions/{$pageaction}.php?id=".$COURSE->id.'&page='.$this->id;
        }
        if ($this->pageitemid) {
            return $CFG->wwwroot.'/course/format/page/action.php';
        } else if ($COURSE->id == SITEID) {
            return $CFG->wwwroot.'/index.php';
        } else {
            return $CFG->wwwroot.'/course/view.php';
        }
    }

    /**
     * Override - VERY IMPORTANT
     * Include page and pageitemid (if set) in the params
     * so the format knows what page we are on and
     * so it can uniquely identify the block_instance (EG: page item)
     * This is needed because multiple page items can relate to a
     * single block instance
     *
     * @return array
     **/
    public function url_get_parameters() {
        $pagerec = $this->get_formatpage();

        $params = array('id' => $pagerec->courseid, 'page' => $pagerec->id);

        if ($this->pageitemid) {
            $params['pageitemid'] = $this->pageitemid;
        }

        return $params;
    }

    /**
     * Local method - Builds an appropriate URL
     *
     * Pass as many parameter name and value pairs
     * as you like.  This function will contstruct
     * a URL with them
     *
     * If param name id is not passed and this is
     * not the site front page, then the id param is
     * automatically added.
     *
     * @return string
     **/
    public function url_build() {
        global $CFG;

        $args = func_get_args();

        $key    = '';
        $params = array();
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = $args[$i];

            if ($i % 2 == 0) {
                $key = $arg;
                $params[$arg] = '';
            } else {
                $params[$key] = $arg;
            }
        }

        $aspage = false;
        if (array_key_exists('aspage', $params)) {
            $aspage = $params['aspage'];
            unset($params['aspage']);
        }
        
        if ($this->courseid != SITEID and !array_key_exists('id', $params)) {
            $params['id'] = $this->courseid;
        }

        $wheretogo = $this->url_get_path($params, $aspage);

        $pairs = array();
        foreach ($params as $name => $value) {
            $pairs[] = "$name=$value";
        }

        if (strstr($wheretogo, '?') !== false) {
            return $wheretogo.'&'.implode('&', $pairs);
        }
        return $wheretogo.'?'.implode('&', $pairs);
    }

    /**
     * Override - can the user edit?
     *
     * @return boolean
     **/
    public function user_allowed_editing() {
        if (has_capability('format/page:editpages', context_course::instance($this->formatpage->courseid))) {
            return true;
        }
        return parent::user_allowed_editing();
    }

    /**
     * Override - cache result
     *
     * @return boolean
     **/
    public function user_is_editing() {
        global $PAGE;

        static $cache = NULL;

        if ($cache === NULL) {
            $cache = $PAGE->user_is_editing();
        }
        return $cache;
    }

    function get_type() {
        return 'course-view-page';
    }

   // Access management.

    // Adds a user access member.
    public function add_member($userid) {
        global $DB;

        $rec->pageid = $this->id;
        $rec->policy = 'user';
        $rec->arg1int = $userid;

        // Ensures it is in.
        $DB->delete_records('format_page_access', array('pageid' => $this->id, 'policy' => 'user', 'arg1int' => $userid));
        return $DB->insert_record('format_page_access', $rec);
    }

    // Removes a user access member.
    public function remove_member($userid) {
        global $DB;

        $rec->pageid = $this->id;
        $rec->policy = 'user';
        $rec->arg1int = $userid;

        // Ensures it is out.
        return $DB->delete_records('format_page_access', array('pageid' => $this->id, 'policy' => 'user', 'arg1int' => $userid));
    }

    // Adds a group access member.
    function add_group($groupid) {
        global $DB;

        $rec->pageid = $this->id;
        $rec->policy = 'group';
        $rec->arg1int = $groupid;

        // Ensures it is in.
        $DB->delete_records('format_page_access', array('pageid' => $this->id, 'policy' => 'group', 'arg1int' => $groupid));
        return $DB->insert_record('format_page_access', $rec);
    }

    // Removes a group access member.
    function remove_group($groupid) {
        global $DB;

        $rec->pageid = $this->id;
        $rec->policy = 'group';
        $rec->arg1int = $groupid;

        // Ensures it is out.
        return $DB->delete_records('format_page_access', array('pageid' => $this->id, 'policy' => 'group', 'arg1int' => $groupid));
    }

    public function has_user_accesses() {
        global $DB;

        return $DB->record_exists('format_page_access', array('pageid' => $this->id, 'policy' => 'user'));
    }

    public function has_group_accesses() {
        global $DB;

        return $DB->record_exists('format_page_access', array('pageid' => $this->id, 'policy' => 'group'));
    }

    /**
     * checks if the page has a completion lock. 
     *
     */
    public function check_activity_lock() {
        global $USER, $CFG, $COURSE, $DB;

        require_once($CFG->libdir.'/gradelib.php');

        if ($this->lockingcmid) {
            if (!$cm = $DB->get_record('course_modules', array('id' => $this->lockingcmid))) {
                // The locking module was deleted. clean up the lockingcmid.
                // TODO : check the restore case, and restore reencoding of this cmid.
                $DB->set_field('format_page', 'lockingcmid', 0, array('id' => $this->id));
                return true;
            }
            $module = $DB->get_record('modules', array('id' => $cm->module));

            $gradecap = 'mod/'.$module->name.':grade';

            // Graders for this module can pass through.
            if (!$DB->get_record('capabilities', array('name' => $gradecap))) {
                // Non explicit gradable modules will not do anything.
                return true;
            }
            if (has_capability($gradecap, context_module::instance($cm->id))) {
                return true;
            }

            if ($usergrades = grade_get_grades($cm->course, 'mod', $module->name, $cm->instance, $USER->id)) {
                $usergradestruct = reset($usergrades->items);
                $grademax = $usergradestruct->grademax;
                $grademin = $usergradestruct->grademin;
                $usergrade = @$usergradestruct->grades[$USER->id]->grade;
                $usercompletion = $usergrade / ($grademax - $grademin) * 100;

                return($usercompletion >= $this->lockingscore);
            }
            return false;
        }
        return true;
    }

    /**
     * get an array of activities in this page in order of implantation
     *
     */
    public function get_activities() {
        global $DB;

        $sql = "
            SELECT
                cm.*
            FROM
                {course_modules} cm,
                {format_page_items} fpi
            WHERE
                fpi.cmid != NULL AND
                cm.id = fpi.cmid AND
                fpi.pageid = ?
            ORDER BY
                fpi.sortorder
        ";

        return $DB->get_records_sql($sql, array($this->id));
    }

    function delete_all_blocks() {
        global $DB;

        // Delete all page items.
        if ($pageitems = $DB->get_records('format_page_items', array('pageid' => $this->id))) {
            foreach ($pageitems as $pageitem) {
                $this->block_delete($pageitem);
            }
        }
    }

    /**
     * this static function can delete all blocks belonging
     * to a particular course module, in all pages or just in one page
     */
    public static function delete_cm_blocks($cmid, $pageid = 0) {
        global $DB;

        $wheres = array('cmid' => $cmid);
        if ($pageid) {
            $wheres['pageid'] = $pageid;
        }
        
        // Delete all page items in the given scope.
        if ($pageitems = $DB->get_records('format_page_items', $wheres)) {
            foreach ($pageitems as $pageitem) {
                $page = course_page::get($pageitem->pageid);
                $page->block_delete($pageitem);
            }
        }
    }


    public function delete_section($verbose = false) {
        global $DB, $COURSE;

        // Delete the section.
        if ($verbose) {
            echo "Delete section $this->section \n";
        }
        $DB->delete_records('course_sections', array('course' => $COURSE->id, 'section' => $this->section));

        if ($verbose) {
            echo "Pulling down sections after $this->section \n";
        }

        // Remap all higher range sections.
        $sql = "
            UPDATE
                {course_sections}
            SET
                section = section - 1
            WHERE
                course = ? AND
                section > ?
        ";
        $DB->execute($sql, array($COURSE->id, $this->section));

        if ($verbose) {
            echo "Pulling down page sections after $this->section \n";
        }

        // Remap all format_pages for sections.
        $sql = "
            UPDATE
                {format_page}
            SET
                section = section - 1
            WHERE
                courseid = ? AND
                section > ?
        ";
        $DB->execute($sql, array($COURSE->id, $this->section));
    }

    /**
     * @param int $sid section num
     * @param object $restoretask if used from a restore automaton, �map the ids to new ids
     * @return the newly inserted section ID
     */
    public function make_section($sid, $restoretask = null, $verbose = false) {
        global $DB;

        if ($verbose) {
            echo "Making section id $sid \n";
        }

        $sequence = '';
        if (!empty($this->formatpage->id)) {
            if ($cmitems = $DB->get_records_select_menu('format_page_items', " pageid = ? AND cmid != 0 ", array($this->id), 'id', 'id,cmid')) {
    
                // If used in a restore process, the activity page item are not yet remapped by the page_module post process.
                if ($restoretask) {
                    foreach ($cmitems as $id => $it) {
                        $cmitems[$id] = $restoretask->external_get_mappingid('course_module', $it);
                    }
                }
                $sequence = implode(',', array_values($cmitems));
            }
        }

        $sectionrec = new StdClass();
        $sectionrec->course = $this->courseid;
        $sectionrec->section = $sid;
        $sectionrec->name = $this->nametwo;
        $sectionrec->summary = '';
        $sectionrec->summaryformat = '';
        $sectionrec->sequence = $sequence;
        $sectionrec->visible = 1;
        if (!$oldsection = $DB->get_record('course_sections', array('course' => $this->courseid, 'section' => $sid))) {
            $sectionrec->id = $DB->insert_record('course_sections', $sectionrec);
        } else {
            $sectionrec->id = $oldsection->id;
            $DB->update_record('course_sections', $sectionrec);
        }

        // Remap all course modules to updated section id.
        if (!empty($cmitems)) {
            foreach ($cmitems as $cid => $it) {
                $DB->set_field('course_modules', 'section', $sectionrec->id, array('id' => $it));
            }
        }

        // Remap the page to proper section.
        $this->section = $sid;
        return $sectionrec->id;
    }

    /**
     * @param int $sid section num
     * @param object $restoretask if used from a restore automaton, map the ids to new ids
     */
    public function update_section() {
        global $DB;

        $section = $DB->get_record('course_sections', array('course' => $this->courseid ,'section' => $this->section));
        $section->name = $this->nametwo;

        $sequence = '';
        if (!empty($this->formatpage->id)) {
            if ($cmitems = $DB->get_records_select_menu('format_page_items', " pageid = ? AND cmid != 0 ", array($this->id), 'id', 'id,cmid')) {
                $sequence = implode(',', array_values($cmitems));
            }
        }

        $visibleforstudents = $this->formatpage->display == FORMAT_PAGE_DISP_HIDDEN || $this->formatpage->display == FORMAT_PAGE_DISP_PROTECTED;

        $section->sequence = $sequence;
        $section->visible = $visibleforstudents;
        $DB->update_record('course_sections', $section);
    }

    /**
     * This function removes blocks/modules from a page
     *
     * @param object $pageitem a fully populated page_item object
     * @uses $CFG
     * @uses $COURSE
     */
    public function block_delete($pageitem) {
        global $CFG, $DB;

        require_once($CFG->libdir.'/blocklib.php');

        // We leave module cleanup to the manage modules tab... blocks need some help though.
        if ($blockinstance = $DB->get_record('block_instances', array('id' => $pageitem->blockinstance))) {

            // See if this is the last reference to the blockinstance.
            $count = $DB->count_records('format_page_items', array('blockinstance' => $pageitem->blockinstance));

            if ($count == 1) {
                if ($block = blocks_get_record($blockinstance->id)) {
                    if ($block->name != 'navigation' || $block->name != 'settings') {
                        /*
                         * At this point, the format has done all of its own checking,
                         * hand it off to block API
                         */
                        blocks_delete_instance($blockinstance);
                    }
                }
            }
        }

        $DB->delete_records('format_page_items', array('id' => $pageitem->id));
    }

    // Statics defines : where no instance is available or when processing over object scope.

    /**
     * Builds an initial state of the object content with default or contextual defaults
     *
     */
    public static function instance($parentid = 0) {
        global $COURSE, $CFG;

        // Initialise a new record.
        $formatpagerec = new StdClass();
        $formatpagerec->id = 0; // New record.
        $formatpagerec->courseid = $COURSE->id;
        $formatpagerec->nameone = get_string('newpagename', 'format_page');
        $formatpagerec->nametwo = get_string('newpagelabel', 'format_page');
        $formatpagerec->display = 0 + @$CFG->format_page_initially_displayed;
        $formatpagerec->prefleftwidth = self::__get_default_width('side-pre');
        $formatpagerec->prefcenterwidth = self::__get_default_width('main');
        $formatpagerec->prefrightwidth = self::__get_default_width('side-post');
        $formatpagerec->parent = $parentid;
        $formatpagerec->sortorder = page_get_next_sortorder($COURSE->id, $parentid);
        $formatpagerec->template = 0;
        $formatpagerec->showbuttons = FORMAT_PAGE_SHOW_TOP_NAV | FORMAT_PAGE_SHOW_BOTTOM_NAV;
        $formatpagerec->cmid = 0;
        $formatpagerec->lockingcmid = 0;
        $formatpagerec->lockingscore = 0;

        return $formatpagerec;
    }

    /**
     * get the best fitting available page in a course
     *
     */
    static function get_current_page($courseid = 0) {
        global $CFG, $USER, $COURSE;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }

        // Check session for current page ID only if we can store our current page.
        if (has_capability('format/page:storecurrentpage', context_course::instance($courseid)) && isset($USER->format_page_display[$courseid])) {
            if ($page = self::validate_pageid($USER->format_page_display[$courseid], $courseid)) {
                return $page;
            }
        }

        // Last try, attempt to get the default page for the course.
        if ($page = self::get_default_page($courseid)) {
            return $page;
        }

        return false;
    }

    /**
     * Gets the default first page for a course
     *
     * @param int $courseid (Optional) The course to look in
     * @return mixed Page object or false
     * @todo Check to make sure that the page being returned has any page items?  Still might be blank depending on blocks though.
     */
    public static function get_default_page($courseid = 0) {
        global $COURSE, $DB;

        $return = false;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }

        if (has_capability('format/page:managepages', context_course::instance($courseid))) {
            $display = false;
        } else {
            $display = FORMAT_PAGE_DISP_PUBLISHED;
        }

        if ($masterpages = self::get_master_pages($courseid, 1, $display)) {
            $return = current($masterpages);
        }
        if (!$return) {
            // OK, first try failed, try grabbing almost anything now.
            $select = "courseid = $courseid ";
            if ($display) {
                $select .= " AND display = ? ";
            }
            if ($pages = $DB->get_records_select('format_page', $select, array($display), 'sortorder,nameone', '*', 0, 1)) {
                $return = current($pages);
            }
        }

        return $return;
    }

    /** 
     * This function returns a number of "master" pages that are first in the sortorder
     *
     * @param int $courseid the course id to get pages from
     * @param int $limit (optional) the maximumn number of 'master' pages to return (0 meaning no limit);
     * @param int $display (optional) bitmask representing what display status pages to return
     * @return array of pages
     */
    public static function get_master_pages($courseid, $limit = 0, $seehidden = false) {

        if (!$allpages = self::get_all_pages($courseid)) {
            return false;
        }
        $pages = array();
        foreach ($allpages as $page) {
            if (!empty($limit) and count($pages) == $limit) {
                break;
            }
            if ($page->is_visible() || $seehidden) {
                $pages[] = $page;
            }
        }

        if (empty($pages)) {
            return false;
        }
        return $pages;
    }

    /**
     * Grabs all of the pages and organizes
     * them into their parent/child hierarchy
     * or into a logical flat structure.
     *
     * The result is cached and the whole operation
     * is performed with one database query.
     *
     * All page objects get a new attribute of depth, which
     * is their current depth in the parent/child hierarchy
     *
     * @param int $courseid ID of the course
     * @param string $structure The structure in which to organize the pages.  EG: flat or nested
     * @param boolean $clearcache If true, then the cache is reset for the passed structure
     * @return mixed False if no pages are found otherwise an array of page objects with children set
     */
    public static function get_all_pages($courseid, $structure = 'nested', $clearcache = false) {
        global $DB;
        static $cache = array();

        if (!in_array($structure, array('nested', 'flat'))) {
            print_error('errorunkownstructuretype', 'format_page', $structure);
        }

        if ($clearcache) {
            $cache = array();
        }

        if (empty($cache[$courseid])) {
            if ($allpages = $DB->get_records('format_page', array('courseid' => $courseid, 'parent' => 0), 'sortorder')) {
                foreach ($allpages as $p) {
                    $pobj = new course_page($p);
                    $cache[$courseid]['flat'][$p->id] = $pobj;
                    $cache[$courseid]['nested'][$p->id] = $pobj;
                    // get potential subtree
                    self::get_all_pages_rec($courseid, $pobj, $cache);
                }
            } else {
                $cache[$courseid] = array('nested' => false, 'flat' => false);
            }
        }

        return $cache[$courseid][$structure];
    }

    static protected function get_all_pages_rec($courseid,&$parentpage, &$cache) {
        global $DB;

        if ($allpages = $DB->get_records('format_page', array('courseid' => $courseid, 'parent' => $parentpage->id), 'sortorder')) {
            foreach ($allpages as $p) {
                $pobj = new course_page($p);
                $cache[$courseid]['flat'][$p->id] = $pobj;
                $parentpage->childs[$p->id] = $pobj;
                // Get potential subtree.
                self::get_all_pages_rec($courseid, $pobj, $cache);
            }
        }
    }

    /**
     * Makes sure that the current page ID
     * is an actual page ID and if the page
     * is published.  If not published,
     * then do a capability check to see
     * if the user can view unpubplished pages
     *
     * @param int $pageid ID to process
     * @param int $courseid ID of the current courese
     * @return mixed Page object or false
     */
    public static function validate_pageid($pageid, $courseid) {
        $return = false;
        $pageid = clean_param($pageid, PARAM_INT);

        if ($pageid > 0 and $page = self::get($pageid, $courseid)) {
            if ($page->courseid == $courseid and ($page->is_visible() or has_capability('format/page:editpages', context_course::instance($page->courseid)))) {
                // This page belongs to this course and is published or the current user can see unpublished pages.
                $return = $page;
            }
        }
        return $return;
    }

    /**
     * Gets a page object in page base (faster than load)
     * @see load
     * @param int $pageid ID of the page to be fetched
     * @param int $courseid ID of the course that the page belongs to
     * @return object
     **/
    public static function get($pageid, $courseid = NULL) {
        global $COURSE, $DB;

        if ($courseid === NULL) {
            $courseid = $COURSE->id;
        }

        // Attempt to find in cache, otherwise try the DB.
        if ($pages = self::get_all_pages($courseid, 'flat')) {
            if (array_key_exists($pageid, $pages)) {
                return clone($pages[$pageid]);
            }
        }

        if ($pagerec = $DB->get_record('format_page', array('id' => $pageid))) {
            return new course_page($pagerec);
        }

        return null;
    }

    /**
     * Gets a page object in page base (faster than load)
     * @see load
     * @param int $pageid ID of the page to be fetched
     * @param int $courseid ID of the course that the page belongs to
     * @return object
     **/
    public static function get_by_section($section, $courseid = null) {
        global $COURSE, $DB;

        if (is_null($courseid)) {
            $courseid = $COURSE->id;
        }

        try {
            $pagerec = $DB->get_record('format_page', array('courseid' => $courseid, 'section' => $section));
            return new course_page($pagerec);
        } catch (Exception $e) {
            self::fix_tree();
            $pagerec = $DB->get_record('format_page', array('courseid' => $courseid, 'section' => $section));
            return new course_page($pagerec);
        }

        return null;
    }

    /**
     * Sets the current page for the user
     * in their session.
     *
     * @param int $courseid ID of the current course
     * @param int $pageid ID of the page to set
     * @return int
     */
    static function set_current_page($courseid, $pageid) {
        global $USER;

        if (!isset($USER->format_page_display)) {
            $USER->format_page_display = array();
        }

        return $USER->format_page_display[$courseid] = $pageid;
    }

    /**
     * Function returns the next sortorder value for a group of pages with the same parent
     *
     * @param int $parentid ID of the parent grouping, can be 0
     * @return int
     */
    public static function get_next_sortorder($parentid, $courseid) {
        global $CFG, $DB;

        $sql = "
            SELECT 
                1, 
                MAX(sortorder) + 1 AS nextfree
            FROM 
                {format_page}
            WHERE 
                parent = ? AND 
                courseid = ?
        ";

        $sortorder = $DB->get_record_sql($sql, array($parentid, $courseid));

        if (empty($sortorder->nextfree)) {
            $sortorder->nextfree = 0;
        }

        return $sortorder->nextfree;
    }

    /**
     * inserts a new section and push all upper sections one unit up
     *
     *
     */
    function insert_in_sections($verbose = false) {
        global $DB, $COURSE;

        $allpages = course_page::get_all_pages($COURSE->id,'nested');
        if (empty($allpages)) {
            $newsection = 1;
        } else {
            $previous = null;
            if ($this->parent == 0) {
                $last = array_pop($allpages); // Take last.
                $previous = $last->get_last();
            } else {
                $parent = course_page::get($this->parent);
                $previous = $parent->get_last();
            }

            if ($verbose) {
                echo "Push down sections after $previous->section \n";
            }

            $sections = $DB->get_records_select('course_sections', " course = ? AND section > ? ", array($this->courseid, $previous->section), 'section DESC', 'id,section');
            foreach ($sections as $s) {
                $s->section++;
                $DB->update_record('course_sections', $s);
            }

            if ($verbose) {
                echo "Push down page sections after $previous->section \n";
            }

            $pages = $DB->get_records_select('format_page', " courseid = ? AND section > ? ", array($this->courseid, $previous->section), 'section DESC', 'id,section');
            foreach ($pages as $p) {
                $p->section++;
                $DB->update_record('format_page', $p);
            }

            $newsection = $previous->section + 1;
        }

        return $this->make_section($newsection, null, $verbose);
    }

    /**
     * Get last end leaf of descendants, or self as unique leaf if empty children.
     *
     *
     */
    public function get_last() {
        $children = $this->get_children();
        if (empty($children)) return $this;
        $last = array_pop($children);
        return $last->get_last();
    }

    /**
     * Removes a page from its current location my decrementing
     * the sortorder field of all pages that have the same
     * parent and course as the page.
     *
     * Must be called before the page is actually moved/deleted
     *
     * @param int $pageid ID of the page that is to be removed
     * @return boolean
     */
    public static function remove_from_ordering($pageid) {
        global $CFG, $DB;

        if ($pageinfo = $DB->get_record('format_page', array('id' => $pageid), 'parent, courseid, sortorder')) {

            $sql = "
                UPDATE {format_page}
                    SET sortorder = sortorder - 1
                WHERE 
                    sortorder > ? AND 
                    parent = ? AND 
                    courseid = ?
            ";

            return $DB->execute($sql, array($pageinfo->sortorder, $pageinfo->parent, $pageinfo->courseid));
        }
        return false;
    }

    /**
     * Function checks if sortorder is free in parent scope and pushes page up
     * if required to liberate the slot.
     *
     * @param int $parentid ID of the parent grouping, can be 0
     * @return int
     */
    public static function prepare_page_location($parentid, $fromsortorder, $tosortorder) {
        global $DB, $COURSE;

        if ($allchilds = $DB->get_records('format_page', array('parent' => $parentid, 'courseid' => $COURSE->id), 'sortorder')) {
            $so = 0;
            foreach ($allchilds as $child) {
                if ($child->sortorder < $fromsortorder) {
                    continue;
                }
                if ($child->sortorder == $fromsortorder) {
                    $torelocateid = $child->id;
                }
                if ($child->sortorder > $fromsortorder && $child->sortorder <= $tosortorder) {
                    $neworder = $child->sortorder - 1;
                    $DB->set_field('format_page', 'sortorder', $neworder, array('id' => $child->id));
                }
                $so++;
            }
            $DB->set_field('format_page', 'sortorder', $tosortorder, array('id' => $torelocateid));
        }

        return $tosortorder;
    }

    /**
     * Function checks if sortorder is free in parent scope and pushes page up
     * if required to liberate the slot.
     *
     * @param int $parentid ID of the parent grouping, can be 0
     * @return int
     */
    public static function fix_tree_level($parentid) {
        global $DB, $COURSE;

        if ($allchilds = $DB->get_records('format_page', array('parent' => $parentid, 'courseid' => $COURSE->id), 'sortorder')) {
            $so = 0;
            foreach ($allchilds as $child) {
                $DB->set_field('format_page', 'sortorder', $so, array('id' => $child->id));
                $so++;
            }
        }
    }

    /**
     * Function checks if sortorder is free in parent scope and pushes page up
     * if required to liberate the slot.
     *
     * @return int
     */
    public static function fix_tree() {
        global $DB, $COURSE;

        $oldparent = 0;
        if ($allchilds = $DB->get_records('format_page', array('courseid' => $COURSE->id), 'parent,sortorder')) {
            $so = 0;
            foreach ($allchilds as $child) {
                if ($child->parent != $oldparent) $so = 0;
                $DB->set_field('format_page', 'sortorder', $so, array('id' => $child->id));
                $oldparent = $child->parent;
                $so++;
            }
        }
        page_format_redraw_sections($COURSE);
    }

    /**
     *
     *
     */
    static protected function __get_default_width($region) {
        switch ($region) {
            case 'side-pre':
                return 200;
            case 'main':
                return 600;
            case 'side-post':
                return 200;
        }
    }

    /**
     * Organizes modules array(Mod Name Plural => instances in course)
     * and sorts by the plural name and by the instance name
     *
     * @param object $course Course
     * @param string $field Specify a field from the instance object to return, otherwise whole instance is returned
     * @return array
     **/
    public static function get_modules($field = null) {
        global $COURSE;

        $modinfo  = get_fast_modinfo($COURSE);
        $function = create_function('$a, $b', 'return strnatcmp($a->name, $b->name);');
        $modules  = array();
        if (!empty($modinfo->instances)) {
            foreach ($modinfo->instances as $instances) {
                uasort($instances, $function);

                foreach ($instances as $instance) {
                    if (empty($modules[$instance->modplural])) {
                        $modules[$instance->modplural] = array();
                    }
                    if (is_null($field)) {
                        $modules[$instance->modplural][$instance->id] = $instance;
                    } elseif ($field == 'name+IDNumber') {
                        $modules[$instance->modplural][$instance->id] = $instance->name;
                        if (!empty($instance->idnumber)) {
                            $modules[$instance->modplural][$instance->id] .= ' ('.$instance->idnumber.')';
                        }
                    } else {
                        $modules[$instance->modplural][$instance->id] = $instance->$field;
                    }
                }
            }
        }

        // Sort by key (module name).
        ksort($modules);

        return $modules;
    }

    /**
     *
     *
     *
     */
    function prepare_url_action($action, &$renderer, $course = NULL) {
        global $CFG, $OUTPUT, $COURSE;

        if (empty($action)) {
            return;
        }

        // Load some vars that can be used by the actions.
        if (!isset($course)) {
            $course  = $COURSE;
        }
        $context = context_course::instance($course->id);

        // Include a file from the actions directory
        //$file = "$CFG->dirroot/course/format/$course->format/actions/$action.php";

        // addition: 8 sept 2008 DJD
        // check for local course action file, if not there fall back to default page format file TODO: more seamless way of doing this

        $file = "$CFG->dirroot/course/format/$course->format/actions/$action.php";

        if (!file_exists($file)) {
           $file = "$CFG->dirroot/course/format/page/actions/$action.php";
        }

        if (file_exists($file)) {
            include($file);

            // Above script may perform an exit or a redirect - but usually we want to finish the page.
            echo $OUTPUT->container_end(); // format action container closing
            echo $OUTPUT->footer($course);
            die;
        } else {
            print_error('errorunkownpageaction', 'format_page', '', $action);
        }
    }

    /**
     * This is the master controller of a format page, 
     * Handles format actions, specifically, the parameter action.
     *
     * @param string $action (Optional) The action that should be handled
     * @return void
     */
    public function execute_url_action($action, &$renderer, $course = NULL) {
        global $CFG, $PAGE, $USER, $DB, $COURSE, $OUTPUT;

        $pbm = new page_enabled_block_manager($PAGE);

        if ($action === NULL) {
            // Try to grab from request.
            $action  = optional_param('action', '', PARAM_ALPHA);
        }
    
        // Load some vars that can be used by the actions.
        if ( !isset($course) ) {
            $course  = $COURSE;
        }
        $context = context_course::instance($course->id);
    
        if (!empty($action)) {
            if (!isloggedin()) {
                // If on site page, then require_login may not be called.
                // At this point, we make sure the user is logged in.
                require_login($course->id);
            }
            switch ($action) {
                case 'addmod':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    $cminstance = required_param('instance', PARAM_INT);
                    $pageid = required_param('page', PARAM_INT);

                    // Build a page_block instance and feed it with the course module reference.
                    // Add page item consequently.
                    if ($instance = $pbm->add_block_at_end_of_page_region('page_module', $this->id)) {
                        $pageitem = $DB->get_record('format_page_items', array('blockinstance' => $instance->id));
                        $DB->set_field('format_page_items', 'cmid', $cminstance, array('id' => $pageitem->id));
                    }

                    // Now add cminstance id to configuration.
                    $block = block_instance('page_module', $instance);
                    $block->config->cmid = $cminstance;
                    $block->instance_config_save($block->config);

                    redirect($this->url_build());

                    break;
                case 'showhidemenu':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:managepages', $context);

                    $showhide = required_param('showhide', PARAM_INT);

                    $this->displaymenu = $showhide;
                    $this->save();

                    redirect($this->url_build('action', 'manage'));
                    break;

                case 'templating':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:managepages', $context);

                    $enable = required_param('enable', PARAM_INT);

                    $this->globaltemplate = $enable;
                    $this->save();

                    redirect($this->url_build('action', 'manage'));
                    break;

                case 'setdisplay':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:managepages', $context);

                    $display  = required_param('display', PARAM_INT);

                    $this->display = $display;
                    $this->save();

                    redirect($this->url_build('action', 'manage'));
                    break;

                case 'deletepage':  // Actually delete a page.
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:editpages', $context);

                    $pageid = required_param('page', PARAM_INT);

                    if (!$landingpage = page_delete_page($pageid)) {
                        print_error('couldnotdeletepage', 'format_page');
                    }
                    redirect($this->url_build('action', 'manage', 'page', $landingpage));

                    break;

                case 'copypage':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:managepages', $context);

                    $copy = required_param('copypage', PARAM_INT);
                    $this->copy_page($copy);

                    redirect($this->url_build('action', 'manage'));
                case 'fullcopypage':
                    if (!confirm_sesskey()) {
                        print_error('confirmsesskeybad', 'error');
                    }
                    require_capability('format/page:managepages', $context);

                    $copy = required_param('copypage', PARAM_INT);
                    $this->copy_page($copy, true);

                    redirect($this->url_build('action', 'manage'));
                default:
                    break;
            }
        }
    }

    /**
     * Duplicates a page as a new page. All page items are cloned. full clone copy allows all pageitems that are 
     * linked to activities to be fully cloned and a new independant activity instance be generated  for them.
     * A page ID coming from another course is supported, thus import of a global page template is handled
     * by this function.
     * @param int $pageid a page id, can be in other course (global templates)
     * @param boolean $fullclone If enabled, will clone activities
     * @return int ID of newly created page
     */
    public function copy_page($pageid, $fullclone = false) {
        global $DB, $COURSE, $USER, $CFG;

        $formatpage = $DB->get_record('format_page', array('id' => $pageid));
        $pageitems = $DB->get_records('format_page_items', array('pageid' => $pageid));

        // Discard id for forcing insert.
        unset($formatpage->id);
        if (!preg_match("/\\((\\d+)\\)$/", $formatpage->nameone, $matches)) {
            $formatpage->nameone = $formatpage->nameone.' (1)';
        } else {
            $formatpage->nameone = preg_replace("/\\((\\d+)\\)$/", '('.((int)$matches[1] + 1).')', $formatpage->nameone);
        }

        // Change course to current course.
        $oldcourseid = $formatpage->courseid;
        $formatpage->courseid = $COURSE->id;
        $formatpage->section = 0; // Not yet set in the incoming course.
        $formatpage->globaltemplate = 0; // the coped page MUST NOT be a template anymore.

        // Prepare a new page record.
        $newpageid = $DB->insert_record('format_page', $formatpage);

        $newpage = course_page::get($newpageid);
        $newsectionid = $newpage->insert_in_sections();

        // Copy all page items storing non null blockinstance ids.
        if (!empty($pageitems)) {

            // Clone all page items blocks.
            foreach ($pageitems as $pageitem) {
                unset($pageitem->id);
                $pageitem->pageid = $newpageid;

                $blockrecord = $DB->get_record('block_instances', array('id' => $pageitem->blockinstance));
                $block = $DB->get_record('block', array('name' => $blockrecord->blockname));
                $blockobj = block_instance($block->name, $blockrecord);

                if ($blockobj->instance_allow_multiple()) {
                    // Only multiple blocs can be cloned, or do not add to page.

                    require_once($CFG->libdir.'/ddllib.php');
                    // Now check for a db/install.xml file.
                    $blockdbfile = $CFG->dirroot.'/blocks/'.$block->name.'/db/install.xml';
                    $xmldb_file = new xmldb_file($blockdbfile);
                    $instancedependancies = array();
                    if ($xmldb_file->fileExists()) {
                        $xmldb_file->loadXMLStructure();
                        $structure = $xmldb_file->getStructure();
                        if (!empty($structure->tables)) {
                            // Now clone any blockinstances related identifiable records.
                            // This is achieved on an assumption of a field named instanceid.
                            foreach ($structure->tables as $table) {
                                if (!empty($table->fields)) {
                                    foreach ($table->fields as $field) {
                                        if ($field->name == 'instanceid') {
                                            $instancedependancies[] = $table->name;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $constructor = "block_{$block->name}";
                    $blockobj = new $constructor();
                    if (!empty($instancedependancies)) {
                        if (!method_exists('block_'.$block->name, 'block_clone')) {
                            echo $OUTPUT->notification('Clone error : block has instance dependancies and no block_clone method. Clone may be incomplete.');
                        }
                    }
                    $oldblockid = $blockrecord->id;
                    unset($blockrecord->id);

                    // Ensure parentcontext id is current course.
                    $coursecontext = context_course::instance($COURSE->id);
                    $blockrecord->parentcontextid = $coursecontext->id;

                    // Recode the subpage pattern for the new block jumping to the new page.
                    $blockrecord->subpagepattern = 'page-'.$newpageid;

                    $newblockid = $blockrecord->id = $DB->insert_record('block_instances', $blockrecord);

                    // If block has dependancies clone records.
                    if (!empty($instancedependancies)) {
                        $clonemap = array();
                        foreach ($instancedependancies as $dep) {
                            $deprecords = $DB->get_records($dep, array('instanceid' => $oldblockid));
                            foreach ($deprecords as $deprec) {
                                $olddeprec = $deprec->id;
                                unset($deprec->id);
                                $deprec->instanceid = $newblockid;
                                $clonemap[$dep][$olddeprec] = $DB->insert_record($dep, $deprec);
                            }
                        }
                        $blockobj->block_clone($clonemap);
                    }

                    // If block has block_positions, clone positions after remapping all keys.
                    /*
                     * Note that each block should only have one position record as a single instance
                     * belongs to a single page.
                     */
                    if ($positions = $DB->get_records('block_positions', array('blockinstanceid' => $oldblockid))) {
                        foreach ($positions as $pos) {
                            unset($pos->id);
                            $pos->contextid = $blockrecord->parentcontextid;
                            $pos->blockinstanceid = $newblockid;
                            $pos->subpage = $blockrecord->subpagepattern;
                            $DB->insert_record('block_positions', $pos);
                        }
                    }

                    $pageitem->blockinstance = $newblockid;
                }
                $pageitem->id = $DB->insert_record('format_page_items', $pageitem);

                /*
                 * If full clone, process the eventual course module attached to pageitem
                 * this will create a new activity instance. the related block instance
                 * is a page module instance we need to remap the configuration cmid
                 */
                if (!empty($pageitem->cmid) && $fullclone) {
                    include_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
                    include_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
                    include_once($CFG->libdir . '/filelib.php');

                    $cm = get_coursemodule_from_id('', $pageitem->cmid, $oldcourseid, true, MUST_EXIST);
                    debug_trace("Old module is $cm->id / old section is $cm->section in course $cm->course ");
                    $oldcmcontext  = context_module::instance($cm->id);
                    $oldsection = $DB->get_record('course_sections', array('id' => $cm->section, 'course' => $cm->course));
                    $newsection = $DB->get_record('course_sections', array('id' => $newsectionid));

                    // Only clone if possible to backup.
                    if (plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {

                        // Backup the activity.

                        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
                                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

                        $backupid = $bc->get_backupid();
                        $backupbasepath = $bc->get_plan()->get_basepath();

                        $bc->execute_plan();

                        $bc->destroy();

                        // Restore the backup immediately.

                        $rc = new restore_controller($backupid, $COURSE->id,
                                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

                        // This might not be possible to check here. TODO : Try anyway to do some safety checks and discard failing mods.
                        // We need executing for reaching the AWAITING status.
                        if (!$rc->execute_precheck()) {
                        /*
                            $precheckresults = $rc->get_precheck_results();
                            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                                if (empty($CFG->keeptempdirectoriesonbackup)) {
                                    fulldelete($backupbasepath);
                                }

                                echo $output->header();
                                echo $output->precheck_notices($precheckresults);
                                $url = course_get_url($course, $cm->sectionnum, array('sr' => $sectionreturn));
                                echo $output->continue_button($url);
                                echo $output->footer();
                                die();
                            }
                        */
                        }

                        $rc->execute_plan();

                        $cm = get_coursemodule_from_id('', $pageitem->cmid, $oldcourseid, true, MUST_EXIST);
                        debug_trace("Old module after restore is $cm->id / old section is $cm->section in course $cm->course ");

                        // Now a bit hacky part follows - we try to get the cmid of the newly restored copy of the module.
                        $newcmid = null;
                        $tasks = $rc->get_plan()->get_tasks();
                        foreach ($tasks as $task) {
                            if (is_subclass_of($task, 'restore_activity_task')) {
                                if ($task->get_old_contextid() == $oldcmcontext->id) {
                                    $newcmid = $task->get_moduleid();
                                    break;
                                }
                            }
                        }
                        debug_trace("Got new module as $newcmid ");

                        // If we know the cmid of the new course module, let us move it
                        // right below the original one. otherwise it will stay at the
                        // end of the section;
                        // In page ofrmat, this will not really be visible, unless when
                        /// reverting format to topics or other standard section-wise formats
                        if ($newcmid) {
                            debug_trace("Remapping section $newsection->section for Module $newcmid in course $COURSE->id ");
                            course_add_cm_to_section($COURSE, $newcmid, $newsection->section);
                            // Finally update the page item cm reference, actually cloning the instance.
                            debug_trace("Remapping cmid $newcmid in page_item $pageitem->id ");
                            $DB->set_field('format_page_items', 'cmid', $newcmid, array('id' => $pageitem->id));
                        }

                        $rc->destroy();

                        if (empty($CFG->keeptempdirectoriesonbackup)) {
                            fulldelete($backupbasepath);
                        }

                        // Now finally remap the page_module bloc configuration with new cmid
                        $blockconfig = $DB->get_field('block_instances', 'configdata', array('id' => $newblockid));
                        $configobj = unserialize(base64_decode($blockconfig));
                        $configobj->cmid = $newcmid;
                        $blockconfig = base64_encode(serialize($configobj));
                        $blockconfig = $DB->set_field('block_instances', 'configdata', $blockconfig, array('id' => $newblockid));
                    }
                }
            }
        }
        return $newpageid;
    }

    /**
     * Checks he current candidate displayable page to check if
     * it can be seen by unconnected people.
     */
    static function check_page_public_accessibility($course) {
        global $COURSE;

        $courseid = (is_null($course)) ? $COURSE->id : $course->id;

        $pageid = optional_param('page', '', PARAM_INT);

        // Set course display.
        if ($pageid > 0) {
            // Changing page depending on context.
            $pageid = self::set_current_page($courseid, $pageid);
        } else {
            if ($page = self::get_current_page($courseid)) {
                $displayid = $page->id;
            } else {
                $displayid = 0;
            }
            $pageid = self::set_current_page($courseid, $displayid);
        }

        if (!@$page) {
            $page = self::get($pageid);
        }

        if (!$page) {
            return 0;
        }

        return ($page->display == FORMAT_PAGE_DISP_PUBLIC);
    }
    
    /**
     * Get all pages declared as global templates in all courses.
     *
     *
     */
    public static function get_global_templates() {
        global $DB;

        if (!$templates = $DB->get_records('format_page', array('globaltemplate' => 1), 'courseid, section')) {
            return array();
        }

        // Arrange by course.
        $templatearr = array();
        foreach ($templates as $template){
            $templatearr[$template->courseid][$template->id] = $template;
        }

        $templatemenu = array();
        foreach ($templatearr as $cid => $templatelist) {
            $coursename = $DB->get_field('course', 'fullname', array('id' => $cid));
            foreach ($templatelist as $tid => $template) {
                $templatemenu[$coursename][$tid] = format_string($template->nameone);
            }
        }
        
        return $templatemenu;
    }
}
