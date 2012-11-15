<?php
/*
 * $Id: config_template.php,v 1.7 2003/06/26 21:22:57 dan Exp $
 *
 * Copy this file to config.php and then change the variables you need in that file.
 * This is to keep the config-file in CVS clean from passwords and such.
 *
 * This is the configuration for the whole community system.
 */

/*
$www_path = getcwd().'/';
$server_path = $www_path."../";
*/

$server_path = (defined('MODULES_DIR')) ? MODULES_DIR : getcwd().'/';
$www_path = (defined('WWW_DIR')) ? WWW_DIR : getcwd().'/';

define('COMPILE_DIR', $www_path.'template_c/');
define('CONFIG_DIR', $www_path.'config/');
define('CACHE_DIR', $www_path.'cache/');


// Check operating-system and set library-paths
if (strpos(strtoupper(PHP_OS), 'WIN') === false) {
    // This is Unix/other
    $path_sep = ':';

    // load Smarty and PEAR library files, remember / at the end
    define('SMARTY_DIR','/usr/local/share/smarty/');
    define('PEAR_DIR', '/usr/local/php4/lib/php');
} else {
    // This is Windows
    $path_sep = ';';

    // load Smarty and PEAR library files, remember / at the end
    define('SMARTY_DIR','c:/apache/Smarty/libs/');
    define('PEAR_DIR', 'c:/apache/php/pear/');
}


// set the default include path
// include the PEAR code repository, we're using the DB and mail classes,
// and the Smarty-directory
// Also include our server_path to use the classes there
ini_set('include_path', ini_get('include_path').$path_sep.PEAR_DIR.$path_sep.SMARTY_DIR.$path_sep.$server_path);


// User access levels
define('ACCESS_ADMIN', 1);
define('ACCESS_MODERATOR', 5);
define('ACCESS_USER', 10);
define('ACCESS_BANNED', 11);

?>
