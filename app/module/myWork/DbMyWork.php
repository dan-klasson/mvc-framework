<?php

include_once("config/config.php");
include_once("Database.php");

class DbMyWork extends Database {
	
	function DbMyWork () {
        // Must call the parent's constructor
        $this->Database();		
	}
	
	function selectOrphanPool ($adopted, $workstationId, $userId, $clientId = 0) {
		
		$adopt_type = $adopted ? 'INNER' : 'LEFT';
		$client = $clientId ? "AND d.user_id = $clientId" : "";
		
	 	$q = "
 			SELECT a.amount_bonus, a.amount_deposit, a.date_expiry, 
	 		b.id, b.adopted, c.id AS adopted, b.type, 
	 		d.user_firstname AS clientFirstname, 
	 		d.user_lastname  AS clientLastname, 
	 		e.name AS casinoName 
			FROM workorder AS a
 			INNER JOIN workorder_assign AS b ON b.workorder = a.id 
 			$adopt_type JOIN workorder_adopt AS c ON c.workorder_assign = b.id AND c.user = '$userId'
	 		LEFT JOIN users AS d ON b.client = d.user_id 
	 		LEFT JOIN casino AS e ON a.casino = e.id 
 			WHERE b.workstation = '$workstationId'
 			$client
	 		AND UNIX_TIMESTAMP(a.date_expiry) > '".time()."' 
	 		AND b.assigned = 'Y' 
	 		AND b.zeroed_out = 'N' 
	 		ORDER BY clientFirstname, clientLastname, b.id";
	 	//echo $q; 
        $res = $this->my_getAll($q);	
        return $res;
	}
	
/*
	function selectWorkOrderAssigned ($userId) {
	 	$q = "
 			SELECT 
	 		a.id, a.date_expiry, a.amount_deposit, a.amount_bonus, 
	 		c.user_id 			AS clientId, 
	 		c.user_lastname 	AS clientLastname, 
	 		c.user_firstname 	AS clientFirstname, 
	 		d.id				AS assignId,  
	 		e.id				AS workstationId,
	 		e.name				AS workstationName
	 		FROM workorder 		AS a
	 		INNER JOIN users 	AS c ON a.client = c.user_id 
	 		LEFT JOIN workorder_assign AS d ON a.id = d.workorder AND d.user = '".$userId."' 
	 		LEFT JOIN workstation AS e ON c.user_workstation = e.id
	 		WHERE a.expired = 'N'"; //echo $q;
	 	return $this->my_getAll($q);			 
	}
*/
	
	function deleteWorkorderFromPool ($workorderId) {
		$q = "
			UPDATE workorder_assign SET assigned = 'N' 
			WHERE id = $workorderId";  //echo $q; die; 
		$this->make_query($q);	
	}
	
	function selectClient() {
		// todo: maybe do an inner join here on the work orders that pertains to this workstation?
		$q = "
			SELECT user_id, user_name, user_firstname, user_lastname 
			FROM users 
			ORDER BY user_firstname, user_lastname
		";	
		return $this->my_getAll($q);	
	}
	
}