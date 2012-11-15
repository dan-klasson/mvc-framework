<?php

include_once("config/config.php");
include_once("Database.php");

class DbUser extends Database {
	
	var $order_by = "user_firstname";
	
	function DbUser () {
        // Must call the parent's constructor
        $this->Database();		
	}
	
	function selectUserBasic ($userId = "", $status = "", $workstation = "") {
		$q = "
			SELECT user_id, user_name, user_firstname, user_lastname, user_workstation, user_referral  
			FROM users 
			WHERE 1 ";
		$q .= !empty($status) ? "&& user_status = '$status'" : "";
		$q .= !empty($workstation) ? "&& user_workstation = '$workstation'" : "";
		
		$order = " ORDER BY user_firstname, user_lastname";
		
		if (is_numeric($userId)) {
			$q .= " && user_id = ".$userId; //echo $q;
	        $res = $this->make_query($q.$order); 
	        return $res->fetchRow();		
		} 
		return $this->my_getAll($q.$order);	
	}

	function selectUser($userId = "", $userAccess = "", $userWorkstation = "") {
		
		$q = "
			SELECT 
			user_id, 
			user_name, 
			user_firstname, 
			user_lastname,
			user_workstation, 
			country.name AS countryName,
			country.id AS countryId, 
			currency.name AS currencyName, 
			currency.id AS currencyId 
			FROM users 
			LEFT JOIN country ON country.id = user_country
			LEFT JOIN currency ON currency.id = user_currency 
			LEFT JOIN referral ON referral.id = user_referral 
			WHERE 1 ";
		
		$order = " ORDER BY user_firstname";

		if (is_numeric($userWorkstation)) {
			$q .= " && user_workstation = ".$userWorkstation;
		}		
		if (is_numeric($userId)) {
			//$userId param is passed so we only select one row
			$q .= " && user_id = ".$userId;
	        $res = $this->make_query($q.$order); 
	        return $res->fetchRow();		
		} 	
		if (is_array($userAccess)) {
			$q .= " && ";
			foreach ($userAccess as $key => $val) {
				$q .= " user_access = '$val' OR";
			}
			$q = substr($q, 0, -2);
		}					
		//echo $q;		
		return $this->my_getAll($q.$order);			
	}
	
	function selectUserAll($userId = "") {
		
		$q = "
			SELECT user.*, 
			country.name AS countryName,
			country.id AS countryId,
			currency.name AS currencyName, 
			currency.id AS currencyId, 			 
			workstation.id AS workstationId, 			 
			workstation.name AS workstationName,
			referral.name AS referralSite,
			deposit_method.name AS depositMethod,
			withdrawal_method.name AS withdrawalMethod  
			FROM users AS user 
			LEFT JOIN country  ON country.id  = user.user_country 
			LEFT JOIN currency ON currency.id = user_currency 
			LEFT JOIN workstation ON workstation.id = user_workstation 
			LEFT JOIN deposit_method ON deposit_method.id = user_deposit_method 
			LEFT JOIN withdrawal_method ON withdrawal_method.id = user_withdrawal_method 
			LEFT JOIN referral ON referral.id = user_referral";
		
		if (is_numeric($userId)) {
			//$userId param is passed so we only select one row
			$q .= " WHERE user.user_id = ".$userId;
	        $res = $this->make_query($q); 
	        //echo $q;
	        return $res->fetchRow();		
		} 
		$q .= " ORDER BY $this->order_by ";
		return $this->my_getAll($q);			
	}	
	
	function selectUserFinancial ($userId) {
		
		$q = "
			SELECT 
			user_id, 
			user_firstname,
			user_lastname,
			user_eco_balance, 	 	 	 	 	
 	 		user_nt_balance, 	 	 	 	 	 	
 	 		user_eco_modified, 	 	 	 	 	 	 	
 	 		user_nt_modified 			
			FROM users 
			WHERE user_id = '$userId' 
		";	
        $res = $this->make_query($q); 
        return $res->fetchRow();	
	}
	
	function deleteUser ($userId) {
		$q = "DELETE FROM users WHERE user_id = '$userId'";	
		$this->make_query($q);
		return;
	}
	
	
	function selectEmailTemplates($userId) {
		$q = "SELECT 
			user_id, 
			user_firstname,
			user_lastname,
			user_email_tpl_1,
			user_email_tpl_2,
			user_email_tpl_3,
			user_email_tpl_4,
			user_email_tpl_5
			FROM users 
			WHERE user_id = '$userId'
			";	
		$res = $this->make_query($q); 
	    return $res->fetchRow();		
	}
}
