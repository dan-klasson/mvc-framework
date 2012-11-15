<?php

include_once('Module.php');
include_once('DbWorkorder.php');

class Workorder extends Core  {
    
    var $_db;
    var $params;
    var $caching = true;
    var $useSavedResult = false;

    function Workorder(&$smarty, &$core, $params) {

        $this->Core(&$smarty, &$core);
        $this->_db = new DbWorkorder();
        $this->params = $params;
       
        //make sure the user is an admin user for all pages except
        //$publicPage = array ("viewReport", "popUpWorkorderReport", "popUpWorkorder");
		//if ( ! $this->_isPublicPage($publicPage))
		//	die;
    }
    
    function index() {
		$this->setButtonColor("workorder");
 		$this->smarty->display("workorder/index.tpl");
    }
    
    function getWorkorder($id = "", $type = false) {
    	if ($this->_isAdmin())
    		return $this->_db->selectWorkorder($id, $type);
    	else
    		return $this->_db->selectWorkorder($id, $type, $this->myUserInfo['userWorkstation']); 
    }
    
    function getBonusName ($id = "", $type = false) {
    	if ($this->_isAdmin())
    		return $this->_db->selectBonusName($id, $type);
    	else
    		return $this->_db->selectBonusName($id, $type, $this->myUserInfo['userWorkstation']); 
    }

	function select() {
		$this->setButtonColor("workorder");
	
		isset($_REQUEST["expired"]) ? $workorder = $this->getBonusName("", true) : 
				$workorder = $this->getBonusName();
		$this->smarty->assign("workorder", $this->setChangeWorkorderName($workorder));
		$this->smarty->display("workorder/select.tpl");	
	}
	
	function view() {
		$this->setButtonColor("viewWorkorder");
				
		$id = "";
		isset($_REQUEST["id"]) ? $id = $_REQUEST["id"][0] : "";	
		
		if (empty($id)) {	
			$this->smarty->assign("workorder", $this->setChangeWorkorderName($this->getWorkorder()));
		} else {
			$this->smarty->assign("workorder", $this->setChangeWorkorderName($this->getWorkorder($id)));
		}
		$this->smarty->display("workorder/view.tpl");
	}
	
	function edit() { 

		if ( ! $this->_setId()) {
			echo "Error: id lost";
			die;
		}

	    $edit = ModuleStorage::getModule("edit");	
        	
		$this->setButtonColor("workorder");
				
		$this->smarty->assign("casino", $this->db->selectCasino());
		$this->smarty->assign("playType", $this->db->selectPlayType());
		$this->smarty->assign("claimMethod", $this->db->selectClaimMethod());			
		$this->smarty->assign("workorder", $this->setChangeWorkorderName($this->getWorkorder($this->id, true)));
		$this->smarty->assign("workstation", $this->db->getMySqlData("workstation"));
		$this->smarty->assign("user", $this->_db->selectAssignedUsers($this->id));
		$this->smarty->assign("currency", $edit->_db->select("currency"));
		$this->smarty->assign("currencyNotes", $edit->_db->select("currency_notes"));
		
		$this->smarty->display("workorder/edit.tpl");		

	}
	
	function delete() { 
		
		if ( ! $this->_setId())
			return;
		
		$q = "DELETE FROM workorder WHERE id = '".$this->id."'";
		$this->db->make_query($q);
		$this->_redirectUser("index.php?m=workorder&a=select");
	}
	
	function expire () {
		if ( ! $this->_setId())
			return;
		
		$_POST["date_expiry"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "workorder", "id = ".$this->id);
		//echo $q; die;
		$this->db->make_query($q);
		$this->_redirectUser("index.php?m=workorder&a=edit&id=".$this->id);	
	}
	
	function save() {

		$_POST["date_modified"] = "NOW()";
		$_POST["date_expiry"] = $_POST["expiryYear"].$_POST["expiryMonth"].$_POST["expiryDay"].$_POST["expiryHour"].$_POST["expiryMinute"]."00";

		if (isset($_POST["workorderId"]) && is_numeric($_POST["workorderId"])) {
			
			//update the changes
			$q = $this->getMySqlCode($_POST, "workorder", "id = ".$_POST["workorderId"]);
			$this->db->make_query($q);					
				
			//get all current work orders
			$current = $this->_db->selectAssignedUsers($_POST["workorderId"]);
		
			//now we create or delete the work orders specified in the submit form
			$this->setAssignment($current, $_POST["user"], $_POST["workorderId"], "bonus");				
		}
		
		$this->_redirectUser("index.php?m=workorder&a=edit&id=".$_POST["workorderId"]);
	}	
	
