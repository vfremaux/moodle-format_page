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
 * Settings for format_page
 *
 * @package    format_page
 * @copyright  2015 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/format/page/lib.php');

if ($ADMIN->fulltree) {

    $key = 'format_page/navgraphics';
    $label = get_string('navgraphics', 'format_page');
    $desc = get_string('navgraphics_desc', 'format_page');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

    $key = 'format_page/pagerendererimages';
    $label = get_string('pagerendererimages', 'format_page');
    $desc = get_string('pagerendererimages_desc', 'format_page');
    $options = array('subdirs' => false, 'maxfiles' => 20);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'pagerendererimages', 0, $options));

    if (format_page_supports_feature('emulate/community') == 'pro') {
        include_once($CFG->dirroot.'/course/format/page/pro/prolib.php');
        $promanager = format_page\pro_manager::instance();
        $promanager->add_settings($ADMIN, $settings);
    } else {
        $label = get_string('plugindist', 'format_page');
        $desc = get_string('plugindist_desc', 'format_page');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }
}
