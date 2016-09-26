<?php
/**
 * Page format language file
 *
 * @author Jeff Graham
 * @contributor Valery Fremaux
 * @version $Id: format_page.php,v 1.9 2012-07-30 15:02:47 vf Exp $
 * @package format_page
 **/
 
global $COURSE;

$string['page:addpages'] = 'Add New Pages';
$string['page:editpages'] = 'Edit Pages and Page Settings';
$string['page:editprotectedpages'] = 'Edit protected pages and Page Settings';
$string['page:discuss'] = 'Discuss and comment on pages';
$string['page:managepages'] = 'Use Manage Pages Settings';
$string['page:viewpagesettings'] = 'View Hide/Show Pages (Modules) Settings';
$string['page:storecurrentpage'] = 'Store current page';
$string['page:viewpublishedpages'] = 'View published pages';
$string['page:viewhiddenpages'] = 'View hidden pages';
$string['page:quickbackup'] = 'Record a quick backup of the course';
$string['page:individualize'] = 'Individualize';

$string['availability'] = 'Availability';
$string['errorpageid'] = 'Invalid page ID';
$string['errorinsertaccessrecord'] = 'Could not insert access record';
$string['erroractionnotpermitted'] = 'You need to be a teacher or admin user to use this page.';
$string['errorpagebadname'] = 'Cannot get the name of the page';
$string['errorunkownpageaction'] = 'Unknown action passed: {$a}';
$string['errorunkownstructuretyp'] = 'Unknown structure type: {$a}';
$string['errorinsertpageitem'] = 'error inserting a page item in page {$a}';
$string['errorblocksintancemodule'] = 'Failed to create page_module block instance';
$string['errorinvalidepageitem'] = 'Invalid page item to configure';
$string['errorflexpageinstall'] = 'Your installation of page format is incomplete. Page format comes with customscripts that need you configure script overrides in your config.php file.';

