<?php
/*
 * $Id: 
 */

include_once('Module.php');
include_once('DbAdmin.php');

class Admin extends Module  {
 	
	var $_db; //database object
    var $params;
    var $caching = true;	
	
	function Admin (&$smarty, &$core, $params) {
		$this->Module(&$smarty, &$core);
		$this->_db = new DbAdmin();	
		
		//make sure the user is an admin user
		if ( ! $this->_core->_isAdmin() && ! $this->_core->_isModerator())
			return;
			
		$this->_core->setButtonColor("adminCenter");
	}

	function index() {
		//$this->_setId();
		$this->editProblem();
	}
	
	function viewProblem() {
		$this->editProblem();
	}
	
	
	function editProblem() {
		
		$edit = ModuleStorage::getModule("edit");
		$user = ModuleStorage::getModule("user");
		$casino = ModuleStorage::getModule("casino");
		$workstation = ModuleStorage::getModule("workstation");
		$workorder = ModuleStorage::getModule("workorder");
		
		if (isset($_POST['filter']) && $_POST['filter'] == "true") {
			$this->_smarty->assign("problemPool", $this->_db->fetchProblemsBasic($_POST['filterType'], $_POST['filterUser'], $_POST['filterSolved'], $_POST['filterWorkstation']));
		} else {
			$this->_smarty->assign("problemPool", $this->_db->fetchProblemsBasic());
		}
		$this->_smarty->assign("problemType", $edit->_db->select("problem_type"));
		$this->_smarty->assign("user", $user->_db->selectUser()); 		
		$this->_smarty->assign("casino", $casino->_db->selectCasinoEngine()); 		
		$this->_smarty->assign("workstation", $workstation->_db->selectWorkstation()); 		
		
		if ($this->_core->_setId()) {
			$this->_smarty->assign("emails", $this->_db->fetchEmailsByProblem($this->_core->id));
			
			$arrProblem = $this->_db->fetchProblem($this->_core->id);
			$this->_smarty->assign("problem", $arrProblem);
			if ($arrProblem['workorder'])
				$this->_smarty->assign("workorder", $workorder->setChangeWorkorderName($workorder->getWorkorder($arrProblem['workorder'])));
		} elseif (isset($_GET['workorder'])) {
			$this->_smarty->assign("workorder", $workorder->setChangeWorkorderName($workorder->getWorkorder($_GET['workorder'])));
		}
		
		$this->_core->setButtonColor("adminCenter");
		if (isset($_GET['popup'])) 
			$this->_smarty->assign('nav', false);		
		$this->_smarty->display("admin/index.tpl");
	}
	
	function changeProblem() { 
		if ($this->_core->_setId()) { 
    		$_POST['date_phone_contact'] = $_POST['phoneYear'].$_POST['phoneMonth'].$_POST['phoneDay'];
    		$_POST['date_email_sent'] = $_POST['emailYear'].$_POST['emailMonth'].$_POST['emailDay'];
			
			$q = $this->_core->getMySqlCode($_POST, "problem", "id = '".$this->_core->id."'");
			echo $q; print_a($_POST); die;
			if ($this->_db->make_query($q))
				$this->_core->_redirectUser("index.php?m=admin&a=viewProblem&id=".$this->_core->id);
		}
		$this->_core->_error();		
	}
	
	function newProblem() {
		//$this->_smarty->display("admin/index.tpl");
		$this->editProblem();
	}	
	
	function insertProblem () {
	    
		//fix dates and checkboxes
		if ($_POST['phone_contact'] == 'on') {
    		$_POST['date_phone_contact'] = $_POST['phoneYear'].$_POST['phoneMonth'].$_POST['phoneDay'];
    		$_POST['phone_contact'] = 'Y';
		}
    	if ($_POST['email_sent'] == 'on') {
    		$_POST['date_email_sent'] = $_POST['emailYear'].$_POST['emailMonth'].$_POST['emailDay'];
    		$_POST['email_sent'] = 'Y';
    	}
    				
		//get the users workstation
		$user = ModuleStorage::getModule("user");
		$userInfo = $user->_db->selectUserBasic($_POST['user']);
		$_POST['workstation'] = $userInfo['user_workstation'];
		$q = $this->_core->getMySqlCode($_POST, "problem");
		//echo $q; die;
		if ($this->_db->make_query($q)) {
			if ($this->_core->_isAdmin())
				$this->_core->_redirectUser("index.php?m=admin&a=viewProblem&id=".mysql_insert_id());
			else 
				$this->_core->_redirectUser("index.php?m=myWork&a=editWorkorder&id=".$_POST['workorder']);
		}
		else 
			$this->_core->_error();
	}
	
