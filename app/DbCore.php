<?php
/*
 * $Id: DbCore.php,v 1.6 2004/01/22 11:59:52 dan Exp $
 */

include_once("config/config.php");
include_once("Database.php");

class DbCore extends Database {

    function DbCore() {
        // Must call the parent's constructor
        $this->Database();
    }

    /**
     * Updates the last_active field every time a user changes pages
     * Coding done by: dan@linux.nu
     */
    function updateLastActive($userId, $now) {
        if (is_numeric($userId) && $userId > 0) {
            if ($this->cache_update('last_cache_update_last_active')) {
                $q = "UPDATE users SET user_lastactive = '".$now."'
                      WHERE user_id = $userId"; 
                $this->make_query($q);
            }
        }
    }

    /**
     * Fetch data for trying to log in a user
     * @param string $username The name of the user which tries to log in
     */
    function fetchLoginInfo($username, $workstationId) {
        $q =
            "SELECT *, 
             user_lastonline as lastonline  
             FROM users 
             WHERE LOWER(user_username) = LOWER('".$username."') AND
		     user_workstation = '$workstationId' AND 
             user_access < 4";
            
        $res = $this->make_query($q); //echo $q; die;
        return $res->fetchRow(DB_FETCHMODE_ASSOC);
    }

    /**
     * Updates some user data when the user logs in
     * @param integer $userId The user id which should be updated
     * @param integer $ip The ip-number the user currently has
     */
    function updateLastLogin($userId, $ip) {
        if (is_numeric($userId)) {
            $q =
                "UPDATE users set user_logincount = user_logincount + 1, ".
                "user_lastonline = NOW(), ".
                "user_ip = '".$ip."' ".
                "WHERE user_id = '".$userId."'";
            $this->make_query($q);
        }
    }

    function preAddUser($username, $email, $password, $activation_key,
                        $extra_info = '') {
        $q = "INSERT INTO users_activate
              ( activate_email,
                activate_username,
                activate_extra_info,
                activate_password,
                activate_validation,
                activate_date
              )
              VALUES (
                      '".$email."',
                      '".$username."',
                      '',
                      '".md5($password)."',
                      '".$activation_key."',
                      NOW()
              )";
        // '".$this->magic_addslashes($extra_info)."',
        $this->make_query($q);
        $activate_id = mysql_insert_id();


        // The extra info
        // $config_extra_signup_info is supposed to be an array defined in
        // site-config.php consisting of words to accept as fields in the
        // pre-add-user process. The same fields should be defined in the
        // POST-form when a user signs up.
        global $config_extra_signup_info;
        if (is_numeric($activate_id) && $activate_id > 0 &&
            isset($config_extra_signup_info) &&
            is_array($config_extra_signup_info)) {

            while (list($field, $data) = each($_POST)) {
                if (in_array($field, $config_extra_signup_info)) {
                    $q = "INSERT INTO users_activate_extra (
                            extra_activate_id,
                            extra_field,
                            extra_data
                          )
                          VALUES (
                            $activate_id,
                            '".$this->magic_addslashes($field)."',
                            '".$this->magic_addslashes($data)."'
                          )";
                    $this->make_query($q);
                }
            }
        }

    }

    function fetchNumInboxMessages($userId) {
        $q = "SELECT COUNT(privmess_id) AS num
              FROM m_inbox
              WHERE privmess_rec_id = $userId
              AND privmess_new = 1";
        $res = $this->make_query($q);
        $row = $res->fetchRow();
        return $row['num'];
    }
    
	function selectCasino () {
		$q = "SELECT * FROM casino ORDER BY name";
		return $this->my_getAll($q);
	}
	
	function selectPlayType () {
		$q = "SELECT * FROM play ORDER BY name";
		return $this->my_getAll($q);
	}
	
	function selectCountry () {
		$q = "SELECT * FROM country ORDER BY name";
		return $this->my_getAll($q);
	}	
	
	function selectClaimMethod () {
		$q = "SELECT * FROM claim_method ORDER BY name";
		return $this->my_getAll($q);
	}	    
	
	function selectWorkorder ($workorder = "") { 
	 	$q = "
 			SELECT 
	 		a.*, 
	 		b.id		 		AS casinoId, 
	 		b.name		 		AS casinoName, 
	 		c.user_id 			AS clientId, 
	 		c.user_lastname 	AS clientLastname, 
	 		c.user_firstname 	AS clientFirstname, 
	 		f.user_id 			AS creatorId, 
	 		f.user_lastname 	AS creatorLastname, 
	 		f.user_firstname 	AS creatorFirstname,	 	
	 		d.name 				AS play, 
	 		e.name 				AS claimMethodName
	 		FROM workorder 		AS a
	 		LEFT JOIN casino 		AS b ON a.casino = b.id 
	 		LEFT JOIN users 		AS c ON a.client = c.user_id 
	 		LEFT JOIN play 		AS d ON a.play = d.id
	 		LEFT JOIN claim_method AS e ON a.claim_method = e.id
	 		LEFT JOIN users 		AS f ON a.creator = f.user_id ";
	 	if ( ! empty($workorder)) {
	 		$q .= "WHERE a.id = '".$workorder."'";		//echo $q;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();	 		
		} 
	 	return $this->my_getAll($q);		
	}  	

	function selectWorkOrderAssigned ($userId) {
	 	$q = "
 			SELECT 
	 		a.id, a.date_expiry, a.amount_deposit, a.amount_bonus, 
	 		c.user_id 			AS clientId, 
	 		c.user_lastname 	AS clientLastname, 
	 		c.user_firstname 	AS clientFirstname, 
	 		d.id				AS assignId  
	 		e.id				AS workstationId
	 		e.name				AS workstationName
	 		FROM workorder 		AS a
	 		INNER JOIN users 	AS c ON a.client = c.user_id 
	 		LEFT JOIN workorder_assign AS d ON a.id = d.workorder AND d.user = '".$userId."'
	 		LEFT JOIN workstation AS e ON c.user_workstation = e.id"; // echo $q;
	 	return $this->my_getAll($q);			 
	}

	function selectAssignedUsers ($workorderId) {
	 	$q = "
			SELECT 
			a.*, d.id AS assignId, d.adopted  
			FROM users AS a
			LEFT JOIN workorder_assign	AS d ON a.user_id = d.user
	 		AND d.workorder = '".$workorderId."'"; //echo $q;
	 	return $this->my_getAll($q);			 
	}	
	
	function selectWorkstationName ($workstationId) { 
		$q = "SELECT name FROM workstation 
				WHERE id = ".$workstationId;
        $res = $this->make_query($q);
        return $res->fetchRow();			
	}
	
	
	
}

