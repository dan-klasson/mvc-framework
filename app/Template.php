<?php
include_once('Smarty.class.php');
include_once('config/config.php');

  /**
   * smarty_prefilter_i18n()
   * This function takes the language file, and rips it into the template
   * $GLOBALS['_NG_LANGUAGE_'] is not unset anymore
   *
   * @param $tpl_source
   * @return
   **/
  function smarty_prefilter_i18n($tpl_source, &$smarty) {

if (!is_object($GLOBALS['_NG_LANGUAGE_'])) {
      die("Error loading Multilanguage Support");
    }
    // load translations (if needed)
    $GLOBALS['_NG_LANGUAGE_']->loadCurrentTranslationTable();
    // Now replace the matched language strings with the entry in the file
    return preg_replace_callback('/##(.+?)##/', '_compile_lang', $tpl_source);
  }

  /**
   * _compile_lang
   * Called by smarty_prefilter_i18n function it processes every language
   * identifier, and inserts the language string in its place.
   *
   */
  function _compile_lang($key) {
    return $GLOBALS['_NG_LANGUAGE_']->getTranslation($key[1]);
  }
  
  
  
class Template extends Smarty {
    var $fs_root = '';
    var $fs_plugins = '';
    var $_fs_enabled = false;
    var $language;

    function Template() {
       // Class Constructor. These automatically get set with each new instance
       global $server_path;
       
       $this->Smarty();
       $this->template_dir = TEMPLATE_DIR;
       $this->compile_dir = COMPILE_DIR;
       $this->config_dir = CONFIG_DIR;
       $this->cache_dir = CACHE_DIR;
       $this->plugins_dir = array('plugins', $server_path.'smarty-plugin');
   
       //echo "<pre>";
       //print_r($this->plugins_dir);
       //echo "</pre>";
/*
       echo TEMPLATE_DIR."<br>";
       echo COMPILE_DIR."<br>";
       echo CONFIG_DIR."<br>";
       echo CACHE_DIR."<br>"."<br>";
*/
       $this->caching = false;
       $this->debugging = false;

       //$this->security = true;
       $this->secure_dir = array('templates');
   
      // Multilanguage Support
      // use $smarty->language->setLocale() to change the language of your template
      //     $smarty->loadTranslationTable() to load custom translation tables
      $locale = "sv";
      $this->language = new ngLanguage($locale); // create a new language object
      $GLOBALS['_NG_LANGUAGE_'] =& $this->language;
      $this->register_prefilter("smarty_prefilter_i18n");
   }


