<?php

include_once('Module.php');
include_once('DbMyWork.php');

class MyWork extends Core  {
    
    var $_db;
    var $params;
    var $caching = true;
    
    var $problemId = 0;
    

    function MyWork(&$smarty, &$core, $params) {
        $this->Core(&$smarty, &$core);
        $this->_db = new DbMyWork();
        $this->params = $params;
        
        // Disallow not logged in users (if configured that way).
        //$this->_checkPublicAccess();
    }
    
    function index() {
		$admin = ModuleStorage::getModule("admin");
		$workstation = ModuleStorage::getModule("workstation");
		
		
		$this->smarty->assign("problemPool", $admin->_db->fetchProblemByWorkstation($this->myUserInfo['userWorkstation']));
		
		$this->setOrphanPool();
			
		//info displayed at login
		$q = "SELECT welcome_msg FROM site_setting";
        $result = $this->db->make_query($q);
        $res = $result->fetchRow();

		$this->smarty->assign("workstation", $workstation->_db->selectWorkstation($this->myUserInfo["userWorkstation"]));
		$this->smarty->assign("problems", $admin->_db->fetchHighPriority($this->myUserInfo["userId"]));
		$this->smarty->assign("welcomeMsg", $res["welcome_msg"]);
		$this->smarty->assign("id", $this->id);
		
		$this->setButtonColor("myWork");
		$this->smarty->display("mywork/index.tpl");	
    }
	
	function editWorkorder () { 
	    
		if ( ! $this->id && ! $this->_setId())
		  return;	
		  
		//initiate the modules
		$admin = ModuleStorage::getModule("admin");
		$workstation = ModuleStorage::getModule("workstation");	
		$edit = ModuleStorage::getModule("edit");
		$workorder = ModuleStorage::getModule("workorder");
		
		//get the data for the different dropdown menus
		$currency = $edit->_db->select("currency");
		$deposit = $edit->_db->select("deposit");
		$cashout = $edit->_db->select("cashout");		
		$referral = $edit->_db->select("referral");		
		
		//get work order data
		$arrWorkorder = $this->setChangeWorkorderDate(
			$this->setChangeWorkorderName($workorder->_db->selectWorkOrder($this->id, false)));
			
		//if someone deleted the work order
		//todo: error handling instead of die();
		if ( ! count($arrWorkorder)) 
		    die('work order of this problem was deleted');
		
		// todo: replace this with mysql DEFAULT NOW()
		//if dates are blank set them todays date
		$enums = array('claimed', 'deposited', 'completed', 'received', 'docs_sent');
		foreach ($enums as $type) {
            if (
                substr($arrWorkorder['date_'.$type], 0, 4) == 0000 &&
                substr($arrWorkorder['date_'.$type], 6, 2) == 00 &&
                substr($arrWorkorder['date_'.$type], 10, 2) == 00 &&
                substr($arrWorkorder['date_'.$type], 14, 2) == 00 &&
                substr($arrWorkorder['date_'.$type], 18, 2) == 00) {
                $arrWorkorder['date_'.$type] = date("Y-m-d H:i");
                $arrWorkorder['time_'.$type] = date("H:i");
            }
		} 		 
        
		if ($this->problemId) 
			$problem = $admin->_db->fetchProblem($this->problemId);
		else	
			$problem = $admin->_db->fetchProblemByWorkorder($this->id);	
			
		$this->smarty->assign("currency", $currency);   
		$this->smarty->assign("bonusCurrency", $edit->_db->select("currency", $arrWorkorder["currency"]));   
		$this->smarty->assign("deposit", $deposit);   
		$this->smarty->assign("cashout", $cashout);  
		$this->smarty->assign("referral", $referral);  
		$this->smarty->assign("workorder", $arrWorkorder);
		$this->smarty->assign("id", $this->id);
			
		
		if (isset($_GET['type']) && $_GET['type'] == "popup") {
			$this->smarty->assign("nav", false);
			$this->smarty->display("header.tpl");	
			$this->smarty->display("mywork/edit_workorder.tpl");	
			$this->smarty->display("footer.tpl");	
		} else {
			$this->smarty->assign("problemPool", $admin->_db->fetchProblemByWorkstation($this->myUserInfo['userWorkstation']));
			
			$this->setOrphanPool();	
			
			$this->smarty->assign("problem", $problem);
			$this->setButtonColor("myWork");
			$this->smarty->display("mywork/index.tpl");	
		}
	}    
	
