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
 * Objectivates a tree manipulation library
 * this is a utility class when creating page in the tree structure.
 *
 * @author Valery Fremaux
 * @package format_page
 * @category format
 */
namespace format\page;

use StdClass;

class movingflat_controller {

    protected $data;

    protected $received;

    public function __construct() {
        $this->received = false;
        $this->data = new StdClass;
    }

    /**
     * Receives and controls needed data.
     * @param string $cmd an admitted command or empty string if data is provided 
     * @param array or object $data
     */
    public function receive($cmd, $data = null) {

        if (is_array($data)) {
            $this->data = (object)$data;
            $this->received = true;
            return;
        }

        if (is_object($data)) {
            $this->data = $data;
            $this->received = true;
            return;
        }

        switch($cmd) {
            case 'up':
            case 'down': {
                $this->data->pid = required_param('pid', PARAM_INT);
                $this->data->parent = required_param('parent', PARAM_INT);
                $this->data->sortorder = required_param('sortorder', PARAM_INT);
                break;
            }
            case 'levelup':
            case 'leveldown': {
                $this->data->pid = required_param('pid', PARAM_INT);
                break;
            }
        }

        $this->received = true;
    }

    public function process($cmd) {

        if (!$this->received) {
            throw new coding_exception("Controller did not receive data to process.");
        }

        if ($cmd == 'up') {
            debug_trace("Up", TRACE_DEBUG);
            // Lowers sortorder (up in GUI, down in DB).
            $startpage = course_page::get($this->data->pid);
            tree::move_page_sortorder($this->data->pid, $this->data->parent, $startpage, $startpage->sortorder - 1);

        }
        else if ($cmd == 'down') {
            debug_trace("Down", TRACE_DEBUG);
            // Raises sortorder (down in GUI, up in DB).
            $startpage = course_page::get($this->data->pid);
            tree::move_page_sortorder($this->data->pid, $this->data->parent, $startpage, $startpage->sortorder + 1);
        }

        else if ($cmd == 'levelup') {
            debug_trace("levelUp", TRACE_DEBUG);
            // Necessarily climbs up as sibling next to his parent.
            // So : find the parent object, free a sortorder location next to him, move the current there, and discard the old location
            $current = course_page::get($this->data->pid);
            $oldparent = $current->parent;
            $oldsortorder = $current->sortorder;
            $parent = $current->get_parent();
            tree::insert_page_sortorder($parent->courseid, $parent->id, $parent->sortorder + 1);
            $current->parent = $parent->parent;
            $current->sortorder = $parent->sortorder + 1;
            $current->save();
            debug_trace("From $oldsortorder in $oldparent / To $current->sortorder in $current->parent");
            tree::discard_page_sortorder($parent->courseid, $oldparent, $oldsortorder);
        }
        else if ($cmd == 'leveldown') {
            debug_trace("levelDown", TRACE_DEBUG);
            // Enters as last sibling in the sub.
            // So : get previous sibling, get last insert point in it, free current position and insert 
            $current = course_page::get($this->data->pid);
            $oldparent = $current->parent;
            $oldsortorder = $current->sortorder;
            $previous = $current->get_previous_sibling();
            $nextorder = tree::get_next_page_sortorder($previous->courseid, $previous->id);
            $current->parent = $previous->id;
            $current->sortorder = $nextorder;
            $current->save();
            debug_trace("From $oldsortorder in $oldparent / To $current->sortorder in $current->parent");
            tree::discard_page_sortorder($current->courseid, $oldparent, $oldsortorder);
        }

    }

}
