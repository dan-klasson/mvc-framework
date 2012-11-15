<?php

include_once("config/config.php");
include_once("Database.php");

class DbWorkorder extends Database {

    function DbWorkorder() {
        // Must call the parent's constructor
        $this->Database();
    }  
    
    function fetchBonusBasic ($id) {
        $q = "
            SELECT 
            id, amount_bonus, amount_to_wager
            FROM workorder 
            WHERE id = '$id'";
        $res = $this->make_query($q);
        return $res->fetchRow();
        
    }
        

    function selectBonusName ($workorder = "", $expired = false, $userWorkstation = "") { 

    	$order_by = " ORDER BY b.name ";
    	
	 	$q = "
 			SELECT DISTINCT a.id, a.amount_bonus, a.amount_deposit, a.date_expiry, 
	 		b.name AS casinoName 
	 		FROM workorder_assign 	  AS wa 
	 		INNER JOIN workorder	  AS a ON wa.workorder = a.id 
	 		LEFT JOIN casino 	      AS b ON a.casino = b.id ";
	 	
        $q .= "WHERE 1 ";
        
	 	if ( ! $expired )
		  $q .= "AND UNIX_TIMESTAMP(a.date_expiry) > '".time()."' ";
		
		if (is_numeric($userWorkstation)) 
			$q .= " AND wa.workstation = '$userWorkstation' "; 
					
	 	if ( ! empty($workorder)) {
	 		$q .= " AND wa.id = '".$workorder."'";	
	        $res = $this->make_query($q.$order_by); 
	        return $res->fetchRow();	 		
		} 	 
	 	return $this->my_getAll($q.$order_by);	
    }
        
    function selectWorkorderName ($workorder = "") { 

	 	$q = "
 			SELECT a.id, a.amount_bonus, a.amount_deposit, a.date_expiry, 
	 		b.name AS casinoName 
	 		FROM workorder 				AS a
	 		LEFT JOIN casino 			AS b ON a.casino = b.id 
	 		LEFT JOIN workorder_assign 	AS c ON a.id = c.workorder 
	 		WHERE 1 ";
	 	if ( ! empty($workorder)) {
	 		$q .= " AND c.id = '".$workorder."'";	
	        $res = $this->make_query($q); 
	        return $res->fetchRow();	 		
		} 	 	
	 	return $this->my_getAll($q);	
    }
    	
	function selectWorkorder ($workorder = "", $bonus = true, $userWorkstation = "") { 

	 	$q = "
 			SELECT 
	 		a.*, wa.*, 
	 		a.id				AS bonusId,
	 		b.id		 		AS casinoId, 
	 		b.name		 		AS casinoName, 
	 		c.user_id 			AS clientId, 
	 		c.user_lastname 	AS clientLastname, 
	 		c.user_firstname 	AS clientFirstname, 
	 		c.user_status		AS clientStatus, 
	 		c.user_referral 	AS clientReferral, 
	 		f.user_id 			AS creatorId, 
	 		f.user_lastname 	AS creatorLastname, 
	 		f.user_firstname 	AS creatorFirstname,	
	 		d.id 				AS playId, 
	 		d.name 				AS play, 
	 		e.name 				AS claimMethodName,
	 		g.login				AS casinoLogin, 
	 		g.password			AS casinoPassword,
	 		h.name				AS casinoEngineName, 
	 		wa.id 				AS workorderId ";
	 	if ($bonus) {
	 		$q .= "
	 		FROM workorder 				AS a 
	 		LEFT JOIN workorder_assign 	AS wa ON a.id = wa.workorder 
	 		LEFT JOIN users 			AS c ON a.client = c.user_id ";
	 	} else {
	 		$q .= ",
	 		wa.currency_type,
	 		wa.deposit_type,
	 		wa.cashout_type,  
	 		c_notes.name 				AS currencyNotes  
	 		FROM workorder_assign 		AS wa 
	 		LEFT JOIN workorder 		AS a ON a.id = wa.workorder 
	 		LEFT JOIN currency_notes 	AS c_notes ON c_notes.id = a.currency_notes  
	 		LEFT JOIN users 			AS c ON wa.client = c.user_id ";
	 	}
	 		$q .= "
	 		LEFT JOIN casino 		AS b ON a.casino = b.id 
	 		LEFT JOIN play 			AS d ON a.play = d.id
	 		LEFT JOIN claim_method 	AS e ON a.claim_method = e.id
	 		LEFT JOIN users 	   	AS f ON a.creator = f.user_id 
	 		LEFT JOIN casino_login 	AS g ON a.casino = g.casino AND wa.client = g.user ";
	 	if (is_numeric($workorder)) 
	 		$q .= "AND (g.workorder = '$workorder' OR g.workorder = 0) ";
	 	$q .= " 
	 		LEFT JOIN casino_engine AS h ON b.casino_engine = h.id 
	 		WHERE 1 ";
		if (is_numeric($userWorkstation)) 
			$q .= " && wa.workstation = '$userWorkstation' "; 	 	
	 	if (is_numeric($workorder)) {
	 		if ($bonus) 
	 			$q .= "&& a.id = '$workorder'";
	 		else 
	 			$q .= "&& wa.id = '$workorder'";	 			
	 		//echo $q."<br>";
	        $res = $this->make_query($q); 
	        return $res->fetchRow();	 		
		} 
		//echo $q."<br>";
	 	return $this->my_getAll($q);		
	}   

	
	function selectWorkOrderAssigned ($userId) {
		
	 	$q = "
 			SELECT 
	 		a.id, a.date_expiry, a.amount_deposit, a.amount_bonus, a.date_created,  
	 		d.user_id 			AS clientId, 
	 		d.user_lastname 	AS clientLastname, 
	 		d.user_firstname 	AS clientFirstname, 
	 		c.id				AS assignId,  
	 		c.type				as workorderType, 
	 		e.id				AS workstationId,
	 		e.name				AS workstationName,
	 		f.name				AS casinoName 
	 		FROM workorder 		AS a
	 		LEFT JOIN workorder_assign 	AS c ON a.id = c.workorder AND c.client = '".$userId."' 
	 		LEFT JOIN users 			AS d ON d.user_id = c.client 
	 		LEFT JOIN workstation 		AS e ON d.user_workstation = e.id
	 		LEFT JOIN casino 	  		AS f ON a.casino = f.id 
	 		
	 		ORDER BY assignId, a.id";  
	 	//echo $q; WHERE c.client = '".$userId."' 
	 	return $this->my_getAll($q);			 
	}	
	
