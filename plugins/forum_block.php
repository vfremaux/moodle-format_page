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
 * Page Item Definition
 *
 * @author Mark Nielsen
 * @version $Id: __forum.php,v 1.2 2011-04-15 20:14:38 vf Exp $
 * @package format_page
 */

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
function forum_block_set_instance(&$block) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot.'/mod/forum/lib.php');

    $forum = $DB->get_record('forum', array('id' => $block->moduleinstance->id));
    $my_discussions = $DB->count_records('forum_discussions', array('userid' => $USER->id, 'forum' => $block->moduleinstance->id));
    $sql = "
        SELECT
            COUNT(*)
        FROM
            {forum_posts} fp,
            {forum_discussions} fd
        WHERE
            fd.id = fp.discussion AND
            fd.forum = :forum AND
            fp.userid = :userid
    ";
    $my_posts = $DB->count_records_sql($sql, array('userid' => $USER->id, 'forum' => $block->moduleinstance->id));
    $sql = '
        SELECT
            COUNT(DISTINCT(fp.userid))
        FROM
           {forum_posts} fp,
           {forum_discussions} fd
        WHERE
            fd.id = fp.discussion AND
            forum = :forum
    ';
    $distinct_participants = $DB->count_records_sql($sql, array('forum' => $block->moduleinstance->id));

    if ($lastmodifieddisctime = $DB->get_field('forum_discussions', 'MAX(timemodified)', array('forum' => $block->moduleinstance->id))) {
        $lastmodifieddiscid = $DB->get_field('forum_discussions', 'id', array('forum' => $block->moduleinstance->id));
        $lastposttime = $DB->get_field('forum_posts', 'MAX(created)', array('discussion' => $lastmodifieddiscid));
        $select = '
           discussion = ? AND
           created = ?
        ';
        $last_post = $DB->get_record_select('forum_posts', $select, array($lastmodifieddiscid, $lastposttime));
    }

    $block->content->text = '<div class="forum-pageitem block">';
    $block->content->text .= '<div class="header">';
    $forumurl = new moodle_url('/mod/forum/view.php', array('id' => $block->config->cmid));
    $block->content->text .= '<div class="forum-title"><h2><a href="'.$forumurl.'">'.$block->moduleinstance->name.'</a></h2></div>';

    $block->content->text .= '</div>';
    $block->content->text .= '<div class="content">';
    $table = new html_table();
    $table->head = array('', '');
    $table->size = array('50%', '50%');
    $table->data[] = array(get_string('mydiscussions', 'format_page'), $my_discussions);
    $table->data[] = array(get_string('myposts', 'format_page'), $my_posts);
    $table->data[] = array(get_string('participants', 'format_page'), $distinct_participants);
    if (!empty($last_post)) {
        $postdiscussionurl = new moodle_url('/mod/forum/discuss.php', array('d' => $last_post->discussion));
        $table->data[] = array(get_string('lastpost', 'format_page'), '<a href="'.$postdiscussionurl.'">'.format_string(shorten_text($last_post->subject, 40)).'</a>');
    }
    $block->content->text .= html_writer::table($table);
    $block->content->text .= '</div>';
    $block->content->text .= '</div>';

    return true;
}
