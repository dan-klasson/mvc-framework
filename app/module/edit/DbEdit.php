<?php
/*
 * $Id: 
 */

include_once("config/config.php");
include_once("Database.php");

class DbEdit extends Database {
	
	function DbEdit () {
        // Must call the parent's constructor
        $this->Database();		
	}
	
	function select($type, $id = "") {
		
		$q = "SELECT * FROM $type ";

		if (is_numeric($id)) {
			$q .= " WHERE id = $id ";
	        $res = $this->make_query($q); 
	        return $res->fetchRow();	
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}	
	
	function selectPaymentType($paymentTypeId = "") {
		
		$q = "SELECT * FROM payment_type ";
		
		if (is_numeric($paymentTypeId)) {
			$q .= " WHERE id = ".$paymentTypeId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}	
	
	function selectCasinoEngine($casinoEngineId = "") {
		
		$q = "SELECT * FROM casino_engine ";
		
		if (is_numeric($casinoEngineId)) {
			$q .= " WHERE id = ".$casinoEngineId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}
	
	function selectClaimMethod($claimMethodId = "") {
		
		$q = "SELECT * FROM claim_method ";
		
		if (is_numeric($claimMethodId)) {
			$q .= " WHERE id = ".$claimMethodId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}

	function selectPlayType($playTypeId = "") {
		
		$q = "SELECT * FROM play ";
		
		if (is_numeric($playTypeId)) {
			$q .= " WHERE id = ".$playTypeId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}		
	
	function selectCountry($countryId = "") {
		
		$q = "SELECT * FROM country ";
		
		if (is_numeric($countryId)) {
			$q .= " WHERE id = ".$countryId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}	
	
	function selectSiteSetting() {
		
		$q = "SELECT * FROM site_setting ";
		$res = $this->make_query($q); 
	    return $res->fetchRow();	
	}
	
	function selectWorkstation($workstationId = "") {
		
		$q = "SELECT * FROM workstation ";
		
		if (is_numeric($workstationId)) {
			$q .= " WHERE id = ".$workstationId;
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}	
	function selectBgColor($bgColorId = "") {
		
		$q = "SELECT * FROM bg_color ";
		
		if (is_numeric($bgColorId)) {
			$q .= " WHERE id = $bgColorId ";
	        $res = $this->make_query($q); 
	        return $res->fetchRow();		
		} 	
		$q .= " ORDER BY name";
		return $this->my_getAll($q);			
	}			
}
?>