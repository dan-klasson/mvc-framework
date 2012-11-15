<?php

include_once('Module.php');
include_once('DbUser.php');

class User extends Core {
 	
	var $_db; //casino database object
    var $params;
    var $caching = true;	
	
	function User (&$smarty, &$core, $params) {
		$this->Core(&$smarty, &$core);
		$this->_db = new DbUser();	
	}
	
	//todo: rename to index
	function index() {
		
		//make sure the user is an admin user
		//if ( ! $this->_isAdmin())
		//	return;
				
		isset($_REQUEST["p"]) ? $p = $_REQUEST["p"] : $p = "";
		
		if ($p == "editUser" && is_numeric($this->id)) {

			if ( ! $this->_setId())
				return;
				
				
			$edit = ModuleStorage::getModule("edit", "edit/Edit.php");
			$this->smarty->assign("currency", $edit->_db->select("currency")); 				
			$this->smarty->assign("depositMethod", $edit->_db->select("deposit_method")); 				
			$this->smarty->assign("withdrawalMethod", $edit->_db->select("withdrawal_method")); 				
					
			$workstation = ModuleStorage::getModule("dbworkstation", "workstation/DbWorkstation.php");
			$this->smarty->assign("workstation", $workstation->selectWorkstation());
			
			$workorder = ModuleStorage::getModule("dbworkorder", "workorder/DbWorkorder.php");
			$this->smarty->assign("workorder", $this->setChangeWorkorderName($workorder->selectWorkOrderAssigned($this->id)));
			
			$this->smarty->assign("country", $this->db->getMySqlData("country"));
			
			$arUser = $this->_db->selectUserAll($this->id);
			$this->smarty->assign("user", $arUser);
			
			$this->smarty->assign("referral", $edit->_db->select("referral"));
			
		} 
		else if ($p == "selectUser") {
			if ($this->_isAdmin())
				$arUser = $this->_db->selectUser("", $_REQUEST["access"]) ;
			else
				$arUser = $this->_db->selectUser("", $_REQUEST["access"], $this->myUserInfo['userWorkstation']) ;
			$this->smarty->assign("user", $arUser);
			
		} elseif ($p == "newUser") {
			
		    $edit = ModuleStorage::getModule("edit");
		    $workstation = ModuleStorage::getModule("workstation");
			$this->smarty->assign("currency", $edit->_db->select("currency")); 			
			
			//we need to pass some empty array values to this page
			$arParam = $this->_getColumnName("users");
			
		    $this->smarty->assign("workstation", $workstation->_db->selectWorkstation());
		    
		    $this->smarty->assign("user", $arParam);
		    $this->smarty->assign("country", $this->db->getMySqlData("country"));
		    $this->smarty->assign("referral", $edit->_db->select("referral"));
		    
		} else if ($p == "viewAll") {
			$this->_db->order_by = "workstationName, user_firstname";
			$this->smarty->assign("users", $this->_db->selectUserAll());	
		}
		$this->setButtonColor("user");
		
		$this->smarty->display("user/user.tpl");
	}
	
	function viewUser() {
		if ( ! $this->_setId())
			return;	
		
		$this->setButtonColor("viewClient");
				
		
		isset($this->id) && is_numeric($this->id) ?
			$arUser = $this->_db->selectUserAll($this->id) :
			$arUser = $this->_db->selectUser() ;

		$this->smarty->assign("user", $arUser);
		$this->smarty->display("user/view_client.tpl"); 
	}	
	
	function popUpUser() {
		$this->smarty->assign("nav", false);
		$this->viewUser();
	}
	
	function popUpEmailTemplates () {
		if ($this->_setId() && $this->_isAdmin()) {
			$this->smarty->assign("emailTemplates", $this->_db->selectEmailTemplates($this->id));
			$this->smarty->display("user/email_template.tpl");		
		}
	}
	
	function saveEmailTemplates () {
		if ($this->_setId() && $this->_isAdmin()) {
			//update the user
			$arrUpdate = array();
			$arrUpdate["user_modified"] = "NOW()";
			for ($i=1; $i<=5; $i++) {
				$arrUpdate["user_email_tpl_$i"] = $_POST["user_email_tpl_$i"];
			}
			$q = $this->getMySqlCode($arrUpdate, "users", "user_id = ".$this->id);
			$this->db->make_query($q);

			$this->_redirectUser("index.php?m=user&a=popUpEmailTemplates&id=".$this->id);			
		}		
	}
	