$string['nopublicpages'] = 'No public pages';
$string['nopublicpages_desc'] = 'If checked, avoids public pages to be accessible for non connected users.';
$string['applytoallpages'] = 'Apply to all pages';
$string['assignusers'] = 'Assign page to users';
$string['assigngroups'] = 'Assign page to groups';
$string['activitylock'] = 'Lock page by an activity module';
$string['eventsinthepast'] = 'Events in the past';
$string['pagemembers'] = 'Page members (can see)';
$string['pagegroups'] = 'Page assigned groups (can see)';
$string['potentialgroups'] = 'Page potential groups';
$string['potentialmembers'] = 'Page potential members';
$string['protected'] = 'Page protection';
$string['activityoverride'] = 'Override page by an activity module';
$string['addall'] = 'Show all';
$string['addtoall'] = 'Show to all';
$string['addblock'] = 'Add Block...';
$string['addexistingmodule'] = 'Add Existing Activity...';
$string['additem'] = 'Add an item';
$string['addmodule'] = 'Add Activity';
$string['addmoduleinstance'] = 'Add Activity';
$string['addpage'] = 'Add Page';
$string['addresources'] = 'Add Resources to Course';
$string['asachildof'] = 'as a child of {$a}';
$string['asamasterpage'] = 'as a master page';
$string['asamasterpageafter'] = 'as a master page after {$a}';
$string['asamasterpageone'] = 'as the first master page';
$string['badmoverequest'] = 'Bad move request. This would create cyclical (infinite loop) hierarchy';
$string['backupsuccess'] = 'The course has been correctly backup';
$string['backupfailure'] = 'An error occured during the backup';
$string['backtocourse'] = 'Back to course';
$string['blockdirectorymissing'] = 'Block directory missing';
$string['bothbuttons'] = 'Both Previous and Next Page';
$string['by'] = ' by ';
$string['childpage'] = 'Child Page';
$string['choosepagetoedit'] = 'Edit Page...';
$string['choosepathtoimport'] = 'Choose path to import files from: ';
$string['addtemplate'] = 'Add this template';
$string['template'] = 'Template';
$string['templating'] = 'Global Template';
$string['enabletemplate'] = 'Enable as global template';
$string['disabletemplate'] = 'Disable as global template';
$string['ornewpagesettings'] = 'Or create a new page with settings';
$string['newpagesettings'] = 'New page settings';
$string['errornameneeded'] = 'The page name must not be empty';
$string['choosetemplate'] = 'Choose a template';
$string['cleanup'] = 'Cleanup';
$string['cleanupadvice'] = 'Beware, you are up to destroy activity instances that you will not be able to recover. Continue ?';
$string['cleanupreport'] = '{$a->name} will be removed';
$string['cleanuptitle'] = 'Activity List Cleanup';
$string['clone'] = 'Clone page and preserve activities';
$string['fullclone'] = 'Clone page and activities (full clone)';
$string['commands'] = 'Actions';
$string['confirmdelete'] = 'Do you really want to delete page: {$a} child pages will be set to this pages parent';
$string['confirmbackup'] = 'Execute backup';
$string['content:loader'] = 'CRS Loaders';
$string['content:repository'] = 'Central Resource Systems';
$string['content:resource'] = 'CRS Content Resources';
$string['couldnotmovepage'] = 'Serious error, could not move page. Failed update on format_page';
$string['couldnotretrieveblockinstance'] = 'Could not retrieve block instance: {$a}';
$string['coursemenu'] = 'Course Menu';
$string['coursecontent'] = 'Course content';
$string['coursenotremapblockinstanceid'] = 'Could not remap block instance: {$a}';
$string['createitem'] = 'Create a new item';
$string['deephidden'] = 'Hidden unless power user';
$string['deletepage'] = 'Delete Page';
$string['disabled'] = 'Disabled';
$string['displaymenu'] = 'Display in Course Menu';
$string['displaytheme'] = 'Display as Top Tab';
$string['discuss'] = 'Discuss';
$string['discussion'] = 'Discussion';
$string['discussioncancelled'] = 'Cancelled';
$string['discussionhascontent'] = 'Discussion has some content';
$string['discussionhasnewcontent'] = 'Discussion has new content you have not seen yet';
$string['editpage'] = 'Edit Page';
$string['editprotected'] = 'This page cannot be modified';
$string['editpagesettings'] = 'Edit Page Settings';
$string['errornoactionpage'] = 'Bad action code : {$a}.';
$string['erroruninitialized'] = 'This course has no page a normal use can see.';
$string['existingmods'] = 'you can reuse an activity module you already created in the course and used in other pages.';
$string['blocks'] = 'Blocks are helpers, tools or side applications that can be usefull to support the course';
$string['filename'] = 'Filename';
$string['filterbytype'] = 'Filter by type : ';
$string['formatpage'] = 'Page format';
$string['globaltemplate'] = 'Is global template';
$string['pluginname'] = 'Page format';
$string['forum:eachuser'] = 'Each person posts one discussion';
$string['forum:general'] = 'General';
$string['forum:news'] = 'News';
$string['forum:qanda'] = 'Q and A';
$string['forum:single'] = 'Single discussion';
$string['forum:social'] = 'Social';
$string['gotorestore'] = 'Go to backup files management';
$string['mydiscussions'] = 'My discussions';
$string['view_forum_block'] = 'Enriched forum block';
$string['myposts'] = 'My posts';
$string['lastpost'] = 'Last post';
$string['participants'] = 'Active participants (one post at least)';
$string['hideresource'] = 'less';
$string['hideshowmodules'] = 'Hide or Show Modules';
$string['hideshowmodulesinstructions'] = 'To hide a module, click the open eye in the Show/Hide column. To show a module, click the closed eye.<p><b>Important:</b> Only Show/Hide modules that are associated with the Essentials Course Menu.';
$string['importadvice'] = 'You may generate a lot activity as a consequence of this action. Continue ?';
$string['importresourcesfromfiles'] = 'Load files as course resources';
$string['importresourcesfromfilestitle'] = 'Load resources from course files';
$string['individualize'] = 'Individualize';
$string['invalidblockid'] = 'Invalid block id: {$a}';
$string['invalidcoursemodule'] = 'Invalid Course module: {$a}';
$string['invalidcoursemodulemod'] = 'Invalid Course Module Mod: {$a}';
$string['invalidpageid'] = 'Invalid pageid: {$a}, or page does not belong to this course';
$string['invalidpageitemid'] = 'Invalid pageitem id: {$a}';
$string['localdiscussionadvice'] = 'Herein discussed topics are local discussions related to this course page. Discussion will NOT be backuped within the course.';
$string['lastmodified'] = 'Last modified on ';
$string['layout'] = 'Page Layout';
$string['locate'] = 'Locate';
$string['nolock'] = 'None';
$string['locking'] = 'Locking activity';
$string['lockingscore'] = 'Unlocking min score';
$string['lockingscoreinf'] = 'Unlocking max score';
$string['manage'] = 'Manage Pages';
$string['managebackups'] = 'Manage Backups';
$string['managemods'] = 'Manage Activities';
$string['masterpage'] = 'Master Page';
$string['menuitem'] = 'Menu Item';
$string['menuitemlocked'] = 'Module Hidden';
$string['menuitemunlocked'] = 'Module Visible';
$string['menupage'] = 'Module Name';
$string['misconfiguredpageitem'] = 'Missconfigured pageitem: {$a}';
$string['missingblockid'] = 'Could not retrieve blockid from block_instance. Bad pageitem->blockinstance: {$a}?';
$string['missingcondition'] = 'A missing condition forbids you accessing to this page';
$string['missingcourseid'] = 'Missing Course ID';
$string['module'] = 'Element';
$string['moveupdown'] = 'Move up/down in column';
$string['movingpage'] = 'Moving Page: {$a->name} (<a href="{$a->url}">Cancel move</a>)';
$string['namepage'] = 'Page';
$string['navigation'] = 'Navigation';
$string['next'] = 'Next&gt;';  // pagename accessible via $a
$string['nextonlybutton'] = 'Next Page Only';
$string['noactivitiesfound'] = 'No activites found';
$string['nochildpages'] = 'No Subpages';
$string['nomasterpageset'] = 'No Master Page Set';
$string['nomodules'] = 'No activities for overriding';
$string['nooverride'] = 'No override - Standard page';
$string['nopages'] = 'There are no pages for this course. Please create a page.';
$string['nopageswithcontent'] = 'No pages with content were found.  Please contact your instructor or course administrator.';
$string['noparents'] = 'There are no potential parent pages';
$string['noprevnextbuttons'] = 'No Links';
$string['otherblocks'] = 'Other blocks';
$string['occurrences'] = 'Used';
$string['override'] = 'Overriding activity';
$string['page'] = 'Page ';
$string['pagemenusettings'] = 'Page Menu related settings';
$string['idnumber'] = 'ID Number';
$string['protectidnumbers'] = 'Protect idnumbers';
$string['protectidnumbers_desc'] = 'If enabled, the page idnumbers cannot be modified. New pages come with empty IDnumbers. this can be usefull when course structures are built automatically by a building script.';
$string['pageformatonfrontpage'] = 'Show page format on front page';
$string['pageformatonfrontpagedesc'] = 'This will enable the page format on the front page.  If this setting is used, then <em>Front Page (frontpage)</em>, <em>Front page items when logged in (frontpageloggedin)</em>, and <em>Include a topic section (numsections)</em> settings will be ignored.';
$string['quickbackup'] = 'Quick backup';
$string['pagename'] = 'Page Name';
$string['pagenameone'] = 'Page Name';
$string['pagenametwo'] = 'Name to Show in Course Menu';
$string['pageoptions'] = 'Page Options';
$string['parent'] = 'Select the Course Menu Parent Page';
$string['pluginname'] = 'Page Format';
$string['preferredcentercolumnwidth'] = 'Center Column Width';
$string['preferredleftcolumnwidth'] = 'Left Column Width';
$string['preferredrightcolumnwidth'] = 'Right Column Width';
$string['previous'] = '&lt;Previous';  // pagename accessible via $a
$string['prevonlybutton'] = 'Previous Page Only';
$string['publish'] = 'Publish';
$string['removeall'] = 'Hide all';
$string['recurse'] = 'Recurse';
$string['removeforall'] = 'Hide for all';
$string['resource:blog'] = 'Blog';
$string['resource:directory'] = 'Directory';
$string['resource:file'] = 'File';
$string['resource:html'] = 'HTML Page';
$string['resource:text'] = 'Text Page';
$string['resource:themed'] = 'Themed Content';
$string['resource:wikipage'] = 'Wiki Page';
$string['resource:url'] = 'URLs';
$string['resourcename'] = 'Resource name';
$string['reorganize'] = 'Reorganize pages';
$string['searchauser'] = 'Search users by pattern';
$string['seealltypes'] = 'See all module types';
$string['setcurrentpage'] = 'Choose the current page:';
$string['settings'] = 'Page Settings';
$string['showbuttons'] = 'Previous &amp; Next Link';
$string['showhide'] = 'Show/Hide';
$string['showresource'] = 'more...';
$string['thispageisnotpublished'] = '{ This page is not published }';
$string['thispagehasuserrestrictions'] = '{ This page has user restrictions }';
$string['thispagehasgrouprestrictions'] = '{ This page has group restrictions }';
$string['thispagehasprofilerestrictions'] = '{ This page has profile restrictions }';
$string['thispagehaseditprotection'] = '{ This page cannot be edited by teachers }';
$string['updatesequencefailed'] = 'Serious error. Update sequence failed, could not set sequence for format_page';
$string['useasdefault'] = 'Use page settings as default';
$string['hidden'] = ' This page is not published ';
$string['protected'] = ' This page is published to teachers';
$string['published'] = ' This page is published to students';
$string['public'] = ' This page is public ';
$string['sectionname'] = ' page ';
$string['cleanup_help'] = '';
$string['unread'] = 'Unread';
$string['participants'] = 'Participants';
$string['regionwidthformat'] = 'Numeric width in pixel or *';
$string['usesindividualization'] = 'Course uses page element individualisation feature';
$string['usespagediscussions'] = 'Course adds discussions to course pages';
$string['pageindividualization'] = 'Elements on page can be assigned specifically to some users';
$string['pagediscussions'] = 'Each page of the course can have a side discussion panel to exchange about the page content.';
$string['hiddenmark'] = '\{Page hidden\}';
$string['protectedmark'] = '{Page hidden to students}';
$string['timerangemark'] = '{Page hidden by timerange from {$a->from} to {$a->to} }';
$string['newpagename'] = 'page-{$a}';
$string['timelock'] = 'Lock page by date';
$string['disabled'] = 'Disabled';
$string['relativeweek'] = 'Relative week for opening';
$string['relativeweekmark'] = '{ Not opened before +{$a} }';
$string['newpagelabel'] = 'New course page';
$string['prefwidth'] = 'Prefered width';
$string['welcome'] = 'Course Welcome';
$string['administration'] = 'Course administration';

