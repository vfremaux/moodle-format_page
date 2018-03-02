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
 * More internal functions we may need
 * These functions are essentially direct use and data 
 * extration from the underlying DB model, that will not
 * use object instance context to proceed.
 *
 * @author Mark Nielsen
 * @author for Moodle 2 Valery Fremaux
 * @package format_page
 * @category format
 */
defined('MOODLE_INTERNAL') || die();

/**
 * this function handles all of the necessary session hacks needed by the page course format
 *
 * @param int $courseid course id (used to ensure user has proper capabilities)
 * @param string the action the user is performing
 * @uses $SESSION
 * @uses $USER
 */
function page_handle_session_hacks($page, $courseid, $action) {
    global $SESSION, $DB;

    // Load up the context for calling has_capability later.
    $context = context_course::instance($courseid);

    // Handle any actions that need to push a little state data to the session.
    switch ($action) {
        case 'deletemod':
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            if (!isloggedin()) {
                // If on site page, then require_login may not be called.
                // At this point, we make sure the user is logged in.
                require_login($courseid);
            }
            if (has_capability('moodle/course:manageactivities', $context)) {
                // Set some session stuff so we can find our way back to where we were.
                $SESSION->cfp = new stdClass;
                $SESSION->cfp->action = 'finishdeletemod';
                $SESSION->cfp->deletemod = required_param('cmid', PARAM_INT);
                $SESSION->cfp->id = $courseid;
                // Redirect to delete mod.
                $params = array('delete' => $SESSION->cfp->deletemod, 'sesskey' => sesskey());
                redirect(new moodle_url('/course/mod.php', $params));
            }
            break;
    }

    // Handle any cleanup as a result of session being pushed from above block.
    if (isset($SESSION->cfp)) {
        // The user did something we need to clean up after.
        if (!empty($SESSION->cfp->action)) {
            switch ($SESSION->cfp->action) {
                case 'finishdeletemod':
                    if (!isloggedin()) {
                        // If on site page, then require_login may not be called.
                        // At this point, we make sure the user is logged in.
                        require_login($courseid);
                    }
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // Get what we need from session then unset it.
                        $sessioncourseid = $SESSION->cfp->id;
                        $deletecmid = $SESSION->cfp->deletemod;
                        unset($SESSION->cfp);

                        // See if the user deleted a module.
                        if (!$DB->record_exists('course_modules', array('id' => $deletecmid))) {
                            // Looks like the user deleted this so clear out corresponding entries in format_page_items.
                            if ($pageitems = $DB->get_records('format_page_items', array('cmid' => $deletecmid))) {
                                foreach ($pageitems as $pageitem) {
                                    $pageitemobj = new format_page_item($pageitem);
                                    $pageitemobj->delete();
                                }
                            }
                        }
                        if ($courseid == $sessioncourseid && empty($action) && !optional_param('page', 0, PARAM_INT)) {
                            /*
                             * We are in same course and not performing another action or
                             * looking at a specific page, so redirect back to manage modules
                             * for a nice workflow.
                             */
                            $action = 'activities';
                        }
                    }
                    break;
                default:
                    // Doesn't match one of our handled session action hacks.
                    unset($SESSION->cfp);
                    break;
            }
        }
    }

    return $action;
}

/**
 *
 *
 */
function page_get_next_sortorder($courseid, $parent) {
    global $DB;

    $params = array('courseid' => $courseid, 'parent' => $parent);
    $maxsort = 0 + $DB->get_field('format_page', 'MAX(sortorder)', $params);
    return $maxsort + 1;
}

/**
 * @global type $DB
 * @param int $cmid
 * @return stdClass course
 */
function page_get_cm_course($cmid) {
    global $DB;

    $sql = "
        SELECT
            c.*
        FROM
            {course} c
        JOIN
            {course_modules} cm 
        ON
            cm.course = c.id
        WHERE
            cm.id = :cmid
    ";
    $params = array('cmid' => $cmid);
    return $DB->get_record_sql($sql, $params, MUST_EXIST);
}

