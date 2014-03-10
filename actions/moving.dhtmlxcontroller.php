<?php

if ($service == 'load'){
	header("Content-Type:text/xml\n\n");
	echo page_xml_tree($course);
	die;
} elseif ($service == 'dhtmlxprocess'){
	header("Content-Type:text/xml\n\n");
	$dhtmlx_status = optional_param('!nativeeditor_status', '', PARAM_TEXT);
	$dhtmlx_id = optional_param('tr_id', '', PARAM_INT);
	$dhtmlx_order = optional_param('tr_order', '', PARAM_INT);
	$dhtmlx_pid = optional_param('tr_pid', '', PARAM_INT);

	if ($dhtmlx_status == 'updated'){
		$tr_page = course_page::get($dhtmlx_id);
		$tr_page->parent = $dhtmlx_pid;
		$tr_page->sortorder = course_page::prepare_page_location($tr_page->parent, $tr_page->sortorder, $dhtmlx_order); // get the position we exchange with
		$tr_page->save();
		
		course_page::fix_tree_level($tr_page->parent);

		echo page_send_dhtmlx_answer($dhtmlx_status, $dhtmlx_id, $tr_page->id);
		die;
	}
	
	echo page_xml_tree($course);
	die;
}