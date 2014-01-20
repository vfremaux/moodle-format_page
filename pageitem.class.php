<?php

/**
* Represents a pageitem and all attached methods
* //TODO : This class not yet used in format_page.
*
*/

class format_page_item{

	/**
	* The underlying page item record
	*
	*/	
	protected $formatpageitem;
	
	/**
	*
	*
	*/
	function __construct($formatpageitemrec){
		if ($formatpageitemrec){
			$this->formatpageitem = $formatpageitemrec;
 		}
	} 
    
    /**
    * wraps a magic getter to internal fields
    *
    */
    function __get($fieldname){
    	if (isset($this->formatpage->$fieldname)){
    		return $this->formatpage->$fieldname;
    	} else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldnale.'in format_page object');
    	}
    }

    /**
    * wraps a magic getter to internal fields
    *
    */
    function __set($fieldname, $value){
    	if (isset($this->formatpageitem->$fieldname)){
    		$magicmethname = 'magic_set_'.$fieldname;
    		if (method_exists('format_page_item', $magicmethodname)){ // allows override with checked setters if needed
    			$this->$magicmethodname($value);
    		} else {
    			$this->formatpageitem->$fieldname = $value;
    		}
    	} else {
            throw new coding_exception('Trying to acces an unexistant field '.$fieldnale.'in format_page_item object');
    	}
    }

	/**
	*
	*
	*/
	function save(){
		global $DB;
		
		if (!is_object($this->formatpageitem)){
			return;
		}
		
		if ($this->formatpageitem->id){
			$DB->update_record('format_page_item', $this->formatpageitem);
		} else {
			$DB->insert_record('format_page_item', $this->formatpageitem);
		}
	}
		    
    /**
     * Local method - set the member formatpage
     *
     * @return void
     **/
    function set_formatpageitem($formatpageitem) {
        $this->formatpageitem = $formatpageitem;
    }

	/**
	* Removes completely a page item from db, and all related block instances or course modules
	*
	*/
	function delete(){
	    global $CFG, $COURSE, $DB;
	
	    require_once($CFG->libdir.'/blocklib.php');
	    
	    $bm = new block_manager();
	
	    // we leave module cleanup to the manage modules tab... blocks need some help though.
	    if (!empty($pageitem->blockinstance)) {
	    	
	    	$blockid = $pageitem->blockinstance;

	        require_sesskey();
	
	        $block = $bm->page->blocks->find_instance($blockid);
	
	        if (!$block->user_can_edit() || !$bm->page->user_can_edit_blocks() || !$block->user_can_addto($bm->page)) {
	            throw new moodle_exception('nopermissions', '', $bm->page->url->out(), get_string('deleteablock'));
	        }
	
	        blocks_delete_instance($block->instance);
	
	    }

	    $DB->delete_records('format_page_items', array('id' => $pageitem->id));
	    
	    $sql = "
    		UPDATE 
    			{format_page_items}
			SET 
				sortorder = sortorder - 1
			WHERE 
				pageid = $pageitem->pageid AND 
				position = '$pageitem->position' AND 
				sortorder > $pageitem->sortorder
		";
    	$DB->execute($sql);
	}
}