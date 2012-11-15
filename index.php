<?php
/*
 * $Id: index.php,v 1.24 2004/01/22 12:07:15 dan Exp $
 */
// we use php sessions
session_start();

// include the classes
include_once('./site-config.php');
include_once(MODULES_DIR.'/config/config.php');

$core = new Core();
		
//default page is mywork
if ( empty($_GET["m"])) {
	$_GET["m"] = "myWork";
}

$core->_sendOfflineUserToLogin();
$core->_act();

