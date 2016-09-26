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
 * Version details.
 *
 * @package   format_page
 * @category  format
 * @author    Jeff Graham
 * @author    Valery Fremaux (valery.Fremaux@gmail.com) for Moodle 2
 * @copyright 2014 onwards Valery Fremaux (valery.Fremaux@gmail.com) for Moodle 2
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2016071203; // Plugin version
$plugin->requires = 2016051900; // Required Moodle version
$plugin->component = 'format_page';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.1.0 (Build 2016030701)';
$plugin->dependencies = array('block_page_module' => 2013031400);

// Non moodle attribute.
$plugin->codeincrement = '3.1.0001';