/**
 * @global type $DB
 * @param int $pageid
 * @return stdClass course
 */
function page_get_page_course($pageid) {
    global $DB;

    $sql = '
        SELECT
            c.*, fp.id AS pageid
        FROM 
            {course} c
        LEFT JOIN
            {format_page} fp ON fp.courseid = c.id
        WHERE
            fp.id = ?
    ';
    return $DB->get_record_sql($sql, array($pageid), MUST_EXIST);
}

/**
 * Get the list of all course modules that HAVE NOT any insertion in the course
 * @global type $DB
 * @param int $courseid
 * @return stdClass[] course modules
 */
function page_get_unused_course_modules($courseid) {
    global $DB;
    $sql = "
        SELECT
            cm.*,
            m.name
        FROM
            {course_modules} cm,
            {modules} m
        WHERE
            cm.module = m.id AND
            cm.course = ? AND
            cm.id NOT IN (
            SELECT DISTINCT
                fpi.cmid
            FROM
                {format_page_items} fpi,
                {format_page} fp
            WHERE
                fp.id = fpi.pageid AND
                fp.courseid = ? AND
                fpi.cmid != 0
        )
    ";
    return $DB->get_records_sql($sql, array($courseid, $courseid));
}

/**
 * @param type $page
 */
function feed_tree_rec($page) {
    $filtered = str_replace('&', '&amp;', $page->nametwo);
    $filtered = str_replace('"', '\'\'', $filtered);

    if (!empty($page->childs)) {
        echo '<item child="1" text="' . $filtered . '" open="1" id="' . $page->id . '" >' . "\n";
        foreach ($page->childs as $child) {
            feed_tree_rec($child);
        }
        echo '</item>' . "\n";
    } else {
        echo '<item child="0" text="' . $filtered . '" open="1" id="' . $page->id . '" />' . "\n";
    }
}

/**
 * @param type $course
 */
function page_xml_tree($course) {
    $allpages = course_page::get_all_pages($course->id, 'nested');

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    echo '<tree id="0">' . "\n";

    if (!empty($allpages)) {
        foreach ($allpages as $page) {
            $filtered = str_replace('&', '&amp;', $page->nametwo);
            $filtered = str_replace('"', '\'\'', $filtered);

            if (!empty($page->childs)) {
                echo '<item child="1" text="' . $filtered . '" open="1" id="' . $page->id . '">' . "\n";
                foreach ($page->childs as $child) {
                    feed_tree_rec($child);
                }
                echo '</item>' . "\n";
            } else {
                echo '<item child="0" text="' . $filtered . '" open="1" id="' . $page->id . '" />' . "\n";
            }
        }
    }
    echo '</tree>';
}

/**
 * @param type $action
 * @param type $iid
 * @param type $oid
 */
function page_send_dhtmlx_answer($action, $iid, $oid) {
    switch ($action) {
        case 'updated':
            $actionstr = 'update';
        default:
            $actionstr = 'updated';
    }

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    echo '<data>';
    echo '<action type="' . $actionstr . '" sid="' . $iid . '" tid="' . $oid . '" />';
    echo '</data>';
}

/**
 * Local methods to assist with generating output
 * that is specific to this page
 */
