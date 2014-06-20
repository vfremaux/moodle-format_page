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
 * Page format event handler definition.
 *
 * @package   format_page
 * @copyright 2010 Petr Skoda  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* List of handlers */
$handlers = array (
    'mod_created' => array (
        'handlerfile'      => '/course/format/page/lib.php',
        'handlerfunction'  => 'format_page_mod_created_eventhandler',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'mod_deleted' => array (
        'handlerfile'      => '/course/format/page/lib.php',
        'handlerfunction'  => 'format_page_mod_deleted_eventhandler',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'course_deleted' => array (
        'handlerfile'      => '/course/format/page/lib.php',
        'handlerfunction'  => 'format_page_course_deleted_eventhandler',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);
