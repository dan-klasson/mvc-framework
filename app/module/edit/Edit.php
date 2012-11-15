<?php
/*
 * $Id: 
 */

include_once('Module.php');
include_once('edit/DbEdit.php');


/**
 */
class Edit extends Core  {
    
    var $_db;
    var $params;
    var $caching = true;
    
    var $button;

    function Edit(&$smarty, &$core, $params) {
        $this->Core(&$smarty, &$core);
        $this->_db = new DbEdit();
        $this->params = $params;
        
        $this->button = array("delete" => false, "save" => false);
        $this->setButtonColor("edit");
    }
    
    function index() {
    	if ( ! $this->_setId("page")) 
    		$this->smarty->display("edit/index.tpl");
    		
    	switch ($this->id) {
    		case 1:
    			$this->paymentType();
    			break;
    		case 2:
    			$this->casinoEngine();
    			break;    
    		case 3:
    			$this->claimMethod();
    			break; 
    		case 4:
    			$this->playType();
    			break;      
    		case 5:
    			$this->country();
    			break; 			
    		case 6:
    			$this->welcomeMsg();
    			break;  
    		case 7:
    			$this->siteName();
    			break;     			
    		case 8:
    			$this->bgColor();
    			break; 
    			/*
    			not used anymore. moved to affiliate     
    		case 9:
    			$this->referral();			    			    						  						    			
    			$this->bgColor();
    			break;  
    			*/
    		case 10:
    			$this->deposit();			    			    						  						    			
    			$this->bgColor();
    			break; 
    		case 11:
    			$this->cashout();			    			    						  						    			
    			$this->bgColor();
    			break; 
    		case 12:
    			$this->currency();			    			    						  						    			
    			$this->bgColor();
    			break;  
    		case 13:
    			$this->problemType();			    			    						  						    			$this->bgColor();
    			break;   
    		case 14:
    			$this->depositMethod();			    			    						  						    			$this->bgColor();
    			break;  
    		case 15:
    			$this->withdrawalMethod();			    			    						  						    			$this->bgColor();
    			break;      
    		case 16:
    			$this->currencyNotes();			    			    						  						    			$this->bgColor();
    			break;       						    			  			   			    			    			
    			
    	}
    }
    
    
    

	function _setDefault($type) {
        if ($this->_setId()) { 
	    	$update = array('default_value' => 'N');
	    	$this->_db->_sql->autoExecute($type, $update, DB_AUTOQUERY_UPDATE);
	    	$update['default_value'] = "Y";
	    	$this->_db->_sql->autoExecute($type, $update, DB_AUTOQUERY_UPDATE, "id = '".$this->id."'");
    	}
	} 

	function _setTypeUpperSyntax ($type) { 
		$strpos = strpos($type, "_");
		if ($strpos) 
			return substr($type, 0, $strpos) . ucfirst(substr($type, $strpos+1, strlen($type)));
		return $type;	
	}
    
    
    
/* Main, used for all */


    
   function view ($type) {
   		$typeUpper = $this->_setTypeUpperSyntax($type);
   	
    	if ($this->_setId())
    		$this->smarty->assign($typeUpper, $this->_db->select($type, $this->id));
    	else
    		$this->smarty->assign($typeUpper, $this->_db->select($type));

    	$this->smarty->display("edit/$type.tpl");
    }
    
    function save($type) {
    	$typeUpper = $this->_setTypeUpperSyntax($type);
    	
 		if ($this->_setId() && !empty($_POST["name"])) {			
			$_POST["date_modified"] = "NOW()";
			$_POST["date_created"] = "NOW()";
			
			$q = $this->getMySqlCode($_POST, $type, "id = '$this->id'");
			$this->db->make_query($q);		
			$this->_redirectUser("index.php?m=edit&a=$typeUpper&id=".$this->id);  
 		} else {
 			$this->_redirectUser("index.php?m=edit&a=$typeUpper");	
 		}
    }  
    
    function delete($type) {
    	$typeUpper = $this->_setTypeUpperSyntax($type);
    	
 		if ($this->_setId()) {
		$q = "DELETE FROM $type WHERE id = '$this->id'"; 
		$this->db->make_query($q);	

		$this->_redirectUser("index.php?m=edit&a=$typeUpper"); 
 		} else {
 			$this->_redirectUser("index.php?m=edit&a=$typeUpper");	
 		}		  
    }
    
    function create ($type) {
    	$typeUpper = $this->_setTypeUpperSyntax($type);
    	
 		if (!empty($_POST["name"])) {
			$_POST["date_modified"] = "NOW()";
			$_POST["date_created"] = "NOW()";
			$q = $this->getMySqlCode($_POST, $type); 
			$this->db->make_query($q);		
			$this->_redirectUser("index.php?m=edit&a=$typeUpper"); 			
 		} else {
 			$this->_redirectUser("index.php?m=edit&a=$typeUpper"); 
 		}
    } 
    
    
    
    
  
    
 /* Referral */  
    