function page_print_page_row(&$table, $page, &$renderer) {
    global $OUTPUT, $COURSE;

    $context = context_course::instance($COURSE->id);

    $titlestr = get_string('nametwo_desc', 'format_page');

    // Page link/name.
    $pageurl = $page->url_build('page', $page->id);
    $nametwospan = '<br/>&ensp;&ensp;&ensp;&ensp;<span class="format-page-menuname" title="'.$titlestr.'">'.$page->nametwo.'</span>';
    $name = $renderer->pad_string('<a href="'.$pageurl.'">'.format_string($page->nameone).'</a>'.$nametwospan, $page->get_page_depth());

    // Edit, move and delete widgets.
    if (!$page->protected || has_capability('format/page:editprotectedpages', $context)) {
        $actionurl = $page->url_build('page', $page->id, 'action', 'editpage', 'returnaction', 'manage');
        $title = get_string('editpage', 'format_page');
        $pix = $OUTPUT->pix_icon('/t/edit', $title);
        $widgets = ' <a href="'.$actionurl.'">'.$pix.'</a>&nbsp;';

        $actionurl = $page->url_build('action', 'copypage', 'copypage', $page->id);
        $title = get_string('clone', 'format_page');
        $pix = $OUTPUT->pix_icon('/t/copy', $title);
        $widgets .= '&nbsp;<a href="'.$actionurl.'">'.$pix.'</a>&nbsp;';

        $actionurl = $page->url_build('action', 'fullcopypage', 'copypage', $page->id);
        $title = get_string('fullclone', 'format_page');
        $pix = $OUTPUT->pix_icon('fullcopy', $title, 'format_page');
        $widgets .= '&nbsp;<a href="'.$actionurl. '">'.$pix.'</a>&nbsp;';

        $actionurl = $page->url_build('action', 'confirmdelete', 'page', $page->id);
        $pix = $OUTPUT->pix_icon('/t/delete', get_string('delete'));
        $title = get_string('deletepage', 'format_page');
        $widgets .= '&nbsp;<a href="'.$actionurl.'">'.$pix.'</a>';

        // If we have some users.
        if ($users = get_enrolled_users(context_course::instance($COURSE->id))) {
            $dimmedclass = (!$page->has_user_accesses()) ? 'dimmed' : '';
            $title = get_string('assignusers', 'format_page');
            $pix = $OUTPUT->pix_icon('/i/user', $title, 'core', array('class' => $dimmedclass));
            $actionurl = $page->url_build('action', 'assignusers', 'page', $page->id);
            $widgets .= '&nbsp;<a href="'.$actionurl.'">'.$pix.'</a>';
        }

        $menu = page_manage_showhide_menu($page);
        $template = page_manage_switchtemplate_menu($page);
        $publish = page_manage_display_menu($page);
    } else {
        $widgets = '';
        $menu = '';
        $template = '';
        $publish = '';
    }

    $table->data[] = array($name, $widgets, $menu, $template, $publish);

    $childs = $page->childs;
    if (!empty($childs)) {
        foreach ($childs as $child) {
            page_print_page_row($table, $child, $renderer);
        }
    }
}

/**
 * This function displays the hide/show icon & link page display settings
 *
 * @param object $page Page to show the widget for
 * @param int $type a display type to show
 * @uses $CFG
 */
function page_manage_showhide_menu($page) {
    global $OUTPUT;

    $params = array('id' => $page->courseid,
        'page' => $page->id,
        'action' => 'showhidemenu',
        'sesskey' => sesskey());

    if ($page->displaymenu) {
        $params['showhide'] = 0;
        $str = 'hide';
    } else {
        $params['showhide'] = 1;
        $str = 'show';
    }
    $url = new moodle_url('/course/format/page/action.php', $params);

    $pix = $OUTPUT->pix_icon("i/$str", get_string($str));
    $return = '<a href="'.$url.'">'.$pix.'</a>';
    return $return;
}

/**
 * @global type $OUTPUT
 * @global type $COURSE
 * @param type $page
 * @return type
 */
