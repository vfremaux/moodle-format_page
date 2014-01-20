<?php
/**
 * Format Upgrade Path
 *
 * @version $Id: upgrade.php,v 1.3 2012-07-10 12:14:56 vf Exp $
 * @package format_page
 **/

function xmldb_format_page_upgrade($oldversion=0) {

    global $CFG, $DB;

	$dbman = $DB->get_manager();

    include_once($CFG->dirroot.'/course/format/page/lib.php');

    $result = true;

    include_once($CFG->dirroot.'/course/format/page/lib.php');

    $result = true;
    if ($result && $oldversion < 2007041202) {

        /// Define field id to be added to block_course_menu
        $table = new xmldb_table('format_page');
        
        /// Add field showbuttons
        $field = new xmldb_field('showbuttons');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, 0, 'template');
        $dbman->add_field($table, $field);

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007041202, 'format', 'page');
    }
    
    if ($result && $oldversion < 2007042500) {
        // update showbuttons settings to allow for indedependent bitwise previous & next
        if(defined('BUTTON_BOTH')) {
            $DB->set_field('format_page', 'showbuttons', BUTTON_BOTH, array('showbuttons' => 1));
        } else {
            $result = false;
            echo $OUTPUT->notification('BUTTON_BOTH constant not set', 'notifyfailure');
        }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007042500, 'format', 'page');
    }
    
    if ($result && $oldversion < 2007042503) {
         /// Define index index (not unique) to be added to format_page
        $table = new xmldb_table('format_page');
        $index = new xmldb_index('parentpageindex');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('parent'));
        
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('sortorderpageindex');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('sortorder'));
        
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // now add indexes for format_page_items tables
        $table = new xmldb_table('format_page_items');
        $index = new xmldb_index('format_page_items_sortorder_index');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('sortorder'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('format_page_items_pageid_index');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('pageid'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007042503, 'format', 'page');
    }
    
    if ($result && $oldversion < 2007071800) {
        $validcourses = $DB->get_records_menu('course', array(), '', 'id, shortname');
        if (!empty($validcourses)) {
            $keys = array_keys($validcourses);
            
            $invalidpages = $DB->get_records_select_menu('format_page', 'courseid NOT IN('.implode(', ', $keys).')', array(), '','id, nameone');
            if (!empty($invalidpages)) {
                $pagekeys = array_keys($invalidpages);
                $DB->delete_records_select('format_page_items', 'pageid IN ('.implode(', ', $pagekeys).')', array());
                $DB->delete_records_select('format_page', 'id IN ('.implode(', ', $pagekeys).')', array());
            }
        } else {
            $DB->delete_records('format_page');
            $DB->delete_records('format_page_items');
        }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071800, 'format', 'page');
    }

    if ($result && $oldversion < 2007071801) {

    /// Define field width to be dropped from format_page_items
        $table = new xmldb_table('format_page_items');
        $field = new xmldb_field('width');

    /// Launch drop field width
        $dbman->drop_field($table, $field);

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071801, 'format', 'page');
    }

    if ($result && $oldversion < 2007071802) {
    /// Changing logic for sortorder field to more closely resemble block weight

        // This could be huge, do not output everything
        $olddebug  = $db->debug;
        $db->debug = false;

        // Setup some values
        $result = true;
        $i      = 0;

        if ($rs = $DB->get_recordset('format_page', array(), '', 'id')) {
            if ($rs) {
                echo 'Processing page item sortorder field....';

                while ($rs->valid()) {
                	$page = $rs->current();
                    if ($pageitems = $DB->get_records('format_page_items', array('pageid' => $page->id), 'sortorder', 'id, position')) {
                        // Organize by position
                        $organized = array('l' => array(), 'c' => array(), 'r' => array());
                        foreach ($pageitems as $pageitem) {
                            $organized[$pageitem->position][] = $pageitem->id;
                        }
                        // Now - reset sortorder value
                        foreach ($organized as $position => $pageitemids) {
                            $sortorder = 0;
                            foreach ($pageitemids as $pageitemid) {
                                $DB->set_field('format_page_items', 'sortorder', $sortorder, array('id'=> $pageitemid));
                                $sortorder++;
                            }
                        }
                    }
                    if ($i % 50 == 0) {
                        echo '.';
                        flush();
                    }
                    $i++;
                    $rs->next();
                }
                if ($result) {
                    echo $OUTPUT->notification('SUCCESSFULLY fixed page item sort order field', 'notifysuccess');
                } else {
                    echo $OUTPUT->notification('FAILED!  An error occured during upgrade');
                }
            }
            $rs->close();
        }
        // Restore
        $db->debug = $olddebug;

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071802, 'format', 'page');
    }

    if ($result && $oldversion < 2007071803) {
        // This could be huge, do not output everything
        $olddebug  = $CFG->debug;
        $CFG->debug = false;

        $result = true;

        // Make sure all block weights are set properly (before this was never really managed properly)
        if ($courses = $DB->get_records('course', array('format' => 'page'), '', 'id')) {
            echo 'Fixing block weights in courses with format = \'page\'....';

            $i = 0;
            foreach ($courses as $course) {
                course_page::fix_block_weights($course->id);
                if ($i % 5 == 0) {
                    echo '.';
                    flush();
                }
                $i++;
            }
        }

        $CFG->debug = $olddebug;

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071803, 'format', 'page');
    }

    if ($result && $oldversion < 2007071804) {

    /// Changing the default of field sortorder on table format_page_items to 0
        $table = new xmldb_table('format_page_items');
        $field = new xmldb_field('sortorder');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'position');

    /// Launch change of default for field sortorder
        $dbman->change_field_default($table, $field);

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071804, 'format', 'page');
    }

    if ($result && $oldversion < 2007071805) {

        // This could be huge, do not output everything
        $olddebug  = $CFG->debug;
        $CFG->debug = false;
        $result    = true;

        // Make sure all page sortorder values are set properly (before this was never really managed properly)
        if ($courses = $DB->get_records('course', array('format' => 'page'), '', 'id')) {
            echo 'Fixing page sort orders in courses with format = \'page\'....';

            $i = 0;
            foreach ($courses as $course) {
                course_page::fix_page_sortorder($course->id);
                if ($i % 5 == 0) {
                    echo '.';
                    flush();
                }
                $i++;
            }
        }
        // Restore
        $CFG->debug = $olddebug;

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071805, 'format', 'page');
    }

    if ($result && $oldversion < 2007071806) {
        // Remove old setting
        if ($DB->record_exists('config', array('name' => 'pageformatusedefault'))) {
            unset_config('pageformatusedefault');
        }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2007071806, 'format', 'page');
    }

    if ($result && $oldversion < 2011020301) {
    /// Define field cmid to be added to format_page
        $table = new xmldb_table('format_page');
        $field = new xmldb_field('cmid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'showbuttons');

    /// Launch add field cmid
    	if (!$dbman->field_exists($table, $field)){
	        $dbman->add_field($table, $field);
	    }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2011020301, 'format', 'page');
    }

    if ($result && $oldversion < 2012062900) {

    /// Define table learning_discussion to be created
        $table = new xmldb_table('format_page_discussion');

    /// Adding fields to table learning_discussion
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('discussion', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null);
        $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastwriteuser', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table format_page_discussion
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for learning_discussion
    	if !$dbman->table_exists($table)){
	        $dbman->create_table($table);
	    }

    /// Define table learning_discussion_user to be created
        $table = new xmldb_table('format_page_discussion_user');

    /// Adding fields to table learning_discussion_user
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastread', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table learning_discussion_user
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for learning_discussion_user
    	if (!$dbman->table_exists($table)){
	        $dbman->create_table($table);
	    }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2012062900, 'format', 'page');
    }

   if ($oldversion < 2013020702) {
        // Define table format_page_access to be created
        $table = new xmldb_table('format_page_access');

        // Adding fields to table format_page_access
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('policy', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'user');
        $table->add_field('arg1int', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('arg2int', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('arg3text', XMLDB_TYPE_CHAR, '32', null, null, null, null);

        // Adding keys to table format_page_access
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for format_page_access
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // page savepoint reached
        upgrade_plugin_savepoint(true, 2013020702, 'format', 'page');
    }

    if ($result && $oldversion < 2013021000) {
    /// Define field cmid to be added to format_page
        $table = new xmldb_table('format_page');

        $field = new xmldb_field('lockingcmid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'cmid');
        if (!$dbman->field_exists($table, $field)){
        	$dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lockingscore');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'lockingcmid');

    /// Launch add field cmid
        if (!$dbman->field_exists($table, $field)){
	        $dbman->add_field($table, $field);
	    }

        /// course format savepoint reached
        upgrade_plugin_savepoint(true, 2013021000, 'format', 'page');
    }
    
    /** Moodle 2 **/
   	if ($oldversion < 2013040900) {
        $table = new xmldb_table('format_page');

		// add field displaymenu
        $field = new xmldb_field('displaymenu');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'display');

	    // Launch add field displaymenu
        if (!$dbman->field_exists($table, $field)){
	        $dbman->add_field($table, $field);
	    }

        // change type of column widths
        $field = new xmldb_field('prefleftwidth');
        $field->set_attributes(XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, '200');
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('prefcenterwidth');
        $field->set_attributes(XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, '600');
        $dbman->change_field_type($table, $field);
        $dbman->change_field_default($table, $field); // from 400 to 600

        $field = new xmldb_field('prefrightwidth');
        $field->set_attributes(XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, '200');
        $dbman->change_field_type($table, $field);
	    
	    // convert all display values
	    
	    if ($pages = $DB->get_records('format_page')){
	    	foreach ($pages as $p){
	    		$p->display = ($p->display) ? 1 : 0;
	    		$DB->update_record('format_page', $p);
	    	}
	    }   		
	    
	    // Relocate all positions for blocks pageitems
	    if ($pageitems = $DB->get_records('format_page_items')){

	    	foreach($pageitems as $pi){

	    		if ($pi->blockinstance){

		    		if (!$blockinstance = $DB->get_record('block_instances', array('id' => $pi->blockinstance))){
		    			continue;
		    		}		    			
		    		
		    		format_page_upgrade_add_position($pi, $blockinstance);
		    		
			    } else {
			    	// What happens if activities ? We have to create page_module blocks that were
			    	// faked in Moodle 1.9 
			    	if (!$pi->cmid){
			    		continue; // this is an error. Should not happen
			    	}
			    	
			        $blockinstance = new stdClass;
			        $blockinstance->blockname = 'page_module';
			        $courseid = $DB->get_field('course_modules', 'course', array('id' => $pi->cmid));
			        $parentcontext = context_course::instance($courseid);
			        $blockinstance->parentcontextid = $parentcontext->id;
			        $blockinstance->showinsubcontexts = 0;
			        $blockinstance->pagetypepattern = 'course-view-*';
			        $blockinstance->subpagepattern = '';
		    		list($region,$weight) = format_page_upgrade_prepare_region($pi, $blockinstance);
			        $blockinstance->defaultregion = $region;
			        $blockinstance->defaultweight = $weight;
			        $config = new StdClass;
			        $config->cmid = $pi->cmid;
			        $blockinstance->configdata = base64_encode(serialize($config));
			        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
			
			        // Ensure the block context is created.
			        context_block::instance($blockinstance->id);

					format_page_upgrade_add_position($pi, $blockinstance);
			    }
	    	}
	    }
	    
        upgrade_plugin_savepoint(true, 2013040900, 'format', 'page');
	}

    return $result;
}

function format_page_upgrade_prepare_region(&$pi, &$blockinstance){
	static $w = array();	

	if (!array_key_exists($blockinstance->contextid, $w)){
		$w[$blockinstance->contextid] = array('l' => 0, 'c' => 0, 'r' => 0);
	}
	
	switch($pi->position){
		case 'l' : 
			$region = 'side-pre';
			$weight = ++$w[$blockinstance->contextid]['l'];
			break;
		case 'c' : 
			$region = 'main';
			$weight = ++$w[$blockinstance->contextid]['c'];
			break;
		case 'r' :
			$region = 'side-post';
			$weight = ++$w[$blockinstance->contextid]['r'];
			break;
		default:
			return array('', 0);
	}								    				
	
	return array($region, $weight);
}

function format_page_upgrade_add_position(&$pi, &$blockinstance){
	global $DB;

	// we try not pertubate existing records
	if ($DB->record_exists('block_positions', array('blockinstanceid' => $blockinstance->id, 'contextid' => $blockinstance->parentcontextid))){
		return;
	} 
		
	$pageblockpos = new StdClass;
	$pageblockpos->blockinstanceid = $blockinstance->id;
	$pageblockpos->contextid = $blockinstance->parentcontextid;
	$pageblockpos->pagetype = 'course-view-page';
	$pageblockpos->subpage = ''; 
	$pageblockpos->visible = $pi->visible;
	list($region,$weight) = format_page_upgrade_prepare_region($pi, $blockinstance);
	$pageblockpos->region = $region;
	$pageblockpos->weight = $weight;
	$DB->insert_record('block_positions', $pageblockpos);
}