	function selectWorkOrderAssignedByUser ($userId) {
		
	 	$q = "
 			SELECT 
	 		a.id, a.date_expiry, a.amount_deposit, a.amount_bonus, a.date_created,  
	 		d.user_id 			AS clientId, 
	 		d.user_lastname 	AS clientLastname, 
	 		d.user_firstname 	AS clientFirstname, 
	 		c.id				AS assignId,  
	 		c.type				as workorderType, 
	 		e.id				AS workstationId,
	 		e.name				AS workstationName,
	 		f.name				AS casinoName 
	 		FROM workorder 		AS a
	 		INNER JOIN workorder_assign 	AS c ON a.id = c.workorder 
	 		LEFT JOIN users 			AS d ON d.user_id = c.client 
	 		LEFT JOIN workstation 		AS e ON d.user_workstation = e.id
	 		LEFT JOIN casino 	  		AS f ON a.casino = f.id 
	 		WHERE c.client = '".$userId."' 
	 		ORDER BY assignId, a.id";  
	 	//echo $q; 
	 	return $this->my_getAll($q);			 
	}	
	

	function selectAssignedUsers ($workorderId) {
	 	$q = "
			SELECT 
			a.user_id, a.user_name, user_firstname, user_lastname, user_workstation, 
			b.id AS assignId, b.adopted, b.completed, b.deposited   
			FROM users AS a
			LEFT JOIN workorder_assign	AS b 
	 		ON a.user_id = b.client AND b.workorder = '".$workorderId."'"; //echo $q;
	 	return $this->my_getAll($q);			 
	}		

