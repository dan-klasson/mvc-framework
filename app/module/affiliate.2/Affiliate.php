<?php

include_once('Module.php');
include_once('affiliate/DbAffiliate.php');


/**
 */
class Affiliate extends Core  {
    
    var $_db;
    var $params;
    var $caching = true;
    
    var $button;
    var $page;

    function Affiliate(&$smarty, &$core, $params) {
        $this->Core(&$smarty, &$core);
        $this->_db = new DbAffiliate();
        $this->params = $params;

        $this->button = array("delete" => false, "save" => false);
        $this->page = array("select" => false, "create" => false, "edit" => false, "view" => false);
        
    }
    
    function index() {
        $this->setButtonColor("affiliate");
			
		    	
 		$this->smarty->assign("button", $this->button);	
 		$this->smarty->assign("page", $this->page);
 			
 		$this->smarty->assign("referrals", $this->db->getMySqlData("referral"));	
 		
 		$this->smarty->display("affiliate/index.tpl");
    }
    
    
    
	function edit() { 
	    
	    $this->_setId();
	    
		$casino = ModuleStorage::getModule("casino");
		$edit = ModuleStorage::getModule("edit");	
		$user = ModuleStorage::getModule("user");	

		$this->smarty->assign("user", $this->_db->selectAssignedUsers($this->id));			
		$this->smarty->assign("users", $user->_db->selectUserBasic());			
		$this->smarty->assign("casinos", $casino->_db->selectCasino());	
		$this->smarty->assign("payment_types", $edit->_db->selectPaymentType());			
		
		$this->smarty->assign("referrals", $this->_db->getMySqlData("referral"));
		$this->smarty->assign("workstation", $this->db->getMySqlData("workstation")); 		 
		
		$this->button["delete"] = true;
		$this->button["save"] = true;
		$this->smarty->assign("button", $this->button);	
		
		$this->page["edit"] = true;
 		$this->smarty->assign("page", $this->page);		
 		
		
		if ($this->_setId()) {
			$this->smarty->assign("referral", $this->_db->getReferral($this->id));
		    $this->smarty->assign("affiliate", $this->_db->selectAffiliate($this->id));	
		}
			
		$this->setButtonColor("affiliate");
		$this->smarty->display("affiliate/index.tpl");		
	}

	
	function view() {
		
		$workorder = ModuleStorage::getModule("workorder");
		$this->smarty->assign("user", $workorder->_db->selectAssignedUsers($this->id));			
		
		$this->smarty->assign("referrals", $this->db->getMySqlData("referral"));
		$this->smarty->assign("workstation", $this->db->getMySqlData("workstation"));
		
		if ($this->_setId()) {
		    $this->smarty->assign("referral", $this->_db->getReferral($this->id));
			$this->smarty->assign("affiliate", $this->_db->selectAffiliate($this->id));	
		}
			
		$this->setButtonColor("affiliate");
		$this->smarty->display("affiliate/index.tpl");
	}
	
	
	function add() { 
		if ( ! $this->_setId())
			return;	
		
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$_POST["referral"] = $this->id;

		$q = $this->getMySqlCode($_POST, "affiliate");
		//echo $q; die;
		$this->db->make_query($q);		
		$this->_redirectUser("?m=affiliate&a=edit&id=".$this->id);
	}
	
	
	function save() { 
		if ( ! $this->_setId())
			return;	
			
		$update = array();
			
		$update["date_modified"] = "NOW()";
		
		for($i=0; $i<count($_POST['affiliateId']); $i++) {
            
            $update['id'] = $_POST['affiliateId'][$i];
            $update['casino'] = $_POST['casino'][$i];
            $update['payment_type'] = $_POST['payment_type'][$i];
            $update['group'] = $_POST['group'][$i];
            $update['id_tags'] = $_POST['id_tags'][$i];
            $update['username'] = $_POST['username'][$i];
            $update['userpass'] = $_POST['userpass'][$i];
            $update['account'] = $_POST['account'][$i];
            $update['cpa'] = $_POST['cpa'][$i];
            $update['loss'] = $_POST['loss'][$i];
            $update['dep'] = $_POST['dep'][$i];

    		$q = $this->getMySqlCode($update, "affiliate", "id = '".$update['id']."'");
    		//echo $q."<br>";
    		$this->db->make_query($q);            
		}
		
		$this->_redirectUser("?m=affiliate&a=edit&id=".$this->id);
	}	
	
	function saveReferral () {
		if ( ! $this->_setId())
			return;		 
 
	    $insertReferral = $_POST;
		$insertReferral['user'] = $_POST['user_assign'];
	    $q = $this->getMySqlCode($insertReferral, "referral", "id = $this->id");
	    $this->db->make_query($q);
	    
	    $workorder = ModuleStorage::getModule("workorder");
	    
		//get all current work orders
		$current = $this->_db->selectAssignedUsers($this->id);
	
		//now we create or delete the work orders specified in the submit form
		$workorder->setAssignment($current, $_POST["user"], $this->id, "referral");	    
	    
	    $this->_redirectUser("?m=affiliate&a=edit&id=".$this->id);
	}

	
	function delete() { 
		if ( ! $this->_setId())
			return;
		
		$q = "DELETE FROM referral WHERE id = '".$this->id."'";
		$this->db->make_query($q);
		$this->_redirectUser("index.php?m=affiliate");
	}	
	
	
	function create() {
	    $edit = ModuleStorage::getModule("edit");
	    
        $this->setButtonColor("affiliate");
		
				
		$this->button["save"] = true;
		$this->smarty->assign("button", $this->button);	
		
		$this->page["create"] = true;
 		$this->smarty->assign("page", $this->page);	
 				
 		
		$this->smarty->assign("referrals", $edit->_db->select("referral"));
		$this->smarty->display("affiliate/index.tpl");			
	}		
	
	function addReferral () {
	    $q = $this->getMySqlCode($_POST, "referral");
	    $this->db->make_query($q);
	    $this->_redirectUser("?m=affiliate&a=edit&id=".mysql_insert_id());	    
	}
	
	function duplicateReferral() {
	   if ($this->_setId()) {
            $workorder = ModuleStorage::getModule("workorder");
            
	        //copy the referral
	        $referral = $this->_db->selectReferral($this->id);
	        $_POST['name'] = $_POST['duplicate'];
    	    $q = $this->getMySqlCode($_POST, "referral");
    	    $this->_db->make_query($q);
    	    
    	    $duplicatedId = mysql_insert_id();
    	    
    	    //copy the affiliate rows
    	    $affiliate = $this->_db->selectAffiliateByReferral($this->id);
    	    foreach ($affiliate as $key => $value) {
    	       if (count($value)) {
    	           $value['referral'] = $duplicatedId;
    	           //these we don't copy
    	           $value['id_tags'] = '';
    	           $value['username'] = '';
    	           $value['userpass'] = '';
 	      	           
        	       $q = $this->getMySqlCode($value, "affiliate");
        	       $this->_db->make_query($q); 
    	       } 	    
    	    }
	   }   
	   $this->_redirectUser("?m=affiliate&a=edit&id=".$duplicatedId);	
	}
	
}
