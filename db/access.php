<?php
/**
 * Format capabilities
 *
 * @version $Id: access.php,v 1.6 2012-09-14 14:05:06 vf Exp $
 * @package format_page
 **/

$capabilities = array( 

	// controls who can edit pages and access to unpublished pages
    'format/page:editpages' => array (
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

	// controls who has access to published pages (students)
    'format/page:viewpublishedpages' => array (
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),

    'format/page:viewhiddenpages' => array (
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW
        )
    ),

    'format/page:addpages' => array (
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    'format/page:storecurrentpage' => array (
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'format/page:managepages' => array (
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    'format/page:viewpagesettings' => array (
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    // see and accesses to discussion panel
    'format/page:discuss' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )        
    ),

    // can backup a course for publication
    'format/page:quickbackup' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )        
    ),
    
    // can individualize some activities
    'format/page:individualize' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )        
    )
);
