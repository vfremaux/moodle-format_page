<?php
/**
 * Page format
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
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package format_page
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 */

/**
 * Page format restore plugin
 *
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @package format_page
 */
class restore_format_page_plugin extends restore_format_plugin {

    /**
     * Returns the paths to be handled by the plugin at course level
     */
    protected function define_course_plugin_structure() {

        return array(
            new restore_path_element('page', $this->get_pathfor('/pages/page')),
            new restore_path_element('page_item', $this->get_pathfor('/pages/page/items/item')),
            new restore_path_element('page_discussion', $this->get_pathfor('/pages/page/discussion')),
            new restore_path_element('page_access', $this->get_pathfor('/pages/page/grants/access')),
        );
    }

    /**
     * Restore a single page
     */
    public function process_page($data) {
        global $DB;

        $data  = (object) $data;
        $oldid = $data->id;
        $data->courseid = $this->task->get_courseid();
        $data->cmid = $this->get_mappingid('course_modules', $data->cmid);
        $data->lockingcmid = $this->get_mappingid('course_modules', $data->lockingcmid);
        $data->datefrom = $this->apply_date_offset($data->datefrom);
        $data->dateto = $this->apply_date_offset($data->dateto);

        $newid = $DB->insert_record('format_page', $data);

        $this->set_mapping('format_page', $oldid, $newid);
    }

    /**
     * Restore a page item
     * 
     */
    public function process_page_item($data) {
        global $DB;

        $data  = (object) $data;
        $data->pageid = $this->get_mappingid('format_page', $data->pageid);
        $oldid = $data->id;

        $newid = $DB->insert_record('format_page_items', $data);
        $this->set_mapping('format_page_items', $oldid, $newid);
    }

    /**
     * Restore a page discussion
     */
    public function process_page_discussion($data) {
        global $DB;

        $data  = (object) $data;
        $data->pageid = $this->get_mappingid('format_page', $data->pageid);
        $data->lastmodified = $this->apply_date_offset($data->lastmodified);

        $DB->insert_record('format_page_discussion', $data);
    }

    /**
     * Restore a page access
     */
    public function process_page_access($data) {
        global $DB;

        $data  = (object) $data;
        $data->pageid = $this->get_new_parentid('format_page');
        
        // this fixes dynamic annotation against access policy

		switch($data->policy){
			case 'user':
				$data->arg1int = $this->get_mappingid('user', $data->arg1int);
				break;
			case 'group':
				$data->arg1int = $this->get_mappingid('group', $data->arg1int);
				break;
			case 'profile':
				break;
		}

        $DB->insert_record('format_page_access', $data);
    }

	/**
	*
	* remaps parent page as soon as all pages are restored.
	*
	*/    
    function after_execute_course(){
    	global $DB;

    	// after all pages and page items done, we need to remap parent pages links
    	$courseid = $this->task->get_courseid();
    	
    	if ($childpages = $DB->get_records_select('format_page', " courseid = ? AND parent != 0 ", array($courseid), 'id,parent')){
    		foreach($childpages as $page){
    			$newparent = $this->get_mappingid('format_page', $page->parent);
    			$DB->set_field('format_page', 'parent', $newparent, array('id' => $page->id));
    		}
		}    	
    }
    
    /**
    *
    *
    */
    function after_restore_course(){
    	global $DB;

    	$courseid = $this->task->get_courseid();
			
		// get all blocks that are NOT page modules and try to remap them.
		// Page modules will fix by them selves

    	$sql = "
    		SELECT DISTINCT
    			fpi.*
    		FROM
    			{format_page_items} fpi,
    			{format_page} fp,
    			{block_instances} bi,
    			{context} c
    		WHERE
    			fp.courseid = ? AND
    			fpi.pageid = fp.id
    	";

		if ($blockitems = $DB->get_records_sql($sql, array($courseid))){
			foreach($blockitems as $fpi){
				$oldblockinstance = $fpi->blockinstance;
				if ($newblockid = $this->get_mappingid('block_instance', $fpi->blockinstance)){

					$newblock = $DB->get_record('block_instances', array('id' => $newblockid));
					if ($newblock->blockname == 'page_module') continue; // skip page modules that have thei own remapping process
					
					if (!$newblock){
						// some fake blocks can be missing
						$this->step->log("Format page : Remapped block $newblockid is missing. ", backup::LOG_ERROR);    			
						continue;
					}

					$fpi->blockinstance = $newblockid;
					$DB->update_record('format_page_items', $fpi);
					
					// also remap block records sub page bindings
					$subpagepattern = $DB->get_field('block_instances', 'subpagepattern', array('id' => $newblockid));
					$contextid = $DB->get_field('block_instances', 'parentcontextid', array('id' => $newblockid));
					$oldpageid = str_replace('page-', '', $subpagepattern);

					if (empty($oldpageid)){ // fix missings
						$oldpageid = $fpi->pageid;
					}

					$newpageid = $this->get_mappingid('format_page', $oldpageid);
					$DB->set_field('block_instances', 'subpagepattern', 'page-'.$newpageid, array('id' => $newblockid));

					if ($subpagepattern = $DB->get_field('block_positions', 'subpage', array('blockinstanceid' => $newblockid, 'contextid' => $contextid))){
						$DB->set_field('block_positions', 'subpage', 'page-'.$newpageid, array('blockinstanceid' => $newblockid, 'contextid' => $contextid));
					}
				} else {
					// some fake blocks can be missing
					// $this->step->log("Format page : Failed to remap $oldblockinstance . ", backup::LOG_ERROR);    			
				}
			}
		} else {
			// $this->step->log("Format page : No blocks to remap. ", backup::LOG_ERROR);    			
		}
		
		// delete all sections		
		$DB->delete_records_select('course_sections', " course = $courseid AND section != 0 "); 
		
		// rebuild all section list from page information
		$allpages = course_page::get_all_pages($courseid, 'flat');
		
		$i = 1;
		foreach($allpages as $page){
			$page->make_section($i, $this);
			$page->save();
			$i++;
		}
		
		rebuild_course_cache($courseid, true);
    }
    
    public function external_get_mappingid($table, $oldid){
    	// echo "converting $oldid to ";
    	$newid = $this->get_mappingid($table, $oldid);
    	// echo " $newid in $table <br/>";
    	return 0 + $newid;
    	
    }
}