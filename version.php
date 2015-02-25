<?php
/**
 * Format Version
 *
 * @author Jeff Graham
 * @reauthor Valery Fremaux (valery.Fremaux@gmail.com)
 * @package format_page
 **/

$plugin->version  = 2015020400; // Plugin version (update when tables change) if this line is changed ensure that the following line 
                                // in blocks/course_format_page/block_course_format_page.php is changed to reflect the proper version number
                                // set_config('format_page_version', '2007071806');        // trick the page course format into thinking its already installed.
$plugin->requires = 2013111800; // Required Moodle version
$plugin->component = 'format_page';
$plugin->maturity = MATURITY_RC;
$plugin->release = '2.8.0 (Build 2015020400)';
$plugin->dependencies = array('block_page_module' => 2013031400);