function page_manage_display_menu($page) {
    global $OUTPUT, $COURSE;

    $displayclasses[FORMAT_PAGE_DISP_HIDDEN] = 'format-page-urlselect-hidden';
    $displayclasses[FORMAT_PAGE_DISP_PROTECTED] = 'format-page-urlselect-protected';
    $displayclasses[FORMAT_PAGE_DISP_PUBLISHED] = 'format-page-urlselect-published';
    $displayclasses[FORMAT_PAGE_DISP_PUBLIC] = 'format-page-urlselect-public';
    $displayclasses[FORMAT_PAGE_DISP_DEEPHIDDEN] = 'format-page-urlselect-private';

    $params = array('id' => $COURSE->id,
                    'page' => $page->id,
                    'action' => 'setdisplay',
                    'sesskey' => sesskey());
    $url = new moodle_url('/course/format/page/action.php', $params);

    $optionurls = array();
    $optionurls[$url.'&display='.FORMAT_PAGE_DISP_HIDDEN] = get_string('hidden', 'format_page');
    $optionurls[$url.'&display='.FORMAT_PAGE_DISP_PROTECTED] = get_string('protected', 'format_page');
    $optionurls[$url.'&display='.FORMAT_PAGE_DISP_PUBLISHED] = get_string('published', 'format_page');
    $optionurls[$url.'&display='.FORMAT_PAGE_DISP_PUBLIC] = get_string('public', 'format_page');
    $optionurls[$url.'&display='.FORMAT_PAGE_DISP_DEEPHIDDEN] = get_string('deephidden', 'format_page');

    $selected = $url.'&display='.$page->display;

    $select = new url_select($optionurls, $selected, array());
    $select->class = $displayclasses[$page->display];
    return $OUTPUT->render($select);
}

/**
 * @global type $OUTPUT
 * @param type $page
 * @return string
 */
function page_manage_switchtemplate_menu($page) {
    global $OUTPUT;

    $params = array('id' => $page->courseid,
        'page' => $page->id,
        'action' => 'templating',
        'sesskey' => sesskey());
    if ($page->globaltemplate) {
        $params['enable'] = 0;
        $str = 'disabletemplate';
        $pix = 'activetemplate';
    } else {
        $params['enable'] = 1;
        $str = 'enabletemplate';
        $pix = 'inactivetemplate';
    }
    $url = new moodle_url('/course/format/page/action.php', $params);

    $pix = $OUTPUT->pix_icon($pix, get_string($str, 'format_page'), 'format_page');
    $return = '<a href="'.$url.'">'.$pix.'</a>';
    return $return;
}

/**
 * Prints a modtype selector for individualization checkboard
 */
function page_print_moduletype_filter($modtype, $mods, $url) {
    global $DB;

    if (empty($mods)) {
        return;
    }
    // Start counting how many instances in which type.
    $modcount = array();
    $modnames = array();
    foreach ($mods as $mod) {
        if (!$DB->get_records_select('format_page_items', " cmid = $mod->id")) {
            // Forget modules who are not viewed in page.
            continue;
        }
        isset($modcount[$mod->module]) ? $modcount[$mod->module] ++ : $modcount[$mod->module] = 1;
        $modnames[$mod->module] = $mod->modfullname;
    }
    foreach (array_keys($modcount) as $modid) {
        $modtypes[$modid] = $modnames[$modid].' ('.$modcount[$modid].')';
    }
    echo '<form name="moduletypechooser" class="moduletypechooser" action="' . $url . '" method="post">';
    echo get_string('filterbytype', 'format_page');
    $nochoice = array('' => get_string('seealltypes', 'format_page'));
    $attrs = array('onchange' => 'document.forms[\'moduletypechooser\'].submit();');
    echo html_writer::select($modtypes, 'modtype', $modtype, $nochoice, $attrs);
    echo '</form>';
}

/**
 * prints a small user search engine form
 *
 */
function page_print_user_filter($url) {

    // Start counting how many instances in which type.
    $usersearch = optional_param('usersearch', '', PARAM_TEXT);
    echo '<form name="usersearchform" class="usersearchform" action="'.$url.'" method="post">';
    $usersearchstr = get_string('searchauser', 'format_page');
    echo '<input type="text" name="usersearch" value="'.$usersearch.'" />';
    echo '<input type="submit" name="go_btn" value="'.$usersearchstr.'" />';
    echo '</form>';
}

/**
 * @global type $CFG
 * @param type $direction
 * @param type $userid
 * @param type $cmid
 * @return int
 */
