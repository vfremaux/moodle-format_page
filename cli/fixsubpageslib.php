<?php

/**
* Fix script for flexipage
*
*
*/

// this script repairs subpage unassigned blocks.

function page_format_remap_subpages($courseid = null, $blockinstanceid = null){
	global $DB, $CFG;
	
	$verbose = (!$courseid && !$blockinstanceid);

	if ($verbose){
		mtrace("start converting page format blocks...");
		mtrace("querying in DB $CFG->dbname at $CFG->dbhost ");
	}
	
	$courseclause = '';
	if ($courseid){
		$context = context_course::instance($courseid);
		$courseclause = ' AND bi.parentcontextid = '.$context->id;
	}

	$instanceclause = '';
	if ($blockinstanceid){
		$instanceclause = ' AND bi.id = '.$blockinstanceid;
	}
	
	$sql = "
		SELECT
			bi.*,
			fpi.pageid as pageid
		FROM
			{block_instances} bi,
			{format_page_items} fpi
		WHERE
			fpi.blockinstance = bi.id
			{$courseclause} AND
			(bi.subpagepattern = '' OR bi.subpagepattern IS NULL)
	";
	
	$allpagedblocks = $DB->get_records_sql($sql);
	
	if ($allpagedblocks){
		if ($verbose){
			mtrace("fixing ".count($allpagedblocks)." blocks ");
		}
		foreach($allpagedblocks as $b){
			$pageid = $b->pageid;
			$b->subpagepattern = 'page-'.$pageid;
			unset($b->pageid);
			$DB->update_record('block_instances', $b);
			if ($verbose){
				mtrace("Fixing block instance $b->id in $pageid ");
			}
			if ($p = $DB->get_record('block_positions', array('blockinstanceid' => $b->id))){
				$p->subpage = 'page-'.$pageid;
				$DB->update_record('block_positions', $p);
				if ($verbose){
					mtrace("Fixing block position $p->id , $p->blockinstanceid in $p->subpage ");
				}
			}
		}
	}

	if ($verbose){
		mtrace("...finished");
	}
}