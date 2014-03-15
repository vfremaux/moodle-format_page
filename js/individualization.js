
function set_disabled(id, direction){
    document.forms['individualize_form'].elements[direction+'_date_'+id].disabled = true;
    document.forms['individualize_form'].elements[direction+'_hour_'+id].disabled = true;
    document.forms['individualize_form'].elements[direction+'_min_'+id].disabled = true;
}

function change_selector_state(checkboxobj, id, direction){
    if (checkboxobj.checked == false){
        state = true;
    } else {
        state = false;
    }
    document.forms['individualize_form'].elements[direction+'_date_'+id].disabled = state;
    document.forms['individualize_form'].elements[direction+'_hour_'+id].disabled = state;
    document.forms['individualize_form'].elements[direction+'_min_'+id].disabled = state;
}