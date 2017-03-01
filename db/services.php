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
 * These are webservices related to the page format additional capabilities.
 *
 * @package    format_page
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    'block_get_config' => array(
        'classname' => 'format_page_external',
        'methodname' => 'get_block_config',
        'classpath' => 'course/format/page/externallib.php',
        'description' => 'Let retrieve some block configs identified by block idnumber',
        'type' => 'read',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'block_set_config' => array(
        'classname' => 'format_page_external',
        'methodname' => 'set_block_config',
        'classpath' => 'course/format/page/externallib.php',
        'description' => 'Let get some block configs identified by block idnumber',
        'type' => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'module_get_config' => array(
        'classname' => 'format_page_external',
        'methodname' => 'get_module_config',
        'classpath' => 'course/format/page/externallib.php',
        'description' => 'Let retrieve some modules configs identified by course module idnumber',
        'type' => 'read',
        'capabilities' => 'moodle/course:manageactivities'
    ),

    'module_set_config' => array(
        'classname' => 'format_page_external',
        'methodname' => 'set_module_config',
        'classpath' => 'course/format/page/externallib.php',
        'description' => 'Let change some modules configs identified by course module idnumber',
        'type' => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),

);
