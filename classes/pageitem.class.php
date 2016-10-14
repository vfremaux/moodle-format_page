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
 * @package format_page
 * @category format
 * @author valery fremaux (valery.fremaux@gmail.com)
 * @copyright 2008 Valery Fremaux (Edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Represents a pageitem and all attached methods
 * //TODO : This class not yet used in format_page.
 */

class format_page_item {

    /**
     * The underlying page item record
     *
     */
    protected $formatpageitem;

    /**
     *
     *
     */
    public function __construct($formatpageitemrec) {
        if ($formatpageitemrec) {
            $this->formatpageitem = $formatpageitemrec;
         }
    }

    /**
     * wraps a magic getter to internal fields
     *
     */
    public function __get($fieldname) {
        if (isset($this->formatpage->$fieldname)) {
            return $this->formatpage->$fieldname;
        } else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldname.'in format_page object');
        }
    }

    /**
     * wraps a magic getter to internal fields
     *
     */
    public function __set($fieldname, $value) {
        if (isset($this->formatpageitem->$fieldname)) {
            $magicmethname = 'magic_set_'.$fieldname;
            if (method_exists('format_page_item', $magicmethname)) {
                // Allows override with checked setters if needed.
                $this->$magicmethname($value);
            } else {
                $this->formatpageitem->$fieldname = $value;
            }
        } else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldname.'in format_page_item object');
        }
    }

    /**
     *
     *
     */
    public function save() {
        global $DB;

        if (!is_object($this->formatpageitem)) {
            return;
        }

        if ($this->formatpageitem->id) {
            $DB->update_record('format_page_item', $this->formatpageitem);
        } else {
            // Try be sure we never make duples of any kind.
            if (!$DB->record_exists('format_page_item', array('pageid' => $this->formatpageitem->pageid, 'blockinstance' => $this->formatpageitem->pageid))) {
                $DB->insert_record('format_page_item', $this->formatpageitem);
            }
        }
    }

    /**
     * Local method - set the member formatpage
     *
     * @return void
     */
    public function set_formatpageitem($formatpageitem) {
        $this->formatpageitem = $formatpageitem;
    }

    /**
     * Removes completely a page item from db, and all related block instances or course modules
     *
     */
    public function delete() {
        global $CFG, $DB;

        require_once($CFG->libdir.'/blocklib.php');

        $bm = new block_manager();

        // We leave module cleanup to the manage modules tab... blocks need some help though.
        if (!empty($pageitem->blockinstance)) {

            $blockid = $pageitem->blockinstance;

            require_sesskey();

            $block = $bm->page->blocks->find_instance($blockid);

            if (!$block->user_can_edit() || !$bm->page->user_can_edit_blocks() || !$block->user_can_addto($bm->page)) {
                throw new moodle_exception('nopermissions', '', $bm->page->url->out(), get_string('deleteablock'));
            }

            blocks_delete_instance($block->instance);

        }

        $DB->delete_records('format_page_items', array('id' => $pageitem->id));
        
        page_update_pageitem_sortorder($pageitem->pageid, $pageitem->position, $pageitem->sortorder);
    }
}