function page_get_pageitem_changetime($direction, $userid, $cmid) {
    global $CFG;

    if (empty($CFG->individualizewithtimes)) {
        return 0;
    }

    $datekey = $direction."_date_{$cmid}_{$userid}";
    $hourkey = $direction."_hour_{$cmid}_{$userid}";
    $minkey = $direction."_min_{$cmid}_{$userid}";
    $enablekey = $direction."_enable_{$cmid}_{$userid}";
    $enabling = optional_param($enablekey, false, PARAM_INT);
    if (empty($enabling)) {
        return 0;
    } else {
        $date = optional_param($datekey, false, PARAM_TEXT);
        if (empty($date)) {
            $date = date("Y-m-d", time());
        }
        list($year, $month, $day) = explode('-', $date);
        $hour = optional_param($hourkey, 0, PARAM_INT);
        $min = optional_param($minkey, 0, PARAM_INT);
        $time = mktime($hour, $min, 0, $month, $day, $year);
        return $time;
    }
}

/**
 * @global type $OUTPUT
 * @param type $course
 * @param type $itemaccess
 * @param type $absolutemaxtime
 */
function page_print_timebar($course, $itemaccess, $absolutemaxtime) {
    global $OUTPUT;

    $now = time();
    $trackwidth = 200;
    $track[$course->startdate] = 'undefined';
    $track[$now] = ($itemaccess->hidden) ? 'hidden' : 'visible';
    if ($itemaccess->revealtime) {
        $track[$itemaccess->revealtime] = 'visible';
    }
    if ($itemaccess->hidetime) {
        $track[$itemaccess->hidetime] = 'hidden';
    }
    $track[$absolutemaxtime] = 'undefined'; // Value is not really usefull on last record.
    $grratio = $trackwidth / ($absolutemaxtime - $course->startdate);
    ksort($track);
    $lastdate = 0;
    foreach ($track as $tracktime => $trackstate) {
        if (empty($lastdate)) {
            $laststate = $trackstate;
            $lastdate = $tracktime;
        } else {
            $trackqsegmentwidth = $grratio * ($tracktime - $lastdate);
            $img = $OUTPUT->image_url('individualization/' . $laststate, 'format_page');
            if ($lastdate == $now) {
                $eventimg = $OUTPUT->image_url('individualization/now', 'format_page');
            } else {
                $eventimg = $OUTPUT->image_url('individualization/event', 'format_page');
            }
            $eventlabel = userdate($lastdate);
            echo '<img src="' . $eventimg . '" title="' . $eventlabel . '" height="16" />';
            echo '<img src="' . $img . '" width="' . $trackqsegmentwidth . '" height="16" />';
            $lastdate = $tracktime;
            $laststate = $trackstate;
        }
    }
    $trackqsegmentwidth = $grratio * ($tracktime - $lastdate);
    $img = $OUTPUT->image_url('individualization/' . $laststate, 'format_page');
    if ($lastdate == $now) {
        $eventimg = $OUTPUT->image_url('individualization/now', 'fotmat_page');
    } else {
        $eventimg = $OUTPUT->image_url('individualization/event', 'format_page');
    }
    echo '<img src="'.$img.'" width="'.$trackqsegmentwidth.'" height="16" />';
    $eventlabel = userdate($lastdate);
    echo '<img src="'.$eventimg.'" title="'.$eventlabel.'" height="16" />';
}

/**
 * @global type $DB
 * @param type $course
 * @return type
 */
function page_get_max_access_event_time($course) {
    global $DB;

    $maxreveal = $DB->get_field_select('block_page_module_access', 'max(revealtime)', " course = $course->id ");
    $maxhide = $DB->get_field_select('block_page_module_access', 'max(hidetime)', " course = $course->id ");
    $maxtime = max(time(), $maxreveal, $maxhide);
    return ($maxtime + 10 * DAYSECS);
}

/**
 * @global type $DB
 * @param type $mods
 * @return array
 */
