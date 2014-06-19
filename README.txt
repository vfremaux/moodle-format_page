This course format is the quite accurate upgrade of the original Flexpage format from MoodleRooms. 

It has been redrawn for Moodle 2 as new track for flexipage was making a technologic break from the
previous version. As we found a lot of good concepts in the former 1.9 version, we wanted to keep them
as content making strategy, integrating lot of nice results of several local developement programs.

We also wanted to get rid of the special "flexpage" format, that could rise an heavy integration work
for people having their own theme, or working with several themes. Now the page format just adds layouts
to existing themes for a smooth and quick integration. 

thisversion keeps a full compatibility data model with the old MoodleRooms flexpage format.

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

Significant differences from Flexipage format
=============================================

* Enhanced ergonomy
* Keeping the activity bag concept
* No proprietary framework that makes development opaque
* Easy integration in existing themes. No separate theme to rework.
* Lot of special features such as page activity oberride, conditional pages, user and group assigned pages

Installation
============

* Drop the "format_page" folder into the course/format directory
* Rename it as "page"
* Get the theme/page in the __theme folder of the distributrion and copy the layout/page.php into your working format
* Open the config.php file of the format page, and copy the layout definitions into you current theme configuration
* Copy the content of the __customscripts into a customscripts folder.
* Activate the customscripting : 

$CFG->dirroot = 'your/root';
$CFG->customscripts = $CFG->dirroot.'/customscripts/';

Note customscripting can be located anywhere else than in customscripts, but choose a location being sure it will not collide 
with anything else.

dependencies
================
- blocks/page_module : an essential block to wrap activties in the "page" columns.
- blocks/page_tracker : an accessory block for navigation
- mod/pagemenu : an activity module for complete internal navigation
- local/course
- lcoal/user
Customscripts on over $CFG->dirroot/local
Theme "page" layouts and config rules for page layout added to your curently used theme (find them in theme "page", not for direct use)

