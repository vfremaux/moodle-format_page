
function reload_activity_list(wwwroot, courseid, pageid, filterobj){
	
    url = wwwroot+'/course/format/page/ajax/get_activity_list.php?id='+courseid+'&page='+pageid+'&filter='+filterobj.value;
   	$.post(url, function(data) {
		$('#page-mod-list').html(data);
   	});
	
}