function page_sort_modules($mods) {
    global $DB;
    $sortedmods = array();

    foreach ($mods as $mod) {
        if (!$modinstance = $DB->get_record($mod->modname, array('id' => $mod->instance))) {
            continue;
        }
        $modinstance->modname = $mod->modname;
        $modinstance->cmid = $mod->id;

        if (isset($modinstance->type)) {
            if (empty($sortedmods[$mod->modname.':'.$modinstance->type])) {
                $sortedmods[$mod->modname.':'.$modinstance->type] = array();
            }
            $sortedmods[$mod->modname.':'.$modinstance->type][$mod->id] = $mod;
        } else {
            if (empty($sortedmods[$mod->modname])) {
                $sortedmods[$mod->modname] = array();
            }
            $sortedmods[$mod->modname][$mod->id] = $mod;
        }
    }

    ksort($sortedmods);
    return $sortedmods;
}

/**
 * @global type $DB
 * @param type $course
 * @return type
 */
function page_get_all_course_modules_and_sections($course) {
    global $DB;

    $sql = "
        SELECT
            cm.id,
            cs.id as sectionid,
            cs.name as sectioname
        FROM
            {course_modules} cm
        LEFT JOIN
            {course_sections} cs
        ON
            cs.id = cm.section
        WHERE
            cm.course = ? AND
            cs.course = ?
        UNION
        SELECT
            cm.id,
            cs.id as sectionid,
            cs.name as sectioname
        FROM
            {course_modules} cm
        RIGHT JOIN
            {course_sections} cs
        ON
            cs.id = cm.section
        WHERE
            cm.course = ? AND
            cs.course = ?
    ";

    return $DB->get_records_sql($sql, array($course->id, $course->id, $course->id, $course->id));
}

/**
 * @global type $DB
 * @param type $course
 * @return type
 */
function page_get_all_course_pages_and_sections($course) {
    global $DB;

    $sql = "
       SELECT
        fp.id,
        fp.nameone as pagename,
        cs.id as sectionid,
        cs.name as sectioname
    FROM
        {format_page} fp
    LEFT JOIN
        {course_sections} cs
    ON
        cs.section = fp.section
    WHERE
        fp.courseid = ? AND
        cs.course = ?
    UNION
    SELECT
        fp.id,
        fp.nameone as pagename,
        cs.id as sectionid,
        cs.name as sectioname
    FROM
        {format_page} fp
    RIGHT JOIN
        {course_sections} cs
    ON
        cs.section = fp.section
    WHERE
        fp.courseid = ? AND
        cs.course = ?
    ";
    return $DB->get_records_sql($sql, array($course->id, $course->id, $course->id, $course->id));
}

/**
 * @global type $DB
 * @param type $course
 * @return type
 */
function page_get_all_course_items_and_modules($course) {
    global $DB;

    $sql = "
        SELECT
            fpi.id,
            cm.id as modid
        FROM
            {format_page} fp,
            {format_page_items} fpi
        LEFT JOIN
            {course_modules} cm
        ON
            fpi.cmid != 0 AND
            cm.id = fpi.cmid
        WHERE
            fp.courseid = ? AND
            cm.course = ? AND
            fpi.pageid = fp.id
        UNION
        SELECT
            fpi.id,
            cm.id as modid
        FROM
            {format_page} fp,
            {format_page_items} fpi
        RIGHT JOIN
            {course_modules} cm
        ON
            fpi.cmid != 0 AND
            cm.id = fpi.cmid
        WHERE
            fp.courseid = ? AND
            cm.course = ? AND
            fpi.pageid = fp.id
    ";
    return $DB->get_records_sql($sql, array($course->id, $course->id, $course->id, $course->id));
}

/**
 * @global type $DB
 * @param type $course
 * @return type
 */