	function sendEmail() {
		if (empty($_POST['recipient']) || empty($_POST['subject']) || empty($_POST['body'])) 
			$this->_core->_error("You must supply all fields.");
		else {
			$this->_core->_setId();
			$headers = 'From: info@50grand.com' . "\r\n" .
			   'Reply-To: info@50grand.com' . "\r\n" .
			   'X-Mailer: PHP/' . phpversion();				   	
			mail($_POST['recipient'], $_POST['subject'], $_POST['body'], $headers);	
			$_POST['problem'] = $this->_core->id;
			$q = $this->_core->getMySqlCode($_POST, "email");
			$this->_db->make_query($q);		
			$this->_core->_redirectUser("index.php?m=admin&a=viewProblem&id=".$this->_core->id);
		}
	}

	function viewEmail() {
		$this->_core->_setId();
		$this->_smarty->assign('nav', false);
		$this->_smarty->assign("email", $this->_db->fetchEmail($this->_core->id));
		$this->_smarty->display("admin/view_email.tpl");
	}	
	
	function solveProblem() {
		$this->_core->_setId();
		$this->changeProblemStatus("solved");
		$this->_core->_redirectUser("?m=admin&a=viewProblem&id=".$this->_core->id);
	}
	
	function actionProblem() {
		$this->_core->_setId();
		$this->changeProblemStatus("actioned");
		$this->_core->_redirectUser("?m=myWork");
	}
	
	function resendProblem() {
		$this->_core->_setId();
		$this->changeProblemStatus("open");
		$this->_core->_redirectUser("?m=admin&a=viewProblem&id=".$this->_core->id);
	}
	
	function changeProblemStatus($status) {
		$update['status'] = $status;
		$this->_db->_sql->autoExecute("problem", $update, DB_AUTOQUERY_UPDATE, "id = '".$this->_core->id."'");
	}
	
	function checkProfitAndDeposit() {
		$this->_core->_setId();
		$workorder = ModuleStorage::getModule("workorder");
		$workorder->_setAssignWorkorderDeposited("collected_profits", array(1 => $this->_core->id));
		$workorder->_setAssignWorkorderDeposited("collected_funds", array(1 => $this->_core->id));	
		$this->_core->_redirectUser("?m=admin&a=viewProblem");
	}
	
	
	//client center
	
	function editUser() {
		
		$casino = ModuleStorage::getModule("casino", "casino/Casino.php");
		$edit 	= ModuleStorage::getModule("edit", "edit/Edit.php");
		$user = ModuleStorage::getModule("user");	
		$workstation = ModuleStorage::getModule("workstation");	
		$workorder = ModuleStorage::getModule("workorder");
		
		$this->_smarty->assign("casino", 	$casino->_db->selectCasino());
		$this->_smarty->assign("play", 		$edit->_db->selectPlayType());	
		$this->_smarty->assign("workstation", $workstation->_db->selectWorkstation());	
		
		if ($this->_core->_setId()) {
		    
		    $this->_smarty->assign("id", $this->_core->id);
			
			$this->_smarty->assign("problems", $this->_db->fetchProblemByUser($this->_core->id));
			$this->_smarty->assign("workorder", 
				$this->_core->setChangeWorkorderName($workorder->_db->selectWorkOrderAssignedByUser($this->_core->id)));
			$this->_smarty->assign("user", $user->_db->selectUserAll($this->_core->id));			
		}
		
		// user status OR/AND workstation filter
		if ($_POST['user_status'] || $_POST['user_workstation']) {
			$this->_smarty->assign("users", $user->_db->selectUserBasic(
				"", 	//empty userId
				$_POST['user_status'] ? $_POST['user_status'] : '',
				$_POST['user_workstation'] ? $_POST['user_workstation'] : ''
			));
		}
		// no filter applied					
		else 
			$this->_smarty->assign("users", $user->_db->selectUserBasic());
			
	
		$this->_core->setButtonColor("adminCenter");
		$this->_smarty->display("admin/index.tpl");
	}
		
