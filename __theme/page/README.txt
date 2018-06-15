This theme is not a real usable one. 

consider it more as a template for integrating the apge strategy into your actual themes.

When adding the page course format to a Moodle using a theme, say T, you just need
and extra layout called : 

layout/page.php

With a very simple construction :

- Elements for your header (without any block regions)
- Main content insert point
- Elements for your footer (without any block regions)

All the following layout with variable with columns will be handled by the course format itself.

For activating the layout, you need define two additional course layout entries in the config.php
file of your theme.

    // Should display the content and basic headers only.
    'format_page' => array(
        'file' => 'page.php',
        'regions' => array('side-pre', 'main', 'side-post'),
        'defaultregion' => 'side-post', // do not position it in center or it will fail when adding activity modules.
        'options' => array('langmenu' => true)
    ),
    'format_page_action' => array(
        'file' => 'page.php',
        'regions' => array(),
        'options' => array('langmenu' => true, 'noblocks' => true),
    ),

ensure you have enabled customscripts in config .php, f.e. : 

$CFG->dirroot  = '/var/www/mymoodleinstall';
$CFG->customscripts  = $CFG->dirroot.'/local';

for customscripting in local, and having copied the local part of the page format distribution for 
overriding key parts of the moodle standard code.