function page_get_all_course_items_and_blocks($course) {
    global $DB;

    $sql = "
        SELECT
            fpi.id,
            fpi.cmid,
            bi.id as blockid,
            bi.blockname
        FROM
            {format_page} fp,
            {format_page_items} fpi
        LEFT JOIN
            {block_instances} bi
        ON
            bi.blockname != 'page_module' AND
            bi.id = fpi.blockinstance
        LEFT JOIN
            {context} ctx
        ON
            bi.parentcontextid = ctx.id
        WHERE
            fp.courseid = ? AND
            ctx.instanceid = ? AND
            fpi.pageid = fp.id AND
            ctx.contextlevel = 50 AND
            ctx.instanceid = fp.courseid
        UNION
        SELECT
            fpi.id,
            fpi.cmid,
            bi.id as blockid,
            bi.blockname
        FROM
            {format_page} fp,
            {format_page_items} fpi
        LEFT JOIN
            {block_instances} bi
        ON
            bi.blockname != 'page_module' AND
            bi.id = fpi.blockinstance
        RIGHT JOIN
            {context} ctx
        ON
            bi.parentcontextid = ctx.id
        WHERE
            fp.courseid = ? AND
            ctx.instanceid = ? AND
            fpi.pageid = fp.id AND
            ctx.contextlevel = 50 AND
            ctx.instanceid = fp.courseid
    ";
    return $DB->get_records_sql($sql, array($course->id, $course->id, $course->id, $course->id));
}

/**
 * Updates or creates a new page.
 * @param type $data
 * @param type $pageid
 * @param type $defaultpage
 * @param course_page $page
 */
