<?php

/**
 * @package format-page
 * @category format
 * @author Valery Fremaux
 * @date 02/12/2012
 * @version Moodle 2
 *
 * Library of functions for rpc remote calls at tracker. All complex
 * variables transport are performed using JSON format.
 */

/**
 * Constants
 *
 */
if (!defined('RPC_SUCCESS')) {
        define('RPC_TEST', 100);
        define('RPC_SUCCESS', 200);
        define('RPC_FAILURE', 500);
        define('RPC_FAILURE_USER', 501);
        define('RPC_FAILURE_CONFIG', 502);
        define('RPC_FAILURE_DATA', 503);
        define('RPC_FAILURE_CAPABILITY', 510);
}

/**
 * checks an user has local identity and comes from a known host
 * @param string $username the user's login
 * @param string $remotehostroot the host he comes from
 * @return a failure report if unchecked, null elsewhere.
 */
function format_page_rpc_check($username, $remotehostroot, &$localuser) {
    global $DB;

    // Get local identity for user.

    if (!$remotehost = $DB->get_record('mnet_host', array('wwwroot' => $remotehostroot))) {
        $response->status = RPC_FAILURE;
        $response->error = "Calling host is not registered. Check MNET configuration";
        return json_encode($response);
    }

    if (!$localuser = $DB->get_record_select('user', "username = '".addslashes($username)."' AND mnethostid = $remotehost->id AND deleted = 0")) {
        $response->status = RPC_FAILURE_USER;
        $response->error = "Calling user has no local account. Register remote user first";
        return json_encode($response);
    }

    return null;
}

/*
 * creates a page in course
 * @param int $courseid
 * @param object $pagerec a page record
 * @param boolean $nojson when true, avoids serializing through JSON syntax
 * @return string a JSON encoded information structure.
 */
function format_page_rpc_create_page($username, $remotehostroot, $courseid, $pagerec = null, $nojson = true) {
    global $CFG, $DB;

    if ($failedcheck = format_page_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;

    if ($nojson) {
        return true;
    }

    $format = $DB->get_field('course', 'format', array('id' => $courseid));
    if ($format != 'page') {
        $response->status = RPC_FAILURE_DATA;
        $response->error = 'Not a page formatted course.';
    }

    $page = new StdClass();
    $page->courseid = $courseid;

    if ($pagerec) {
        foreach($pagerec as $field => $value) {
            $page->$field = $value;
        }
    }
    
    if (empty($page->nameone)) {
        $page->nameone = get_string('newpage', 'format_page'));
    }

    if (empty($page->nametwo)) {
        $page->nametwo = get_string('newpage', 'format_page'));
    }

    $pageobj = new course_page($page);
    $pageobj->insert_in_sections();

    return json_encode(true);
}

/*
 * returns an internal pagid, knowing a page idnumber.
 * If idnumber is not unique, will return the list of all page ids
 * holding this idnumber.
 * @param int $pageidnumber
 * @return string a JSON encoded information structure.
 */
function format_page_rpc_get_page_id_from_idnumber($username, $remotehostroot, $pageidnumber) {
    global $CFG, $DB;

    if ($failedcheck = format_page_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;

    $pages = $DB->get_records('format_page', array('idnumber' => $pageidnumber));

    return json_encode(implode(',', array_keys($pages));
}

/*
 * creates a page in course from an identified page template by full copying its content.
 * @param int $courseid
 * @param object $pagerec a page record overrdides that will be applied to format_pge record after being copied
 * @param int $templateid the pageID of the orginal template to copy. 
 * @return string a JSON encoded information structure.
 */
function format_page_rpc_create_page_from_template($username, $remotehostroot, $courseid, $templateid, $pagerec = null) {

    if ($failedcheck = format_page_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;

    return true;
}

/**
 * Adds a page_item to a page building every internal links needed.
 * @param string $username the user's login
 * @param string $remotehostroot the host he comes from
 * @param string $pageid the page id where to add the item.
 * @param string $blockarea the block region, as 'left', 'main', 'right'
 * @param string $pos the position in region. Special values : -1 first, 9999 end. If numeric but higher 
 * max numer of items, will take the max available pos (eq. end). If pos has an item, will push the stack down from this location.
 * @param string $itemtype, 'block' or 'module'. If is a module, will create the page_module block instance
 * @param array $configdata, an array of properties that will be pushed into block instance configuration if building a block, or
 * into the course module instance record if a module.
 */
function format_page_rpc_add_page_item($username, $remotehostroot, $pageid, $blockregion, $pos, $itemtype, $itemclass, $configdata) {
    global $CFG, $DB;

    if ($failedcheck = format_page_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;


    return;
}

/**
 * Deletes a page_item.
 * @param string $username the user's login
 * @param string $remotehostroot the host he comes from
 * @param string $pageitemid the pageitem id
 */
function format_page_rpc_delete_page_item($username, $remotehostroot, $pageitemid) {
    global $CFG, $DB;

    if ($failedcheck = format_page_rpc_check($username, $remotehostroot, $localuser)) return $failedcheck;

    if (!$pageitem = $DB->get_record('format_page_item', array('id' => $pageitemid)) {
        $response->status = RPC_FAILURE_USER;
        $response->error = "Page item does not exist";
        return json_encode($response);
    }

    $page = course_page::get($pageitem->pageid);
    if ($page) {
        $page->block_delete($pageitem);
    }

    $response->status = RPC_SUCCESS;
    return json_encode($response);
}
