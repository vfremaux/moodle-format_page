<?php
/**
 * Format Version
 *
 * @author Jeff Graham
 * @author Valery Fremaux (valery.Fremaux@gmail.com) for Moodle 2
 * @package format_page
 */

$plugin->version  = 2015042700; // Plugin version (update when tables change) if this line is changed ensure that the following line 
                                // in blocks/course_format_page/block_course_format_page.php is changed to reflect the proper version number
                                // set_config('format_page_version', '2007071806');        // trick the page course format into thinking its already installed.
$plugin->requires = 2014042900; // Required Moodle version
$plugin->component = 'format_page';
$plugin->maturity = MATURITY_RC;
$plugin->release = '2.7.0 (Build 2015042700)';
$plugin->dependencies = array('block_page_module' => 2013031400);