   function referral () {
    	$this->view("referral");	
    	die;	
    }
    
    function saveReferral() {
    	$this->save("referral");
    }  
    
    function deleteReferral() {
    	$this->delete("referral");
    }
    
    function createReferral() {
		$this->create("referral");			
    }   

 /* deposit */  
    
   function deposit () {
    	$this->view("deposit");	
    	die;	
    }
    
    function saveDeposit() {
    	$this->save("deposit");
    }  
    
    function deleteDeposit() {
    	$this->delete("deposit");
    }
    
    function createDeposit() {
		$this->create("deposit");
    }  
    
    function setDefaultDeposit() {
    	$this->_setDefault("deposit");
    	$this->_redirectUser("index.php?m=edit&a=deposit"); 
    }  
    

 /* deposit method */  
    
   function depositMethod () {
    	$this->view("deposit_method");	
    	die;	
    }
    
    function saveDepositMethod() {
    	$this->save("deposit_method");
    }  
    
    function deleteDepositMethod() {
    	$this->delete("deposit_method");
    }
    
    function createDepositMethod() {
		$this->create("deposit_method");
    }  
    

 /* deposit method */  
    
   function withdrawalMethod () {
    	$this->view("withdrawal_method");	
    	die;	
    }
    
    function saveWithdrawalMethod() {
    	$this->save("withdrawal_method");
    }  
    
    function deleteWithdrawalMethod() {
    	$this->delete("withdrawal_method");
    }
    
    function createWithdrawalMethod() {
		$this->create("withdrawal_method");
    }  
        
       
 /* cashout */  
    
   function cashout () {
    	$this->view("cashout");	
    	die;	
    }
    
    function saveCashout() {
    	$this->save("cashout");
    }  
    
    function deleteCashout() {
    	$this->delete("cashout");
    }
    
    function createCashout() {
		$this->create("cashout");			
    }    
    
    function setDefaultCashout() {
    	$this->_setDefault("cashout");
    	$this->_redirectUser("index.php?m=edit&a=cashout"); 
    }      

 /* currency */  
    
   function currency () {
    	$this->view("currency");	
    	die;	
    }
    
    function saveCurrency() {
    	$this->save("currency");
    }  
    
    function deleteCurrency() {
    	$this->delete("currency");
    }
    
    function createCurrency() {
    	$_POST['name'] = strtoupper($_POST['code']);
		$this->create("currency");			
    }      
    
    function setDefaultCurrency() {
    	$this->_setDefault("currency");
    	$this->_redirectUser("index.php?m=edit&a=currency"); 
    }     
    
    
 /* currency notes*/  
    
   function currencyNotes () {
    	$this->view("currency_notes");	
    	die;	
    }
    
    function saveCurrencyNotes() {
    	$this->save("currency_notes");
    }  
    
    function deleteCurrencyNotes() {
    	$this->delete("currency_notes");
    }
    
    function createCurrencyNotes() {
		$this->create("currency_notes");			
    }      
    
    function setDefaultCurrencyNotes() {
    	$this->_setDefault("currency_notes");
    	$this->_redirectUser("index.php?m=edit&a=currencyNotes"); 
    }     
        
 
 /* problemType */  
    
   function problemType () {
    	$this->view("problem_type");	
    	die;	
    }
    
    function saveProblemType() {
    	$this->save("problem_type");
    }  
    
    function deleteProblemType() {
    	$this->delete("problem_type");
    }
    
    function createProblemType() {
		$this->create("problem_type");			
    }           
    
    
  
 ################################################################################
    
    
    /* Payment Type */
    
    function paymentType() {
    	if ($this->_setId())
    		$this->smarty->assign("paymentType", $this->_db->selectPaymentType($this->id));
    	else 
    		$this->smarty->assign("paymentType", $this->_db->selectPaymentType());
    	
    	$this->smarty->display("edit/payment_type.tpl");
    }
    
    function savePaymentType() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";

