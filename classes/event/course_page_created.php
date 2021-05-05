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
 * The mod_resource course module viewed event.
 *
 * @package    format_page
 * @copyright  2014 valery.fremaux.gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_page\event;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

/**
 * The format_page event when a page is created.
 *
 * @package    format_page
 * @copyright  2014 Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_page_created extends \core\event\base {

    /**
     * Create instance of event.
     *
     * @param \stdClass $book
     * @param \context_module $context
     * @param \stdClass $chapter
     * @return deliverable_cleared
     */
    public static function create_from_page(\format\page\course_page $page, $context = null) {
        global $COURSE, $DB;

        if (empty($context)) {
            $context = \context_course::instance($COURSE->id);
        }

        $data = array(
            'contextid' => $context->id,
            'objectid' => $page->id,
            'other' => $page->id
        );

        /** @var course_page_viewed $event */
        $event = self::create($data);
        $pagerec = $DB->get_record('format_page', array('id' => $page->id));
        $event->add_record_snapshot('format_page', $pagerec);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'format_page';
    }
}
