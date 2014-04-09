<?php

require_once $CFG->dirroot.'/user/selector/lib.php';
require_once $CFG->dirroot.'/group/lib.php';

/**
 * Base class to avoid duplicating code.
 */
abstract class page_user_selector_base extends user_selector_base {
    protected $pageid;
    protected $courseid;

    /**
     * @param string $name control name
     * @param array $options should have two elements with keys pageid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $options['accesscontext'] = context_course::instance($options['courseid']);
        parent::__construct($name, $options);
        $this->pageid = $options['pageid'];
        $this->courseid = $options['courseid'];
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['pageid'] = $this->pageid;
        $options['courseid'] = $this->courseid;
        return $options;
    }

    /**
     * @param array $roles array in the format returned by groups_calculate_role_people.
     * @return array array in the format find_users is supposed to return.
     */
    protected function convert_array_format($roles, $search) {
        if (empty($roles)) {
            $roles = array();
        }
        $groupedusers = array();
        foreach ($roles as $role) {
            if ($search) {
                $a = new stdClass;
                $a->role = $role->name;
                $a->search = $search;
                $groupname = get_string('matchingsearchandrole', '', $a);
            } else {
                $groupname = $role->name;
            }
            $groupedusers[$groupname] = $role->users;
            foreach ($groupedusers[$groupname] as &$user) {
                unset($user->roles);
                $user->fullname = fullname($user);
            }
        }
        return $groupedusers;
    }
}

/**
 * User selector subclass for the list of users who are in a certain page.
 * Used on the add group memebers page.
 */
class page_members_selector extends page_user_selector_base {

    public function find_users($search) {
    	global $DB;
    	
        $context = context_course::instance($this->courseid);
        list($wherecondition, $params) = $this->search_sql($search, 'u');

		$sql = "
			SELECT
				u.id AS userid, r.id AS roleid, r.shortname AS roleshortname, r.name AS rolename,
				" . $this->required_fields_sql('u') . "
			FROM
				{user} u,
				{format_page_access} fpa
          	LEFT JOIN 
          		{role_assignments} ra 
          	ON 
          		(ra.userid = userid AND ra.contextid " . get_related_contexts_string($context) . ")
            LEFT JOIN 
            	{role} r 
            ON 
            	r.id = ra.roleid
			WHERE
				fpa.pageid = ? AND
				fpa.policy = 'user' AND
				u.id = fpa.arg1int AND
				u.deleted = 0
			ORDER BY
				u.lastname,
				u.firstname
		";
		$rs = $DB->get_recordset_sql($sql, array($this->pageid));
		$roles = groups_calculate_role_people($rs, $context);
				
        return $this->convert_array_format($roles, $search);
    }
}

/**
 * User selector subclass for the list of users who are not in a certain group.
 * Used on the add group members page.
 */
class page_non_members_selector extends page_user_selector_base {
    const MAX_USERS_PER_PAGE = 100;

    /**
     * An array of user ids populated by find_users() used in print_user_summaries()
     */
    private $potentialmembersids = array();

    public function output_user($user) {
        return parent::output_user($user);
    }

    /**
     * Returns the user selector JavaScript module
     * @return array
     */
    public function get_js_module() {
        return self::$jsmodule;
    }

    public function find_users($search) {
        global $DB;

        // Get list of allowed roles.
        $context = context_course::instance($this->courseid);
        $availableroles = get_roles_for_contextlevels(CONTEXT_COURSE);
    	
        if ($validroleids = array_keys($availableroles)) {
            list($roleids, $roleparams) = $DB->get_in_or_equal($validroleids, SQL_PARAMS_NAMED, 'r');
        } else {
            $roleids = " = -1";
            $roleparams = array();
        }
        
        // Get the search condition.
        list($searchcondition, $searchparams) = $this->search_sql($search, 'u');

        // Build the SQL
        list($enrolsql, $enrolparams) = get_enrolled_sql($context);

        $fields = "SELECT u.id AS userid, r.id AS roleid, r.shortname AS roleshortname, r.name AS rolename, 
                          " . $this->required_fields_sql('u');
        $sql = "   FROM {user} u
                   JOIN ($enrolsql) e ON e.id = u.id
              LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid " . get_related_contexts_string($context) . " AND ra.roleid $roleids)
              LEFT JOIN {role} r ON r.id = ra.roleid
                  WHERE u.deleted = 0
                        AND u.id NOT IN (
                        	SELECT 
                        		arg1int
							FROM 
								{format_page_access} fpa
							WHERE 
								fpa.pageid = :pageid AND
								fpa.policy = 'user')
                        AND $searchcondition";
        $orderby = "ORDER BY u.lastname, u.firstname";

        $params = array_merge($searchparams, $roleparams, $enrolparams);
        $params['courseid'] = $this->courseid;
        $params['pageid']  = $this->pageid;
        
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id) $sql", $params);
            if ($potentialmemberscount > page_non_members_selector::MAX_USERS_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $rs = $DB->get_recordset_sql("$fields $sql $orderby", $params);        
		$roles = groups_calculate_role_people($rs, $context);

        //don't hold onto user IDs if we're doing validation
        if (empty($this->validatinguserids)) {
            if($roles) {
                foreach($roles as $k=>$v) {
                    if($v) {
                        foreach($v->users as $uid=>$userobject) {
                            $this->potentialmembersids[] = $uid;
                        }
                    }
                }
            }
        }

        return $this->convert_array_format($roles, $search);
    }
}

?>