function page_edit_page($data, $pageid, $defaultpage, $page = null) {
    global $DB, $COURSE;

    if (!empty($data->addtemplate)) {

        // New page may not be in turn a global template.
        $overrides = array('globaltemplate' => 0, 'parent' => $data->templateinparent);

        $templatepage = course_page::get($data->usetemplate);
        $newpageid = $templatepage->copy_page($data->usetemplate, true, $overrides);

        // Update the changed params.
        $pagerec = $DB->get_record('format_page', array('id' => $newpageid));
        $pagerec->nameone = $data->extnameone;
        $pagerec->nametwo = $data->extnametwo;
        if ($data->parent) {
            $pagerec->parent = $data->parent;
        } else {
            $pagerec->parent = 0;
        }
        $DB->update_record('format_page', $pagerec);

        rebuild_course_cache($COURSE->id);

        if (empty($defaultpage)) {
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
        }
        redirect($defaultpage->url_build('page', $newpageid));
    }

    // Save/update routine.
    $pagerec = new StdClass;
    $pagerec->nameone = $data->nameone;
    $pagerec->nametwo = $data->nametwo;
    $pagerec->courseid = $COURSE->id;
    $pagerec->display = 0 + @$data->display;
    $pagerec->displaymenu = 0 + @$data->displaymenu;

    if (format_page_is_bootstrapped()) {
        $pagerec->bsprefleftwidth = (@$data->prefleftwidth == '*') ? '*' : '' . @$data->prefleftwidth;
        $pagerec->bsprefcenterwidth = (@$data->prefcenterwidth == '*') ? '*' : '' . @$data->prefcenterwidth;
        $pagerec->bsprefrightwidth = (@$data->prefrightwidth == '*') ? '*' : '' . @$data->prefrightwidth;
    } else {
        $pagerec->prefleftwidth = (@$data->prefleftwidth == '*') ? '*' : '' . @$data->prefleftwidth;
        $pagerec->prefcenterwidth = (@$data->prefcenterwidth == '*') ? '*' : '' . @$data->prefcenterwidth;
        $pagerec->prefrightwidth = (@$data->prefrightwidth == '*') ? '*' : '' . @$data->prefrightwidth;
    }

    $pagerec->template = $data->template;
    $pagerec->globaltemplate = $data->globaltemplate;
    $pagerec->showbuttons = $data->showbuttons;
    $pagerec->parent = $data->parent;
    $pagerec->cmid = 0 + @$data->cmid; // There are no mdules in course.
    $pagerec->lockingcmid = 0 + @$data->lockingcmid;
    $pagerec->lockingscore = 0 + @$data->lockingscore;
    $pagerec->lockingscoreinf = 0 + @$data->lockingscoreinf;
    $pagerec->datefrom = 0 + @$data->datefrom;
    $pagerec->dateto = 0 + @$data->dateto;
    $pagerec->relativeweek = 0 + @$data->relativeweek;

    // There can only be one!

    if ($pagerec->template) {
        // Only one template page allowed.
        $DB->set_field('format_page', 'template', 0, array('courseid' => $pagerec->courseid));
    }

    if ($pageid) {

        $old = course_page::get($data->page);
        $hasmoved = ($old->parent != $pagerec->parent);
        $pagerec->section = $old->section;

        // Updating existing record.
        $pagerec->id = $data->page;

        if ($hasmoved) {
            // Moving - re-assign sortorder.
            $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);

            // Remove from old parent location.
            course_page::remove_from_ordering($pagerec->id);
        }

        $page->set_formatpage($pagerec);

        $page->save(); // Save once.
        if ($hasmoved) {
            $page->delete_section();
            $page->insert_in_sections();
            $page->save();
        } else {
            $page->update_section();
        }
    } else {
        // Creating new.
        $pagerec->sortorder = course_page::get_next_sortorder($pagerec->parent, $pagerec->courseid);
        $pagerec->section = 0;
        $page = new course_page($pagerec);
        $page->insert_in_sections();
        $page->save();
    }

    // Apply some settings to all pages.
    if (!empty($data->displayapplytoall)) {
        if (!empty($data->display)) {
            $DB->set_field('format_page', 'display', $data->display, array('courseid' => $COURSE->id));
        }
    }

    if (!empty($data->displaymenuapplytoall)) {
        if (!empty($data->displaymenu)) {
            $DB->set_field('format_page', 'displaymenu', $data->displaymenu, array('courseid' => $COURSE->id));
        }
    }

    if (!empty($data->prefleftwidthapplytoall)) {
        if (!empty($data->prefleftwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefleftwidth', $data->prefleftwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefleftwidth', $data->prefleftwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->prefcenterwidthapplytoall)) {
        if (!empty($data->prefcenterwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefcenterwidth', $data->prefcenterwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefcenterwidth', $data->prefcenterwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->prefrightwidthapplytoall)) {
        if (!empty($data->prefrightwidth)) {
            if (format_page_is_bootstrapped()) {
                $DB->set_field('format_page', 'bsprefrightwidth', $data->prefrightwidth, array('courseid' => $COURSE->id));
            } else {
                $DB->set_field('format_page', 'prefrightwidth', $data->prefrightwidth, array('courseid' => $COURSE->id));
            }
        }
    }

    if (!empty($data->showbuttonsapplytoall)) {
        $DB->set_field('format_page', 'showbuttons', $data->showbuttons, array('courseid' => $COURSE->id));
    }

    return $page;
}

/**
 * @global type $DB
 * @param type $pageid
 * @param type $position
 * @param type $sortorder
 */
function page_update_pageitem_sortorder($pageid, $position, $sortorder) {
    global $DB;

    $sql = "
        UPDATE
            {format_page_items}
        SET
            sortorder = sortorder - 1
        WHERE
            pageid = ? AND 
            position = ? AND 
            sortorder > ?
    ";
    $DB->execute($sql, array($pageid, $position, $sortorder));
}

/**
 * @global type $DB
 * @param type $courseid
 * @param type $parent
 * @param type $sortorder
 */
function page_update_page_sortorder($courseid, $parent, $sortorder) {
    global $DB;

    $sql = "
        UPDATE
            {format_page}
        SET
            sortorder = sortorder - 1
        WHERE
            courseid = ? AND
            parent = ? AND
            sortorder > ?
    ";
    $DB->execute($sql, array($courseid,$parent, $sortorder));
}
