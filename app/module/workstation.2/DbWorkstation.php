<?php

include_once("config/config.php");
include_once("Database.php");

class DbWorkstation extends Database {

    function DbWorkstation() {
        // Must call the parent's constructor
        $this->Database();
    }  

	function selectWorkstation ($workstationId = "") { 
		$q = "SELECT * FROM workstation ";
		if (is_numeric($workstationId)) {
			$q .= "WHERE id = ".$workstationId;
	        $res = $this->make_query($q);
	        return $res->fetchRow();	
		}
		$q .= " ORDER BY name";
        return $this->my_getAll($q);			
	}
	

/*
	function selectAssignedWorkstation ($userId) { 
		$q = "SELECT a.id, a.name, b.user FROM workstation AS a 
			LEFT JOIN workstation_assign AS b 
			ON a.id = b.workstation AND b.user = $userId"; 
        return $this->my_getAll($q);			
	}	
*/
	function deleteWorkstation ($workstationId) {
		if (is_numeric($workstationId)) {
			$q = "DELETE FROM workstation WHERE id = ".$workstationId;
			$this->make_query($q); 
		}
	}
/*	
	function selectWorkstationAssigned($workstationId = "") { 
		$q = "
			SELECT 
			b.id   AS workstationId, 
			b.name AS workstationName, 
			FROM workstation_assign
			LEFT JOIN workstation ON a.workstation = b.id ";
		if (is_numeric($workstationId)) {
			$q .= "WHERE b.id = ".$workstationId;
	        $res = $this->make_query($q);
	        return $res->fetchRow();	
		}
        return $this->my_getAll($q);			
	}
*/
}
?>