	function changeUserProblem () {

		if ($this->_core->_setId() && isset($_POST['problemId'])) {
			//email sent
			if ($_POST['email_sent'] == 'on') {
				$update['email_sent'] = "Y";
				$update['date_email_sent'] = "NOW()";
			} else {
				$update['email_sent'] = "N";
			}
			//phone contact
			if ($_POST['phone_contact'] == 'on') {
				$update['phone_contact'] = "Y";
				$update['date_phone_contact'] = "NOW()";
			} else {
				$update['phone_contact'] = "N";
			}	
			//assign_workstation
			if ($_POST['assign_workstation'] == 'on') {
				//get the workstation of chosen user.
				$user = ModuleStorage::getModule("user");
				$arrUser = $user->_db->selectUserBasic($_POST['user']);
				$workstation = $arrUser['user_workstation'];
				$update['workstation'] = $workstation;
			} else 
				$update['workstation'] = 0;
					
			$update['date_modified'] = "NOW()";
			$update['notes'] = $_POST['problemNotes'];
			$_POST['solved'] == 'true' ? $update['status'] = "actioned" : "";
			$q = $this->_core->getMySqlCode($update, "problem", "id = '".$_POST['problemId']."'");
			$this->_db->make_query($q);			
			//$this->_db->_sql->autoExecute("problem", $update, DB_AUTOQUERY_UPDATE, "id = '".$_POST['problemId']."'");
		}
		$this->_core->_redirectUser("?m=myWork&a=editProblem&id=".$_POST['problemId']);
	}	
	
	function changeEcoBalance () {
		$update['user_eco_balance'] = $_POST['user_eco_balance'];
		$update['user_eco_modified'] = "NOW()";
		$this->changeBalance($update);
	}
	
	function changeNtBalance () {
		$update['user_nt_balance'] = $_POST['user_nt_balance'];
		$update['user_nt_modified'] = "NOW()";
		$this->changeBalance($update);
	}	
	
	function changeClientNotes() {
		$this->_core->_setId();
		$q = $this->_core->getMySqlCode($_POST, "users", "user_id = '".$this->_core->id."'");
		$this->_db->make_query($q);			
		$this->_core->_redirectUser("?m=admin&a=editUser&id=".$this->_core->id);			
	}
	
	function changeBalance ($update) {
		$this->_db->_sql->autoExecute("users", $update, DB_AUTOQUERY_UPDATE, "user_id = '".$_POST["user"]."'");
		$this->_core->_redirectUser("?m=admin&a=editUser&id=".$_POST["user"]);
	}	
	
	function changeUserStatus () {
		if ($this->_core->_setId()) {
			//workers can only change to grind status
			$update['user_status'] = $this->_core->_isAdmin() ? $_POST['user_status'] : "grind";
			$this->_db->_sql->autoExecute("users", $update, DB_AUTOQUERY_UPDATE, "user_id = '".$this->_core->id."'");

			// unassign all work orders when user status changes to complete
			if ($this->_core->_isAdmin() && $_POST['user_status'] == 'complete') {
				$workorder = ModuleStorage::getModule("workorder");
				$workorder->_db->updateAssignedByUser($this->_core->id, false);
			}
		}
		$this->_core->_redirectUser("?m=admin&a=editUser&type=".$_REQUEST['type']."&id=".$this->_core->id);
	}
	
	function createWorkorder() {
		if ($this->_core->_setId()) {
			//insert the bonus
			$_POST['client'] = $this->_core->id;
			$_POST['creator'] = $this->_core->myUserInfo['userId'];
			$q = $this->_core->getMySqlCode($_POST, "workorder");
			$this->_db->make_query($q);	
			//insert the workorder
			$_POST['client'] = $this->_core->id;
			$_POST['workorder'] = mysql_insert_id();
			$_POST['date_created'] = "NOW()";
			$_POST['date_modified'] = "NOW()";
			$q = $this->_core->getMySqlCode($_POST, "workorder_assign");
			$this->_db->make_query($q);				
		}
		$this->_core->_redirectUser("?m=admin&a=editUser&type=".$_REQUEST['type']."&id=".$this->_core->id);		
	}
	
	function changeUserWorkorder () {
	    //print_a($_POST); die;
		if (is_array($_POST['workorder']) && isset($_POST['workorderType']) && $this->_core->_setId()) {
			$update = array("type" => $_POST['workorderType']);
			//print_a($update);
			foreach ($_POST['workorder'] as $k => $v) {
				$this->_db->_sql->autoExecute("workorder_assign", $update, DB_AUTOQUERY_UPDATE, "id = '$v'");
			}
		}
		$this->_core->_redirectUser("?m=admin&a=editUser&type=".$_REQUEST['type']."&id=".$this->_core->id);		
	}	
}
?>