	function selectReport ($zero_out, $collected_profits, $userWorkstation, $client, $userStatus, $workstation, $casino, $casinoEngine, 
		$play, $country, $beloning, $currency, $deposit, $cashout, $referral, $problemType, 
		$depositedFrom, $depositedTo, $completedFrom, $completedTo, $limit) {

		$q = "  SELECT 
				a.*,
				a.id 					AS bonusId,
				clients.user_id 		AS clientId,
				clients.user_firstname 	AS clientFirst,
				clients.user_lastname 	AS clientLast,
				casino.id 				AS casinoId,
				casino.name 			AS casinoName,
				play.name 				AS playName,
				country.name 			AS countryName, 
				currency.code			AS currencyCode,
				currency.rate			AS currencyRate,
				deposit.name            AS depositName, 
				cashout.name            AS cashoutName, 
				workstation.name 		AS workstationName,
				workstationCompleted.name AS workstationCompletedName,
				UNIX_TIMESTAMP(a.date_expiry) AS dateExpiry, 
				wa.*,
				wa.id 					AS workorderId, 
				problem.id				AS problemId, 
				problem.status			AS problemStatus 
				FROM workorder_assign 	AS wa 
				LEFT JOIN workorder 	AS a 		ON wa.workorder = a.id 
				LEFT JOIN users 		AS clients 	ON wa.client = clients.user_id 		
				LEFT JOIN casino 		AS casino	ON a.casino = casino.id		
				LEFT JOIN casino_engine AS casino_engine ON casino.casino_engine = casino_engine.id		
				LEFT JOIN country		AS country	ON clients.user_country = country.id		
				LEFT JOIN play 			AS play		ON a.play = play.id		
				LEFT JOIN workstation 	AS workstation ON wa.workstation = workstation.id 
				LEFT JOIN workstation 	AS workstationCompleted ON wa.workstation_completed = workstationCompleted.id 
				LEFT JOIN currency 		ON wa.currency_type = currency.id 
				LEFT JOIN deposit 		ON wa.deposit_type = deposit.id 
				LEFT JOIN cashout 		ON wa.cashout_type = cashout.id 
				LEFT JOIN problem 		ON wa.id = problem.workorder  
				LEFT JOIN referral 		ON clients.user_referral = referral.id  
				LEFT JOIN problem_type 	ON problem.type = problem_type.id  
				";

		isset($beloning[0]) && $beloning[0] == "wa.completed" ? $belong = "wa.completed_by" : "";
		isset($beloning[0]) && $beloning[0] == "wa.adopted"   ? $belong = "wa.client" : "";
		
		$where = "";
		if (is_numeric($userWorkstation)) 
			$where .= " AND wa.workstation = $userWorkstation"; 
		$where .= $zero_out == 'on' ? "" : " AND wa.zeroed_out = 'N'";
		$where .= $collected_profits == 'on' ? " AND wa.collected_profits = 'Y'" : "";
		count($client)  	== 1 && empty($client[0]) 	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("clients.user_id", $client);
		isset($userStatus) && $userStatus ?  
			$where .= " AND clients.user_status = '$userStatus'" : '';			
		if (SITE_NAME == 'john') {
		    count($workstation) == 1 && empty($workstation[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("wa.workstation_completed", $workstation);
		} else {
		    count($workstation) == 1 && empty($workstation[0])	? "" :    
			$where .= " AND ".$this->getMySqlColumnCode("wa.workstation", $workstation);
		}
		count($casino) 		== 1 && empty($casino[0]) 	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("casino.id", $casino);		
		count($play) 		== 1 && empty($play[0]) 	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("play.id", $play);
		count($country) 	== 1 && empty($country[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("clients.user_country", $country);
		count($currency) 	== 1 && empty($currency[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("currency.id", $currency);
		count($deposit) 	== 1 && empty($deposit[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("deposit.id", $deposit);
		count($cashout) 	== 1 && empty($cashout[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("cashout.id", $cashout);
		count($referral) 	== 1 && empty($referral[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("referral.id", $referral);
		count($casinoEngine) 	== 1 && empty($casinoEngine[0])	? "" : 
			$where .= " AND ".$this->getMySqlColumnCode("casino_engine.id", $casinoEngine);			
		if (count($problemType) != 1 || ! empty($problemType[0])) {
			$where .= " AND ".$this->getMySqlColumnCode("problem_type.id", $problemType);						
			$where .= " AND problem.status != 'solved'";
		}
		$depositedFrom == $depositedTo ? "" : 
			$where .= " AND (wa.date_deposited >= '$depositedFrom' 
			            AND wa.date_deposited <= '$depositedTo') 
			            AND wa.deposited = 'Y'";
		$completedFrom == $completedTo ? "" : 
			$where .= " AND (wa.date_completed >= '$completedFrom' 
			            AND wa.date_completed <= '$completedTo') 
			            AND wa.completed = 'Y'";
						
		empty($where) ? "" : $q .= "WHERE ".substr($where, 4);
		
		$q .= " ORDER BY wa.completed DESC, wa.collected_funds, wa.id ASC";

		empty($limit) ? "" : $q .= " LIMIT 0, $limit ";
		
		//echo $where;
		//echo nl2br($q);
		return $this->my_getAll($q);		
	}
	
	function selectWorkorderByUser($userId) {
		$q = "SELECT id FROM workorder_assign WHERE client = '$userId'";
        return $this->my_getAll($q);			
	}
	
	function selectWorkorderCompletedStatus ($workorderId) {
	    $q = "SELECT workorder, completed FROM workorder_assign WHERE id = '$workorderId'";
        $res = $this->make_query($q); 
        return $res->fetchRow();  
	}
	
	function updateAssigned ($workorderId, $assigned = true) {
		$assigned = $assigned ? "Y" : "N";
		$q = "
			UPDATE workorder_assign SET assigned = '$assigned' 
			WHERE id = '$workorderId'";  //echo $q; die; 
		$this->make_query($q);	
	}
	
	function updateAssignedByUser($userId, $assigned = true) {
		$assigned = $assigned ? "Y" : "N";
		$q = "
			UPDATE workorder_assign SET assigned = '$assigned' 
			WHERE client = '$userId'";  //echo $q; die; 
		$this->make_query($q);
	}
	
	function deleteWorkorderByClient ($userId) {
		$q = "
			DELETE FROM workorder_assign 
			WHERE client = '$userId'"; 
		//echo $q;
		$this->make_query($q);			
	}
	
	function updateWorkorderWorkstation($workorderId, $workstationId) {
		$q = "
			UPDATE workorder_assign SET workstation = '$workstationId' 
			WHERE id = '$workorderId'";  
		//echo $q; 
		$this->make_query($q);	    
	}
		
/*	
	function selectBonusUser($user) {
		$q = "
			SELECT a.id 
			FROM workorder 			AS a 
			INNER JOIN workstation 	AS b ON a.workstation = b.id 
			INNER JOIN users		AS c ON c.workstation = b.id
			WHERE c.user_id = '$user'";
		return $this->my_getAll($q);			
	}
*/	
}				
?>
