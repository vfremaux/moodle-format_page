/*
 *
 */
// jshint undef:false, unused:false

function reload_activity_list(courseid, pageid, filterobj) {
    url = M.cfg.wwwroot + '/course/format/page/ajax/get_activity_list.php?id=' + courseid + '&page=' + pageid + '&filter=' + filterobj.value;
    $.post(url, function (data) {
        $('#page-mod-list').html(data);
    });

}