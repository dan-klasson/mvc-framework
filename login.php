<?php

session_start();

include_once('./site-config.php');
include_once(MODULES_DIR.'/config/config.php');

$core = new Core;

//if the action is set we log in
if ( ! empty($_GET["a"])) {
	$core->_act();
}
//if not we fetch the workstations and display the login page
$workstation = ModuleStorage::getModule("DbWorkstation", "workstation/DbWorkstation.php");
$core->smarty->assign("workstation", $workstation->selectWorkstation()); 
$core->smarty->display("login.tpl");