	function create() {
		$edit = ModuleStorage::getModule("edit");	
		$this->smarty->assign("casino", $this->db->selectCasino());
		$this->smarty->assign("playType", $this->db->selectPlayType());
		$this->smarty->assign("currencyNotes", $edit->_db->select("currency_notes"));
		$this->smarty->assign("claimMethod", $this->db->selectClaimMethod());
		$this->smarty->assign("country", $this->db->selectCountry());
		
		$this->setButtonColor("workorder");
					
		
		$this->smarty->display("workorder/new.tpl");			
	}		
	
	function insert () {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		
		//the creator of this workorder, is also part of the workordername
		$_POST["creator"] = $this->myUserInfo["userId"];
		
		$_POST["date_expiry"] = $_POST["expiryYear"].$_POST["expiryMonth"].$_POST["expiryDay"].$_POST["expiryHour"].$_POST["expiryMinute"]."00";
		$q = $this->getMySqlCode($_POST, "workorder");
		$this->db->make_query($q);
		
		$this->_redirectUser("index.php?m=workorder&a=edit&id=".mysql_insert_id());
	}
	
	
	function report() {
		$user 	= ModuleStorage::getModule("user", "user/User.php");
		$casino = ModuleStorage::getModule("casino", "casino/Casino.php");
		$edit 	= ModuleStorage::getModule("edit", "edit/Edit.php");
		$workstation = ModuleStorage::getModule("workstation", "workstation/Workstation.php");

		! empty($_POST) ? $this->reportResult() : "";
		
		//$this->smarty->assign("workorder", 	$this->setChangeWorkorderName($this->getWorkorder("", false)));
		$this->smarty->assign("client", 	$user->_db->selectUser()); 
		$this->smarty->assign("workstation", $workstation->_db->selectWorkstation());
		$this->smarty->assign("casino", 	$casino->_db->selectCasino());
		$this->smarty->assign("casinoEngine", $edit->_db->selectCasinoEngine());
		$this->smarty->assign("play", 		$edit->_db->selectPlayType());
		$this->smarty->assign("country", 	$edit->_db->selectCountry());
		
		$this->smarty->assign("deposit", 	$edit->_db->select("deposit"));
		$this->smarty->assign("cashout", 	$edit->_db->select("cashout"));
		$this->smarty->assign("currency", 	$edit->_db->select("currency"));
		$this->smarty->assign("problemType", 	$edit->_db->select("problem_type"));
		$this->smarty->assign("referral", 	$edit->_db->select("referral"));
		
		$this->smarty->assign("siteName", SITE_NAME);
	
		//$this->smarty->assign("workorderCalc", $this->setCalculateWorkorder($this->_db->selectWorkorder()));
		
		$this->setButtonColor("workorderReport");
				
		$this->smarty->display("workorder/report.tpl");	
	}
	function reportResult() { 
	    
		//we'll need to save the post for the setDeposit
		$this->useSavedResult ? $_POST = $_SESSION["post"] : $_SESSION["post"] = $_POST;
		
		//either date span or specific month
		if (isset($_POST["depositedType"]) && $_POST["depositedType"] == "span") {
			$depositedTo = $_POST["depositedToYear"].$_POST["depositedToMonth"].
				$_POST["depositedToDay"].str_replace(":", "", $_POST["depositedToTime"])."00";
				
			$depositedFrom = $_POST["depositedFromYear"].$_POST["depositedFromMonth"].
				$_POST["depositedFromDay"].str_replace(":", "", $_POST["depositedFromTime"])."00";
		}
		else {
			$depositedTo = $_POST["depositedSpecificYear"]."-".$_POST["depositedSpecificMonth"]."-31 99:99:99";
			$depositedFrom = $_POST["depositedSpecificYear"]."-".$_POST["depositedSpecificMonth"]."-01 00:00:00";
		}
		
		//either date span or specific month
		if (isset($_POST["completedType"]) && $_POST["completedType"] == "span") {
			$completedTo = $_POST["completedToYear"].$_POST["completedToMonth"].
				$_POST["completedToDay"].str_replace(":", "", $_POST["completedToTime"])."00";
				
			$completedFrom = $_POST["completedFromYear"].$_POST["completedFromMonth"].
				$_POST["completedFromDay"].str_replace(":", "", $_POST["completedFromTime"])."00";
		}
		else {
			$completedTo = $_POST["completedSpecificYear"]."-".$_POST["completedSpecificMonth"]."-31 99:99:99";
			$completedFrom = $_POST["completedSpecificYear"]."-".$_POST["completedSpecificMonth"]."-01 00:00:00";
		}

		$userWorkstation = $this->_isAdmin() ? "" : $this->myUserInfo["userWorkstation"];
		
		$result = $this->_db->selectReport(
			$_POST["zeroed_out"],
			$_POST["collected_profits"],
			$userWorkstation, 
			$_POST["client"],
			$_POST["user_status"],
			$_POST["workstation"],
			$_POST["casino"],
			$_POST["casinoEngine"],
			$_POST["play"],
			$_POST["country"],
			$_POST["belonging"],
			$_POST["currency"],
			$_POST["deposit"],
			$_POST["cashout"],			
			$_POST["referral"],			
			$_POST["problemType"],			
			$depositedFrom,
			$depositedTo,
			$completedFrom,
			$completedTo, 
			$_POST["limit"]
			);	//print_a($_POST);
		$this->smarty->assign("result", $this->setChangeWorkorderName($this->setChangeWorkorderReport($result)));
		$this->smarty->assign("workorderCalc", $this->setCalculateWorkorder($this->setChangeWorkorderReport($result)));
		
	}	
	
