<?php

include_once("config/config.php");
include_once("Database.php");

class DbAdmin extends Database {

    function DbAdmin() {
        // Must call the parent's constructor
        $this->Database();
    }
    
    function fetchProblem($problemId = "") {
    	$q = "
    		SELECT p.*, t.name, e.name AS casinoName, 
    		u.user_id, u.user_firstname, u.user_lastname     
    		FROM problem AS p 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		LEFT JOIN casino_engine AS e ON p.casino = e.id     		
    	";
    	if ( ! empty($problemId)) {
    		$q .= "WHERE p.id = '$problemId' ";	
	    	$res = $this->make_query($q);
	    	return $res->fetchRow();    		
    	}
    	$q .= " ORDER BY priority";
    	return $this->my_getAll($q);
    }
    
    function fetchHighPriority($userId) {
    	$q = "
    		SELECT p.*, t.name, u.user_firstname, u.user_lastname 
    		FROM problem AS p 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		WHERE user = '$userId' 
    		AND status != 'solved' 
    		ORDER BY priority DESC
    		LIMIT 0, 5";
    	return $this->my_getAll($q);	
    }
    
    function fetchProblemsBasic($type = "", $user = "", $status = "", $workstation = "") {
    	$q = "
    		SELECT p.id, p.type, t.name,   
    		u.user_id, u.user_firstname, u.user_lastname  
    		FROM problem AS p 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		WHERE (status = 'open' || status = 'actioned' ";
    	$q .= !empty($status) && $status == "on" ? " || status = 'solved') " : ")";
    	$q .= !empty($type) && $type 	!= 0 ? " && type = '$type' " : "";
    	$q .= !empty($user) && $user 	!= 0 ? " && user = '$user' " : "";
    	$q .= !empty($workstation) && $workstation 	!= 0 ? " && workstation = '$workstation' " : "";
    	$q .= " ORDER BY priority";
		return $this->my_getAll($q);
    }    
    
    function fetchProblemByUser ($userId) {
    	$q = "
    		SELECT p.*, t.name, e.name AS casinoName, 
    		u.user_id, u.user_firstname, u.user_lastname     
    		FROM problem AS p 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		INNER JOIN casino_engine AS e ON p.casino = e.id    
    		WHERE user = '$userId' 
    		ORDER BY priority 
    	";
    	//echo $q;
    	$res = $this->make_query($q);	
    	return $this->my_getAll($q);    	
    }
    
    function fetchProblemByWorkstation($workstationId) {
    	$q = "  	
    		SELECT p.id, p.type, p.status, t.name, p.workorder, 
    		u.user_id, u.user_firstname, u.user_lastname   	
    		FROM problem AS p 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		WHERE p.workstation = '$workstationId' 
    	";  
    	//echo $q;
    	return $this->my_getAll($q);   	
    }  
    
    
    function fetchProblemByWorkorder($workorderId) {
    	$q = "
    		SELECT p.*, t.name, 
    		u.user_id, u.user_firstname, u.user_lastname     
    		FROM problem AS p 
    		INNER JOIN problem_type AS t ON p.type = t.id 
    		INNER JOIN users AS u ON p.user = u.user_id 
    		WHERE p.workorder = '$workorderId' ";	
	    	$res = $this->make_query($q);
	    	return $res->fetchRow();    		
    }
        
    
    function fetchEmailsByProblem ($problemId) {
    	$q = "SELECT * FROM email WHERE problem = '$problemId'";	
    	return $this->my_getAll($q);
    }  
    
    function fetchEmail ($emailId = "") {
    	$q = "SELECT * FROM email WHERE id = '$emailId'";
    	$res = $this->make_query($q);	
    	return $res->fetchRow();
    }      
}
?>