<?php
/*
 * $Id: 
 */

include_once('Module.php');
include_once('DbWorkstation.php');

class Workstation extends Core {
 	
	var $_db; //casino database object
    var $params;
    var $caching = true;	
	
	function Workstation (&$smarty, &$core, $params) {
		$this->Core(&$smarty, &$core);
		$this->_db = new DbWorkstation();	
		
		//make sure the user is an admin user
		if ( ! $this->_isAdmin())
			return;
	}

	function index() {
		$this->_setId();

		isset($_REQUEST["p"]) ? $p = $_REQUEST["p"] : $p = "";
		
		$this->smarty->assign("type", $p);
					
		$this->setButtonColor("workstation");
			
	
		switch ($p) {
			case 'edit':
				$this->smarty->assign("workstation", $this->_db->selectWorkstation($this->id));
				break;
			case 'select':
				$this->smarty->assign("workstation", $this->_db->selectWorkstation());
				break;
			case 'insert':
				if (isset($_POST["name"]) && ! empty($_POST["name"])) {
					$q = $this->getMySqlCode($_POST, "workstation");
					$this->db->make_query($q);
					$this->_redirectUser("index.php?m=workstation&p=edit&id=".mysql_insert_id());
					die;
				}
				break;
			case 'save':
				if (isset($_POST["name"]) && ! empty($_POST["name"]) && ! empty($this->id)) {
					$q = $this->getMySqlCode($_POST, "workstation", "id = ".$this->id);
					$this->db->make_query($q);
				}
				$this->_redirectUser("index.php?m=workstation&p=edit&id=".$this->id);			
				break;
			case 'delete':
				empty($this->id) ? "" : $this->_db->deleteWorkstation($this->id);
				$this->_redirectUser("index.php?m=workstation&p=select");	
				break;
				
			
		}
		$this->smarty->display("workstation/workstation.tpl");
	}
}
?>