	function popUpWorkorder() {
		$this->smarty->assign("nav", false);
		$this->view();
	}

	function popUpWorkorderReport() {
		$this->smarty->assign("nav", false);
		$this->viewReport();
	}	
	
	function viewReport() {
		if ( ! $this->_setId())
			return;
		$this->smarty->assign("report", $this->setChangeWorkorderName($this->getWorkorder($this->id, false)));
		$this->smarty->display("workorder/view_report.tpl"); 
	}
	
	function _setAssignWorkorderDeposited ($type, $arDeposited) {
		if (is_array($arDeposited)) {
		    print_a($arDeposited);
			foreach ($arDeposited as $k => $v) {
				$arUpdate[$type] = "Y";
				$q = $this->getMySqlCode($arUpdate, "workorder_assign", "id = $v");
				//echo $q."<br>";
				$this->db->make_query($q); 				
			}			
		}
	}
	
	function _setDocsSent ($arrWorkorder, $arrDocsSent) {
		//docs sent flags are different because they can be unchecked
		if (is_array($arrWorkorder) && is_array($arrDocsSent)) {
			//loop all rows of work order ids
			foreach ($arrWorkorder as $v1) {
				//loop all work order ids with docs sent as checked
				$match = false;
				foreach ($arrDocsSent as $v2) {
					//if there's a match we update the flag
					if ($v1 == $v2) {
						$arUpdate = array();
						$arUpdate["docs_sent"] = "Y";
						$arUpdate["date_docs_sent"] = "NOW()";
						$q = $this->getMySqlCode($arUpdate, "workorder_assign", "id = $v1");
						$this->db->make_query($q); 						
						
						$match = true;
					}
				}
				//no match after looping all docs sent, make sure this row has false docs sent
				if ( ! $match) {
					$arUpdate = array();
					$arUpdate["docs_sent"] = "N";
					$q = $this->getMySqlCode($arUpdate, "workorder_assign", "id = $v1");
					$this->db->make_query($q); 	
				}					
			}
		}		
	}
		
	function setCollected () {
		$this->_setAssignCollected($_POST["workorder"], $_POST["profits"], "collected_profits");
		$this->_setAssignCollected($_POST["workorder"], $_POST["funds"], "collected_funds");
		$this->_setDocsSent($_POST['workorder'], $_POST["docs_sent"]);
		$this->useSavedResult = true;
		$this->report();
	}
	
