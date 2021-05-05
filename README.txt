This course format is the quite accurate upgrade of the original Flexpage format from MoodleRooms. 

It has been redrawn for Moodle 2 as new track for flexipage was making a technologic break from the
previous version. As we found a lot of good concepts in the former 1.9 version, we wanted to keep them
as content making strategy, integrating lot of nice results of several local developement programs.

We also wanted to get rid of the special "flexpage" format, that could rise an heavy integration work
for people having their own theme, or working with several themes. Now the page format just adds layouts
to existing themes for a smooth and quick integration. 

This version keeps a full compatibility data model with the old MoodleRooms flexpage format.

It adds some significant improvements from the 1.9 version as : 

* Reorganising pages with a treeview
* Overriding a page with an activity
* Making a page conditional to an activity result
* Full public (no login) pages
* Student published pages
* Teachers special reserved pages
* User assigned pages (individualization)
* Group assigned pages (group individualisation)
* Individualisation grid per activity
* page templates
* dual page duplication mode (fullcopy and copy by ref)

Significant differences from Flexipage format
=============================================

* Enhanced ergonomy
* Keeping the activity bag concept
* No proprietary framework that makes development opaque
* Easy integration in existing themes. No separate theme to rework.
* Lot of special features such as page activity oberride, conditional pages, user and group assigned pages
* adding page structure templating

Installation
============

* Drop the "page" folder into the course/format directory
* open the __theme/page pseudo-theme folder and copy the layout/page.php into your working format layouts
* Open the config.php file of the format page, and copy the layout definitions from that file into you current theme configuration
* Copy the content of the __customscripts into a customscripts folder.
* Activate the customscripting : 

$CFG->dirroot = 'your/root';
$CFG->customscripts = $CFG->dirroot.'/customscripts/';

Note customscripting can be located anywhere else than in customscripts, but choose a location being sure it will not collide 
with anything else.

Theme adjustement
=================
the "page.php" page of the pseudo-theme "page" as theme add-on proposes a template
sequence for the layout. You udsually use a theme in which header and footer use
a local strategy that may not look correctly in the page.php layout out from distro.

The easy way to harmonize this is to reedit the page.php layout, and plug in
your header and footer generation sequences from a somewhata generic layout of your current theme.
(f.e. general.php, or for clean bootstrapped based themes, columns1.php.

If you are using several themes in Moodle, you'll have to repeat this adjustment
in all your themes. 

dependencies
================
- blocks/page_module : an essential block to wrap activties in the "page" columns.
- blocks/page_tracker : an accessory block for navigation (not mandatory)
- mod/pagemenu : an activity module for complete internal navigation (not mandatory)
- customscripts/course
- customscripts/user
- Customscripts activated in main config.php as $CFG->dirroot.'/customscripts';
- Theme "page" layouts and config rules for page layout added to your curently used theme (find them in the "__theme/page" pseudo theme directory, not for direct use)

Note about dependencies : none of the page_tracker or the pagemenu module are strictly mandatory, but choosing at least one of them is a good choice. We do not 
rely on the standard moodle navigation block for page formatted internal navigation menus, because of some rules in navigation extension that make some links
not full consistant.

Changelog
================
2017033100 : Add capability format/page:checkdata and button to check data in bottom buttons area.