	function moveOutOfWorkPool () {
		if ( ! $this->_setId()) 
			return;
		$this->_db->deleteWorkorderFromPool($this->id);
		$this->_redirectUser("index.php?m=myWork");		
	}
	
	function saveWorkorder () { 
		if ( is_numeric ($_REQUEST["id"])) {
		    
			//docs sent
			if ($_POST['docs_sent'] == 'on') {
				$_POST['docs_sent'] = "Y";
				$_POST['date_docs_sent'] = "NOW()";
			} else {
				$_POST['docs_sent'] = "N";
			}
					
			$_POST["date_modified"] = "NOW()";
			
			$enums = array('claimed', 'deposited', 'completed', 'received');
			foreach ( $enums as $type) {
    			if ( empty($_POST[$type])) {
    			    $_POST[$type]  = "N";
    			} else {
    			    $_POST["date_".$type] = $_POST[$type."Year"].$_POST[$type."Month"].$_POST[$type."Day"].str_replace(":", "", $_POST[$type."Time"])."00";		
    			    $_POST[$type]  = "Y";
    			}
			} 	
			
			//get the current completed value from this work order
			$workorder = ModuleStorage::getModule("workorder");
			$c_workorder = $workorder->_db->selectWorkorderCompletedStatus($_REQUEST["id"]);
			$c_bonus = $workorder->_db->fetchBonusBasic($c_workorder['workorder']);
			
			//if the user is completing a work order that was uncomplete before
			if ($_POST['completed'] == 'Y' && $c_workorder['completed'] == 'N') {
			    // we insert the workstation id that we use in the workstation filter in reports
			    $_POST['workstation_completed'] = $this->myUserInfo['userWorkstation'];
			    $_POST['amount_to_wager_completed'] = $c_bonus['amount_to_wager'];
			    $_POST['amount_bonus_completed'] = $c_bonus['amount_bonus'];
			}
			
			$q = $this->getMySqlCode($_POST, "workorder_assign", "id = ".$_REQUEST["id"]);
			//echo $q; die;	
			$this->db->make_query($q);
			$type = ! empty($_REQUEST['type']) ? "&type=popup" : "";
			$this->_redirectUser("index.php?m=myWork&a=editWorkorder&id=".$_REQUEST["id"].$type);
		}
		return false;
	}
	
	function editProblem () {
		$this->_setId();
	
		//get the problem info and set vars b4 
		//calling editWorkorder or index
		$admin = ModuleStorage::getModule("admin");
		$problem = $admin->_db->fetchProblem($this->id);
		$this->problemId = $problem['id'];
		
		//if there's a work order associated with the problem
		if ($problem['workorder']) {
			$this->id = $problem['workorder'];

			return $this->editWorkorder();
		}
		
		$admin = ModuleStorage::getModule("admin");
		$workstation = ModuleStorage::getModule("workstation");
		
		$this->smarty->assign("problemPool", $admin->_db->fetchProblemByWorkstation($this->myUserInfo['userWorkstation']));
		
		$this->setOrphanPool();	
		
		$this->smarty->assign("problem", $problem);
		$this->setButtonColor("myWork");
		$this->smarty->display("mywork/index.tpl");	
	}	
	
	function newProblem () {
		$edit = ModuleStorage::getModule("edit");
		$user = ModuleStorage::getModule("user");
		$casino = ModuleStorage::getModule("casino");
		$workstation = ModuleStorage::getModule("workstation");
		$workorder = ModuleStorage::getModule("workorder");
		
		$this->smarty->assign("problemType", $edit->_db->select("problem_type"));
		$this->smarty->assign("user", $user->_db->selectUser()); 		
		$this->smarty->assign("casino", $casino->_db->selectCasinoEngine()); 		
		$this->smarty->assign("workstation", $workstation->_db->selectWorkstation()); 		

		$this->smarty->assign("workorder", $workorder->setChangeWorkorderName($workorder->getWorkorder($_GET['workorder'])));
		
		$this->index();	
	}
	
	function setOrphanPool() {
		$this->smarty->assign("clients", $this->_db->selectClient());
		
		if ( isset( $_POST['client']) && ! empty($_POST['client']) ) {
			$this->smarty->assign("bastardPool", $this->setChangeWorkorderName(
				$this->_db->selectOrphanPool(false, $this->myUserInfo["userWorkstation"],$this->userId, $_POST['client']))
			);
		}	
		$this->smarty->assign("adoptedPool", $this->setChangeWorkorderName(
			$this->_db->selectOrphanPool(true, $this->myUserInfo["userWorkstation"],$this->userId))
		);
	}
}
?>