    function fetch($_smarty_tpl_file, $_smarty_cache_id = null, $_smarty_compile_id = null, $_smarty_display = false) {
      // We need to set the cache id and the compile id so a new script will be
      // compiled for each language. This makes things really fast ;-)
      $_smarty_compile_id = SITE_SESSION_ID.'-'.$this->language->getCurrentLanguage().'-'.$_smarty_compile_id;
      #$_smarty_compile_id = 'sv-'.$_smarty_compile_id;
      $_smarty_cache_id = $_smarty_compile_id;

      // Now call parent method
      return parent::fetch( $_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $_smarty_display );
    }
    /**
    * fetchModule
    * Fetches a module. Works like displayModule but it's only
    * called from the template code.
    * @param string $moduleName Name of module
    * @param array $params An array with all parameters passed from the template file    
    */    
    function fetchModule ($moduleName, $params) {
        global $server_path, $tplPath, $glob_modules;
    
        // Get the method-name and file-name
        $method = isset($params["assign"]) ? $params["assign"] : '';
        $moduleFilename = 'module/'.$moduleName."/".ucfirst($moduleName).'.php'; 

        if ( ! file_exists($server_path.'/'.$moduleFilename)) {
            echo "<b>Module error: </b>The module $moduleName could not be found.<br />";
            die;
        }   
         
        require_once('ModuleStorage.php');      
    
        //call the module method
        $module = ModuleStorage::getModule($moduleName, $moduleFilename, $params);
           
            
        //check that the specified parameter assign's method name exists
        if ( ! method_exists($module, 'assign'.ucwords($method))) {
            echo "<b>Module error: </b>The module <i>".$moduleName."</i> parameter: <b>$method</b> is invalide.<br />";
            echo "<b>Correct syntax:</b> methodName.";
            die;
        }      
        
        if ( ! isset($params["file"])) {
            //no template file specified for this module
            //we use default; same as the method name
            $params["file"] = $method.".tpl";
        }
        
        //add the correct start of the method name
        $method = "assign".ucwords($method);

        //set the lang variabels
        $lang_file = str_replace(".tpl", "", $moduleName);
        $this->language->loadTranslationTable("sv", $lang_file);         
        
        //the module either has caching on or off 
        if ($module->caching) {
            $this->caching = SMARTY_CACHE_ENABLED;
        } else {
            $this->caching = false;
        }            
                
        //full path to chosen template (index default)
        $indexTpl = $tplPath.$moduleName."/".$params["file"];
    
        if ( ! file_exists($indexTpl)) {
            echo "<b>Module error: </b>The module template file $indexTpl could ".
                "not be found.<br />";
            return;
        }        
    
        //if the class is set to caching and if we have a cached page
        if ($module->caching && SMARTY_CACHE_ENABLED === true) {
    
            if ($this->is_cached($indexTpl)) {
                // The module is cached, dont call the module, just fetch the template
                //$smarty->clear_cache($indexTpl); //testing purposes
                //$smarty->clear_all_cache();
                echo "<small>cachad</small>";
    
            } else {
                // Module is not cached, run it.
                //call the index method that does all the php code of the module
                $module->$method();
                //echo "<small>inte cachad</small>";
            }
    
        } else {
            // Caching is not enabled for this module, just run it.
            //$this->clear_cache($indexTpl); //behovs detta? /dan
            $module->$method();
            //echo "<small>ingen caching</small>";
        }
        echo $this->fetch($indexTpl);
        $smarty->caching = false;
        return;
        }      
        
    
    /**
    * _smarty_include
    * new include functionality from the template code
    * replaced {init module=""}
    * usage:
    * 1. A normal smarty include:
    *    {include file="filenameName.tpl"}   
    * 2. Calling a module:   
    *    {include file="moduleName"}
    *    {include file="moduleName" template="filename.tpl"}
    *    If the template parameter is empty it automatically looks for the 
    *    template file index.tpl
    * 3. Calling an action method in a module:
    *    {include file="moduleName" action="methodName"}
    * 4. Calling an assign method in a module (fetch as assign is a 
    *    reserved variable in smarty):
    *    {include file="moduleName" fetch="methodName"}
    *    {include file="moduleName" fetch="methodName" template="fileName.tpl"}
    *    If the template parameter is empty it automatically looks for the 
    *    template file methodName.tpl
    * 
    *  There are two types of methods:
    * 1. action:
    *    does an action, the method name has to start with action and
    *    the template parameter has to be action
    * 2. assign:
    *    fetches info from the database and assigns it as a smarty array or variable
    *    has to start with assign and the template parameter has to be fetch.
    *    (assign is a reserved variable in Smarty)
    * @param array $params An array with all parameters passed from the template file
    */
    function _smarty_include($params) {
        global $server_path, $tplPath, $smarty;  
   
        if (substr($params['smarty_include_tpl_file'], -4) != ".tpl") {
            
            
            // Get the module-name and file-name
            $argModule = isset($params["smarty_include_tpl_file"]) ? $params["smarty_include_tpl_file"] : '';
            $moduleFilename = 'module/'.$argModule."/".ucfirst($argModule).'.php';
         
            //get the needed include parameters
            foreach($params["smarty_include_vars"] as $k => $v) {
                if ($k == "action")
                    $action = $v;
                else if ($k == "fetch")
                    $method = $v;
                else if ($k == "template")
                    $template = $v;  
                    echo "$k => $v";   
            }

            if ( ! file_exists($server_path.'/'.$moduleFilename)) {
                echo "<b>Module error: </b>The module $argModule could not be found.<br />";
                die;
            }   
         
            require_once('ModuleStorage.php');      
        
            //call the module method
            $module = ModuleStorage::getModule($argModule, $moduleFilename, $params);
               
            //do necassary error check and set variables for the ACTION method
            if (isset($action)) { 
                if ( ! method_exists($module, 'action'.ucwords($action))) {
                    echo "<b>Module error: </b>The module ".$argModule."'s <i>action</i> parameter: <b>$action</b> is invalide.<br />";
                    echo "<b>Correct syntax:</b> methodName.".ucwords($action);
                    die;
                }
                //add the correct start of the method name
                $action = "action".ucwords($action);                
            }
            
            //do necassary error check and set variables for the ASSING method
            if (isset($method)) {
                if ( ! method_exists($module, 'assign'.ucwords($method))) {
                    echo "<b>Module error: </b>The module ".$argModule."s <i>assign</i> parameter: <b>$method</b> is invalide.<br />";
                    echo "<b>Correct syntax:</b> methodName.";
                    die;
                }
                //if the template parameter is empty, 
                //we use default for the assign method
                if ( ! isset($template)) {                   
                    $template = $method.".tpl";
                }       
                //add the correct start of the method name
                $method = "assign".ucwords($method);
            } else {                           
                //set the default method and template to index
                $method = "index";
                //if the template parameter is empty, 
                //we use default index
                if ( ! isset($template)) {                     
                    $template = "index.tpl";
                }
            }
  
            //set the lang variabels
            $lang_file = $params['smarty_include_tpl_file'];
            $this->language->loadTranslationTable("sv", $lang_file);            
            
            //the module either has caching on or off 
            if ($module->caching) {
                $smarty->caching = SMARTY_CACHE_ENABLED;
            } else {
                $smarty->caching = false;
            }            
                    
            //full path to chosen template (index default)
            $indexTpl = $tplPath.$argModule."/".$template;
        
            if ( ! file_exists($indexTpl)) {
                echo "<b>Module error: </b>The module template file $indexTpl could ".
                    "not be found.<br />";
                return;
            }

            //if the class is set to caching and if we have a cached page
            if ($module->caching && SMARTY_CACHE_ENABLED === true) {
        
                if ($smarty->is_cached($indexTpl)) {
                    // The module is cached, dont call the module, just fetch the template
                    $smarty->clear_cache($indexTpl);
                    //echo $smarty->fetch($indexTpl); 
                    echo $this->fetch($argModule."/".$template); 
                        //$smarty->clear_all_cache();
                        //echo "<small>cachad</small>";
                        $smarty->caching = false;
                        return;
            
                    } else {
                        // Module is not cached, run it.
                        //call the index method that does all the php code of the module
                        $module->$method();
                        //print the template file, the tpl vars have been set in index()
                        //echo $smarty->fetch($indexTpl); 
                        echo $this->fetch($argModule."/".$template);
                        //echo "<small>inte cachad</small>";
                        $smarty->caching = false;
                        return;
                    }
            
                } else {
                    // Caching is not enabled for this module, just run it.
                    $smarty->clear_cache($indexTpl);
                    $module->$method();  
                    echo $smarty->fetch($indexTpl); 
                    //echo "<small>ingen caching</small>";
                    $smarty->caching = false;
                    return;
                }
        } else {
            //fetch the lang vars for the "normal" includes aswell
            $lang_file = str_replace(".tpl", "", $params['smarty_include_tpl_file']);
            $this->language->loadTranslationTable("sv", $lang_file);
        }
    
        return parent::_smarty_include($params);
    }

  }

