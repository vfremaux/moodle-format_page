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
 * Format Version
 *
 * @author Jeff Graham
 * @author Valery Fremaux (valery.Fremaux@gmail.com) for Moodle 2
 * @package format_page
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2016030701; // Plugin version (update when tables change) if this line is changed ensure that the following line 
                                // in blocks/course_format_page/block_course_format_page.php is changed to reflect the proper version number
                                // set_config('format_page_version', '2007071806');        // trick the page course format into thinking its already installed.
$plugin->requires = 2015111100; // Required Moodle version
$plugin->component = 'format_page';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.0.0 (Build 2016030701)';
$plugin->dependencies = array('block_page_module' => 2013031400);

