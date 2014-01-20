This course main view overrideis necessary to hook the course to use page formats. 

In case the course is using standard formats, it will be routed back to those 
standard page layouts.

This will need :

$CFG->dirroot = 'your root';
$CFG->customscripts = $CFG->dirroot.'/local'; 

to be defined in config.php.

Note that in Moodle 2, $CFG->dirroot is not any more defined before the call to setup.php where customescripting
takes place. This needs you adding explicitely the $CFG->dirroot definition in config.php.