	function saveUser() {
		if ($this->_setId()) {
			
			//we need this object
			$workorder  = ModuleStorage::getModule("workorder");
			
			//get all work orders
			$current = $workorder->_db->selectWorkOrderAssigned($this->id);
			
			//get this user
			$user = $this->_db->selectUserBasic($this->id);
			
			//if the workstation was changed we got to update all workstation ids for this user.
			if ($user['user_workstation'] != $_POST["user_workstation"]) {
			    //get all work orders assigned to this user
			     $arrUpdate = $workorder->_db->selectWorkorderByUser($this->id);
			     foreach ($arrUpdate as $key => $value) {
			         $workorder->_db->updateWorkorderWorkstation($value['id'], $_POST["user_workstation"]);
			     }
			     
			}
			//now we create or delete the work orders specified in the submit form
			$workorder->setAssignment($current, $_POST["workorder"], $this->id, "user", $_POST["user_workstation"]);

			//update the user
			$_POST["user_modified"] = "NOW()";
			$_POST["user_birth"] = $_POST["birthYear"].$_POST["birthMonth"].$_POST["birthDay"];	
						
			$q = $this->getMySqlCode($_POST, "users", "user_id = ".$this->id);
			$this->db->make_query($q);

			$this->_redirectUser("index.php?m=user&p=editUser&id=".$this->id);
		}		
	}
	
	function insertUser() {
			
		$_POST["user_id"]		= "null";
		$_POST["user_modified"] = "NOW()";
		$_POST["user_created"]  = "NOW()";
		$_POST["user_birth"] 	= $_POST["birthYear"].$_POST["birthMonth"].$_POST["birthDay"];			
		
		if ( empty ( $_POST["user_workstation"] ) )
		    $_POST["user_workstation"] = $this->myUserInfo['userWorkstation'];			
		    
		$q = $this->getMySqlCode($_POST, "users"); //print_a($_POST);echo $q; die;
		$this->db->make_query($q);
		$user = mysql_insert_id();
		/* disable multiple workstations per user
		foreach ($_POST["user_workstation"] as $k => $v) {
			$q = $this->getMySqlCode(array("user" => $user, "workstation" => $v), "workstation_assign");
			$this->db->make_query($q);	
		}
		*/
		$this->_redirectUser("index.php?m=user&p=editUser&id=".$user);
	}

	function deleteUser() {
		if ($this->_setId()) {
			$this->_db->deleteUser($this->id);
			//delete any work orders assigned to this client too
			$workorder  = ModuleStorage::getModule("workorder");
			$workorder->_db->deleteWorkorderByClient($this->id);
		}
		$this->_redirectUser("index.php?m=user");	
	}
	
	function viewClient() { 
		$this->_setId();
		
		$this->setButtonColor("viewClient");
		
		if ($this->_isAdmin()) {
			$this->id ? $arUser = $this->_db->selectUserAll($this->id) : 
					$arUser = $this->_db->selectUser() ;
		} else {
			$this->id ? $arUser = $this->_db->selectUserAll($this->id, "", $this->myUserInfo['userWorkstation']) : 
					$arUser = $this->_db->selectUser("", "", $this->myUserInfo['userWorkstation']) ;
		}
			
		$this->smarty->assign("user", $arUser);
		$this->smarty->display("user/view_client.tpl"); die;
	}	
	
	function setMDeposit() {
		$this->_setId();
		$_POST["user_m_deposit_date"]  = "NOW()";
		
		$q = $this->getMySqlCode($_POST, "users", "user_id = '$this->id'");
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=user&p=editUser&id=".$this->id);
	}
	
	function setDeposit() {
		$this->_setId();
		$_POST["user_deposit_date"]  = "NOW()";
		$q = $this->getMySqlCode($_POST, "users", "user_id = '$this->id'");
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=user&p=editUser&id=".$this->id);
	}	

}
