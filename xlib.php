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
 * @copyright 2008 Valery Fremaux (http://www.mylearningfactory.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Cross component library. Called from other components to call the page
 * format facade.
 */
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/course/format/page/classes/page.class.php';

/**
 * prints the current "page" related navigation in foreign
 * situations. (modules or blocks customisation)
 * The module must be customized to print this navigation, and
 * also store the current pageid (coming by an "aspage" parameter)
 * in session.
 */
function page_print_page_format_navigation($cm = null, $backtocourse = false, $return = false) {
    course_page::print_page_format_navigation($cm, $backtocourse, $return);
}

/**
 * @return true if embedded activity as page
 */
function page_save_in_session() {
    return course_page::save_in_session();
}

/**
 * Get all course modules from that page
 */
function page_module_is_visible($cmid, $bypass) {
    return course_page::is_module_visible($cmid, $bypass);
}