	function setZeroOut ($zeroedOut = true) {
		if ( ! $this->_setId())
			return;		
		$update = $zeroedOut ? "Y" : "N";
		$arUpdate = array("zeroed_out" => $update);
		$q = $this->getMySqlCode($arUpdate, "workorder_assign", "id = $this->id");
		$this->db->make_query($q); 
		if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'popup')
			$this->_redirectUser("index.php?m=myWork&a=editWorkorder&id=".$this->id."&type=".$_REQUEST['type']);
		else 
			$this->_redirectUser("index.php?m=myWork");
	}
	
	function setUndoZeroOut () {
		$this->setZeroOut(false);
	}
	
	
	
    /**
     * setAssignment
     * Creates or deletes multiple work orders per bonus or user
     * @param array 	$current users or bonuses
     * @param array 	$new user checkbox submit value
     * @param int 		$id bonus or user id
     * @param string 	$type bonus or user
     * @param int	 	$workstationId is mandatory when type is user
     */	
	function setAssignment($current, $new, $id, $type = "bonus", $workstationId = "") {
		
		$user = ModuleStorage::getModule("user");
		
		if (is_array($new)) { 
		
			foreach ($current as $currentK => $currentV) {
				
				$delete = 0;
				
				foreach ($new as $newK => $newV) {
					
					$insert = false;
					//if the user or work order id match
					if ($currentV["id"] == $newV || $currentV["user_id"] == $newV) {  
						if (empty($currentV["assignId"])) {
							$insert = true; 
						} 
					} else {
						//count the number of unsuccesful matches
						if ( ! empty($currentV["assignId"])) { 
							$delete++;
						}									
					}	
					if ($insert) {	
						$arrInsert["date_created"] = "NOW()";
						$arrInsert["date_modified"] = "NOW()";
						if ($type == "bonus") {
							//we need to get some info for this client
							$arrUser = $user->_db->selectUserBasic($currentV["user_id"]);
							$arrInsert["workstation"] = $arrUser["user_workstation"];
							// this shouldn't be here? $arrInsert["referral"] = $arrUser["user_referral"];
							
							$arrInsert["workorder"] = $id;
							$arrInsert["client"] = $currentV["user_id"];
							$q = $this->getMySqlCode($arrInsert, "workorder_assign");
						} elseif ($type == "referral") {
							$arrInsert["referral"] = $id;
							$arrInsert["client"] = $currentV["user_id"];
							$q = $this->getMySqlCode($arrInsert, "affiliate_assign");
						    
						} elseif ($type == "user") {
							//we need to get some info for this client
							$arrUser = $user->_db->selectUserBasic($id);
							$arrInsert["referral"] = $arrUser["user_referral"];
														
							$arrInsert["workstation"] = $workstationId;
							$arrInsert["workorder"] = $currentV["id"];
							$arrInsert["client"] = $id;		
							$q = $this->getMySqlCode($arrInsert, "workorder_assign");						
						}
						
						//echo $q."<br>"; 	
						$this->db->make_query($q);	
					} 																	
				}
		
				if (count($new) == $delete) {					
					
					if ($type == "bonus") {
					    $q = "DELETE FROM workorder_assign WHERE ";
						$q .= "client = ".$currentV["user_id"]." AND workorder = '$id'";
					} elseif ($type == "referral") {
					    $q = "DELETE FROM affiliate_assign WHERE ";
						$q .= "client = ".$currentV["user_id"]." AND referral = '$id'";
					} elseif ($type == "user") {
					    $q = "DELETE FROM workorder_assign WHERE ";
						$q .= "workorder = ".$currentV["id"]." AND client = '$id'";
					}
					//echo $q."<br>"; 	
					$this->db->make_query($q);
				}	
			}
		} else {
		    //if new is empty we just delete all 
		    if ($type == "bonus") {
		        $q = "DELETE FROM workorder_assign WHERE workorder = '$id'";
		    } elseif ($type == "referral") {
		        $q = "DELETE FROM affiliate_assign WHERE referral = '$id'";
		    } elseif ($type == "user") {
		        $q = "DELETE FROM workorder_assign WHERE client = '$id'";
		    }
			//echo $q."<br>"; 	
			$this->db->make_query($q);				
		}
	}

	/*
     * Creates or deletes multiple profit or funds collected
     * @param array 	$workorder, an array of all work order id's in the report result
     * @param array 	$collected checkbox submit value with work orders that has profit or funds collected
     * @param string 	$type profit or funds collected
	*/
	
    function _setAssignCollected($workorder, $collected, $type) {
		
		if (is_array($workorder) && is_string($type)) { 
		
			foreach ($workorder as $workorderId) {
				
				$update = 'N';
				
				if (is_array($collected) ) {
				    
    				foreach ($collected as $collectedId) {
    					
    					//if the user or work order id match
    					if ($workorderId == $collectedId) {  
    						$update = 'Y';
    					} 	
    				}
				}
				$q = "UPDATE workorder_assign SET $type = '$update' WHERE id = $workorderId";
				//echo $q."<br>"; 	
				$this->db->make_query($q);
			}
		} 
	}		
	
	function moveBackToWorkPool () {
		if ( ! $this->_setId()) 
			return;
		$this->_db->updateAssigned($this->id);
		$this->_redirectUser("?m=workorder&a=popUpWorkorderReport&id=".$this->id);		
	}
}