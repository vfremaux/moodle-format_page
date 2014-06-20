Scripts in this folder are needed to sidepath some core features at very start of course/view.php
or some user related pages.

These are provided as clone path s in "customscripts" to be used with a customscript configuration : 

$CFG->dirroot = 'your/dir/root';
$CFG->customscripts = $CFG->dirroot.'/customscripts/';

(of course you can also decide you own location for customscripts)

These need to be added in config file BEFORE setup.php is called.

Note that dirroot has disapeared from standard configuration, and is now defined in setup.php. This is 
not a good idea as basing customscript location on dirroot, so we have to preset it here !