		$q = $this->getMySqlCode($_POST, "payment_type", "id = ".$this->id);
		//echo $q; die;
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=paymentType&id=".$this->id);   
    }
    
    function deletePaymentType() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM payment_type WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=paymentType");   
    }
    
    function createPaymentType() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "payment_type"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=paymentType"); 			
    }
    
     /* Casino Engine */
    
    function casinoEngine () {
    	if ($this->_setId())
    		$this->smarty->assign("casinoEngine", $this->_db->selectCasinoEngine($this->id));
    	else 
    		$this->smarty->assign("casinoEngine", $this->_db->selectCasinoEngine());
    	
    	$this->smarty->display("edit/casino_engine.tpl");    		
    }
    
    function saveCasinoEngine() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";

		$q = $this->getMySqlCode($_POST, "casino_engine", "id = ".$this->id);
		//echo $q; die;
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=casinoEngine&id=".$this->id);   
    }  
    function deleteCasinoEngine() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM casino_engine WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=casinoEngine");   
    }
    
    function createcasinoEngine() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "casino_engine"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=casinoEngine"); 			
    }
    
     /* Claim Method */
    
    function claimMethod () {
    	if ($this->_setId())
    		$this->smarty->assign("claimMethod", $this->_db->selectClaimMethod($this->id));
    	else 
    		$this->smarty->assign("claimMethod", $this->_db->selectClaimMethod());
    	
    	$this->smarty->display("edit/claim_method.tpl");    		
    }
    
    function saveClaimMethod() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";

		$q = $this->getMySqlCode($_POST, "claim_method", "id = ".$this->id);
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=claimMethod&id=".$this->id);   
    }  
    function deleteClaimMethod() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM claim_method WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=claimMethod");   
    }
    
    function createClaimMethod() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "claim_method"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=claimMethod"); 			
    }    
    
   /* Play Type */  
    
   function playType () {
    	if ($this->_setId())
    		$this->smarty->assign("playType", $this->_db->selectPlayType($this->id));
    	else 
    		$this->smarty->assign("playType", $this->_db->selectPlayType());
    	
    	$this->smarty->display("edit/play_type.tpl");    		
    }
    
    function savePlayType() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";

		$q = $this->getMySqlCode($_POST, "play", "id = ".$this->id);
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=playType&id=".$this->id);   
    }  
    function deletePlayType() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM play WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=playType");   
    }
    
    function createPlayType() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "play"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=playType"); 			
    }

  /* Country */  
    
   function country () {
    	if ($this->_setId())
    		$this->smarty->assign("country", $this->_db->selectCountry($this->id));
    	else 
    		$this->smarty->assign("country", $this->_db->selectCountry());
    	
    	$this->smarty->display("edit/country.tpl");    		
    }
    
    function saveCountry() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";

		$q = $this->getMySqlCode($_POST, "country", "id = ".$this->id);
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=country&id=".$this->id);   
    }  
    
    function deleteCountry() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM country WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=country");   
    }
    
    function createCountry() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$q = $this->getMySqlCode($_POST, "country"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=country"); 			
    }
    
    
  /* welcomeMsg */  
    
   function welcomeMsg () {
    	$this->smarty->assign("welcomeMsg", $this->_db->selectSiteSetting());
    	$this->smarty->display("edit/welcomeMsg.tpl");    		
    }
    
    function saveWelcomeMsg() {
		$q = "UPDATE site_setting SET welcome_msg = '".$_REQUEST["welcome_msg"]."'";
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=welcomeMsg");   
    }  
    
  /* siteName */  
    
   function siteName () {
   		$workstation = ModuleStorage::getModule("workstation");
    	$this->smarty->assign("site", $this->_db->selectSiteSetting());
    	$this->smarty->assign("workstation", $workstation->_db->selectWorkstation());
    	$this->smarty->display("edit/site_name.tpl");
    }
    
    function saveSiteName() {
		$q = "UPDATE site_setting SET site_name = '".$_REQUEST["site_name"]."'";
		$this->db->make_query($q);

		if (is_array($_POST['message'])) {
			foreach($_POST['message'] as $k => $v) {
				$q = "UPDATE workstation SET 
					message = '".$v."' 
					WHERE id = '".$k."'";
				$this->db->make_query($q);					
			}
		}		
		$this->_redirectUser("index.php?m=edit&a=siteName");   
    }     


 /* Background color */  
    
   function bgColor () {
    	if ($this->_setId())
    		$this->smarty->assign("bgColor", $this->_db->selectBgColor($this->id));
    	else 
    		$this->smarty->assign("bgColor", $this->_db->selectBgColor());
    	
    	$this->smarty->display("edit/bg_color.tpl");    		
    }
    
    function saveBgColor() {
 		if ( ! $this->_setId())
			return;
		
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		
		$q = $this->getMySqlCode($_POST, "bg_color", "id = '$this->id'");
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=bgColor&id=".$this->id);   
    }  
    
    function deleteBgColor() {
 		if ( ! $this->_setId())
			return;
		$q = "DELETE FROM bg_color WHERE id = '$this->id'"; 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=bgColor");   
    }
    
    function createBgColor() {
		$_POST["date_modified"] = "NOW()";
		$_POST["date_created"] = "NOW()";
		$_POST['user_theme'] = $this->myUserInfo["userTheme"];
		$q = $this->getMySqlCode($_POST, "bg_color"); 
		$this->db->make_query($q);		
		$this->_redirectUser("index.php?m=edit&a=bgColor"); 			
    }   
    
         
         
  
}
?>