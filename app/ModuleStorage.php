<?
/*
 * $Id: ModuleStorage.php,v 1.3 2003/10/04 15:26:34 dan Exp $
 */

/**
 * A static storage area for modules.
 * Used to prevent creating a module more than one time.
 */
class ModuleStorage {
	
    function getModule($argModule, $moduleFilename = "", $params = "") {
        global $smarty, $core;
        static $glob_modules;
        $module = null;

        if ( ! isset($glob_modules) || ! is_array($glob_modules)) 
            $glob_modules = array();

        //if the filename is not supplied we use standard naming convention
        if ( empty($moduleFilename)) 
        	$moduleFilename = $argModule."/".ucfirst($argModule).".php";
        
        $argModule = ucfirst ($argModule);

        if ( ! isset($glob_modules) || ! is_array($glob_modules)) {
            $glob_modules = array();
        }

        //debugObject($glob_modules);

        // Create an instance of the module if it doesn't exist in the storage already
        if (isset($glob_modules[$argModule]) &&
            is_object($glob_modules[$argModule])) {
            //echo "<br>Debug: Not creating $argModule, already exists.<br>";
            $module = $glob_modules[$argModule];
            $module->params = $params;

        } else {
            //echo "<br>Debug: Creating $argModule.<br>";
            //initiate the object
            include_once($moduleFilename);
            $module = new $argModule($smarty, $core, $params);
            $glob_modules[$argModule] = &$module;
        }

        return $module;
    }

}


?>
