<?php

include_once("config/config.php");
include_once("Database.php");

class DbAffiliate extends Database {
	
	function DbAffiliate () {
        // Must call the parent's constructor
        $this->Database();		
	}

	function selectAffiliate($affiliateId = "") {
		
		$q = "SELECT a.*, 			
		b.id AS casinoId, 	   b.name AS casinoName,
		c.id AS paymentTypeId, c.name AS paymentTypeName
		FROM affiliate AS a 
		LEFT JOIN casino AS b ON a.casino = b.id 
		LEFT JOIN payment_type  AS c ON a.payment_type  = c.id 	";
			
		if (is_numeric($affiliateId)) {		
			$q .= "WHERE a.referral = ".$affiliateId;
		}
		//echo $q;
		return $this->my_getAll($q);		
	}
	
	function selectReferral ($referralId = "") {
	    $q = "SELECT r.id, r.name 
	       FROM referral AS r ";
	    
		if (is_numeric($referralId)) {		
			$q .= "WHERE r.id = ".$referralId;
    	    $res = $this->make_query($q); 
    	    return $res->fetchRow();			
		}
		return $this->my_getAll($q);	    
	}

	function selectAffiliateByCasino($casinoId) {
		
		//select one row
		if (is_numeric($casinoId)) {
			$q = "SELECT a.*, 			
			b.id AS referralId, 	   b.name AS referralName,
			c.id AS paymentTypeId, c.name AS paymentTypeName
			FROM affiliate AS a 
			LEFT JOIN referral AS b ON a.referral = b.id 
			LEFT JOIN payment_type  AS c ON a.payment_type  = c.id 			
			WHERE a.casino = ".$casinoId;
			//echo $q;
			return $this->my_getAll($q);		
		}
	}	
	
	function selectAffiliateByReferral($referralId) {
	    $q = "SELECT * FROM affiliate WHERE referral = '$referralId'";
	    return $this->my_getAll($q);	    
	}

	function selectAssignedUsers ($referralId) {
	 	$q = "
			SELECT 
			a.user_id, a.user_name, user_firstname, user_lastname, user_workstation, 
			b.id AS assignId 
			FROM users AS a
			LEFT JOIN affiliate_assign	AS b 
	 		ON a.user_id = b.client AND b.referral = '".$referralId."'"; 
	 	 //echo $q;
	 	return $this->my_getAll($q);			 
	}		
	
	function getReferral($id) {
	    $q = "SELECT referral.*, u.user_id, u.user_firstname, u.user_lastname 
	          FROM referral 
	          LEFT JOIN users AS u ON u.user_id = referral.user 
	          WHERE referral.id = '$id'";   
	    $res = $this->make_query($q); 
	    return $res->fetchRow();		    
	}
		
}
