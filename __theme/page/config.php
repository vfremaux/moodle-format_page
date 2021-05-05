<?php
/**
 * Page Theme
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package theme_page
 * @author Mark Nielsen
 * @reauthor Valery Fremaux
 * @version Moodle 2
 */

$THEME->name = 'page';
$THEME->parents = array();
$THEME->enable_dock = false;
$THEME->sheets = array(
    'page',
);
$THEME->layouts = array(
    'format_page' => array(
        'file' => 'page.php',
        'regions' => array('side-pre', 'main', 'side-post'),
        'defaultregion' => 'side-post', // avoid putting in main, or standard course will fail showing the new block menu 
        'options' => array('langmenu' => true)
    ),

    'format_page_action' => array(
        'file' => 'page.php',
        'regions' => array(),
        'options' => array('langmenu' => true, 'noblocks' => true),
    ),
);

$THEME->rendererfactory = 'theme_overridden_renderer_factory';