$string['pageindividualization_help'] = '
Elements on page can be assigned specifically to some users.
';

$string['reorder_help'] = '
#Reordering pages

Use the tree representation at left side to drag and drop pages to change course structure.<br/><br/>
You can drag a page to the left most edge to raise it to the top course hierarchy level, at the last position.
';

$string['globaltemplate_help'] = '
# Page templates

You can use this page as a global template for all the site. A global template is accessible in all other page formatted courses 
to initialize a new page based on the current structure of this page.
';

$string['prefwidth_help'] = '
You can set the desired width of the column. Values depend on the theme you are using. If you are using a boostrapped theme such as 
Essentials, Bootstrapbase, you should use "span" values between 0 and 12, 
the sum of your width should always be equal to 12. Conversely, use real pixel values.
';

$string['protected_help'] = '
A protected page can only be edited by people having an adequate capability in course. This feature allows locking 
some pages in a course that the editing teacher cannot alter.';

$string['activityoverride_help'] = '
# Overriding the full page content with an activity

You may replace the full content of this page with an activity view screen.
This feature is optimised if your moodle administrator has installed code overrides that will make the forth and back 
navigation consistant in such pages. Not all activities may be correctly handled for the navigation consistancy. See page 
format documentation for more information.
';

$string['pagediscussions_help'] = '
# Side design discussions

Each page of the course can have a side discussion panel to exchange about the page content.
';

$string['blocks_help'] = '
# Add a block

Choose blocks in this menu to be added to this page. A block is a single instance attached to the page.
';

$string['existingmods_help'] = '
# Existing activities

Choosing in this list, you will add to the page an already defined activity in the ocurse. this activity may or may NOT be already published
in the course in another page. You will find the complete list of existing activity with more management controls in the panel "Manage activities"
of the page format edition block.
';
