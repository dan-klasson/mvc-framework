<?php
/*
 * $Id: config_template.php,v 1.7 2003/06/26 21:22:57 dan Exp $
 *
 * This is the configuration for the whole site.
 */

define('DB_DEBUG_QUERIES', 'true');
define('DB_SHOW_ERRORS', 'true');

// Theme settings
$tplPath = MODULES_DIR."/template/";
define('THEME_DIR', 'theme1');

// Smarty paths
define('TEMPLATE_DIR', $tplPath);

// What characters to allow in the user names, this is checked with ereg()
define('VALID_USER_NAME', "^[a-zA-Z0-9_]*$");

// If we should send the email, otherwise it is up to the admin to activate the users
define('USER_ACTIVATE_BY_MAIL', 'true');

define('SMARTY_CACHE_ENABLED', 'false');

define("FO_THREADSEP", "Page:");

//if language is enabled or not
define('LANGUAGE_ENABLED', 'false');

// What extra info to use when pre-registering a user.
// See DbCore::preAddUser() for more info.
$config_extra_signup_info = array();

 
/*
$www_path = getcwd().'/';
$server_path = $www_path."../";
*/

$server_path = (defined('MODULES_DIR')) ? MODULES_DIR : getcwd().'/';
$www_path = (defined('WWW_DIR')) ? WWW_DIR : getcwd().'/';

define('COMPILE_DIR', MODULES_DIR.'smarty/template_c/');
define('CONFIG_DIR', $server_path.'config/');
define('CACHE_DIR', MODULES_DIR.'smarty/cache/');

// Check operating-system and set library-paths
if (strpos(strtoupper(PHP_OS), 'WIN') === false) {
    // This is Unix/other
    $path_sep = ':';

    // load Smarty and PEAR library files, remember / at the end
    define('SMARTY_DIR', MODULES_DIR.'smarty/libs/');
	define('PEAR_DIR', '/usr/share/php/PEAR/');
} else {
    // This is Windows
    $path_sep = ';';

    // load Smarty and PEAR library files, remember / at the end
    define('SMARTY_DIR','d:/htdocs/Smarty/libs/');
    define('PEAR_DIR', 'd:/htdocs/pear/');
}


// set the default include path
// include the PEAR code repository, we're using the DB and mail classes,
// module path
// and the Smarty-directory
// Also include our server_path to use the classes there
ini_set('include_path', ini_get('include_path').$path_sep.PEAR_DIR.$path_sep.SMARTY_DIR.$path_sep.$server_path.$path_sep.MODULES_DIR."/module/");


// User access levels
define('ACCESS_ADMIN', 1);
define('ACCESS_MODERATOR', 2);
define('ACCESS_USER', 3);
define('ACCESS_BANNED', 11);

//change servertime to U.S EASTERN time
putenv("TZ=US/Eastern"); 

include_once(MODULES_DIR.'Core.php');




ini_set("memory_limit","12M");

