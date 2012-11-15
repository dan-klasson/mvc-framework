<?php
/*
 * $Id: Core.php,v 1.57 2004/01/24 18:13:38 dan Exp $
 */

include_once('DbCore.php');
include_once('Template.php');
require_once('ModuleStorage.php');

define('COOKIE_NAME', 'saved_sid');

/*
  About the argument-string, $_GET['m'] and $_GET['s']
  ====================================================
  Method is used for dynamic pages. The method-string contains two
  parts, class-name and method-name in this format: classMethod
  The method-string is sent as a single argument to the class-
  constructor, and specifies what actions to be taken.
  Note that the method-string should be the same as the template-
  name (sans the prefix .tpl), like this:
  method-string: classMethod
  template-name: classMethod.tpl
  
  Static is used for static pages (doh!)
  The static-string only have a template associated to it, like this:
  static-string: PageName
  template-name: staticPageName.tpl
*/


/**
 * Superclass for pages
 *
 * All classes which inherit from this class should have a method-
 * call (in their own constructor) which calls Page's constructor.
 * Like this:
 *
 * <pre>
 *  class TestClass extends Page {<br>
 *    function TestClass($action) {<br>
 *        $this->Page($action);<br>
 *    }<br>
 *  }
 *  </pre>
 *
 */
class Core {
    var $db;
    var $smarty;

    var $id = 0; // General purpose id, gotten from $_GET['id'] or $_POST['id']
    var $arrId = array(); //array of id's
    var $userId = 0;
    var $myUserInfo = array(
        'userIsOnline' => false,
        'userId' => 0,
        'userAccess' => 11,
        'userName' => '',
        'userLastIp' => '',
        'userIsAdmin' => false,
        'userIsModerator' => false,
        'userIsBanned' => false,
        'userLastLogin' => '',
        'userThemeCode' => '#FFFFFF',
        'userWorkstation' => 0
    );

    /**
     * Checks so the parameters are correctly specified.
     */
    function _verifyParams() {
        // Cannot have both m and s in the querystring
        if ((isset($_GET['a']) && isset($_GET['s']))
            || (isset($_GET['m']) && isset($_GET['s']))) {
            echo "errorPage('404');";
        }
    }

    /**
     * Get the variables from either the GET or POST-arrays
     * Only accepts variables consisting of pure text: '/^[a-z]+$/i'
     */
    function _getParams() {
        $result = array();
        $match = '/^[a-z]+$/i';

        // Get the variables from either the GET or POST-arrays
        foreach(array('m', 'a', 's') as $what) {
            if (isset($_GET[$what]) && preg_match($match, $_GET[$what])) {
                $result[$what] = $_GET[$what];
            } else if (isset($_POST[$what]) && preg_match($match, $_POST[$what])) {
                $result[$what] = $_POST[$what];
            } else {
                $result[$what] = '';
            }
        }

        /*
        $arg_module = (isset($_GET['m']) && preg_match($match, $_GET['m'])) ? $_GET['m'] : '';
        $arg_action = (isset($_GET['a']) && preg_match($match, $_GET['a'])) ? $_GET['a'] : '';
        $arg_static = (isset($_GET['s']) && preg_match($match, $_GET['s'])) ? $_GET['s'] : '';
        */

        return $result;
    }


    /**
     * Constructor
     * Initiates database connection and stores the smarty template-object
     * The $module/$action is only used when a method-string is set (see main.class)
     * @param string $module The module to create
     * @param string $action The action to send to the module
     */
    function Core() { 
        global $smarty;

        $this->db = new DbCore();
        $smarty = new Template;
        $this->smarty = &$smarty;
        $this->smarty->register_object('core', $this);
        //$this->smarty->register_function('init', 'initiateModule', true);

        // Get old session if it exists
        $this->_getSavedSession();

        // Image and theme settings
        //$this->smarty->assign('siteName', SITE_NAME); replaced by getSiteName (dynamic)
        $this->setSiteName();
        $this->smarty->assign('themeDir', THEME_DIR);
        $this->smarty->assign('imageDir', THEME_DIR.'/image');
        
        // Is the user online?
        $online = $this->_auth();
        if ($online) {
            //yes and now update last active
            $this->db->updateLastActive($this->myUserInfo['userId'], $this->_localDate("U"));
        }
   
        // If the user gives an incorrect url, i.e. the template doesn't exist,
        // we set the 404.tpl instead of presenting an ugly error message.
        $this->smarty->default_template_handler_func = 'error404';

        // Thread separator, defined in lang-xxx.php
        $this->smarty->assign('FO_THREADSEP', FO_THREADSEP);
        
        // Assign the user-info
        $this->smarty->assign('myUserInfo', $this->myUserInfo);
        
        //assign to display the nav bar
        $nav = isset($_GET['nav']) ? false : true;
        $this->smarty->assign('nav', $nav);
					  
        // Save the url so we can go back to it in case of f.ex. an error
        // Todo: Det finns problem med att ha det här (deadlock), men jag ser inte
        // hur man skulle lösa det annars /Tomas
        $_SESSION[SITE_SESSION_ID.'SessUrlPath'] = $_SERVER['QUERY_STRING'];
       
    }

    function _act() {
        /*
         * Act according to the params:
         * m = module
         * a = action
         * s = static
         */
		 
        $this->_verifyParams();
        $params = $this->_getParams();
        $arg_module = $params['m'];
        $arg_action = $params['a'];
        $arg_static = $params['s'];       

        // Display a page based on module and action
        if (! empty($arg_module) AND ! empty($arg_action)) {
            $arr = array("module" => $arg_module, "action" => $arg_action);
            //dislayModule does the rest
            $this->_displayModule($arr);

        // Display a page based on module
        } else if ( ! empty($arg_module)) { 
            $arr = array("module" => $arg_module);
            //dislayModule does the rest
            $this->_displayModule($arr);
        
        // Display a static page
        } else if ( ! empty($arg_static)) {
            //static lang vars
            $this->smarty->language->loadTranslationTable("sv", "static.".$arg_static);
            $tplA = TEMPLATE_DIR.$arg_static.'/'.$arg_static.'.tpl';
            $tplB = TEMPLATE_DIR.$arg_static.'.tpl';
            if(file_exists($tplA)) {
                $this->smarty->display($tplA);
            } else if(file_exists($tplB)) {
                $this->smarty->display($tplB);
            } else { 
                $this->smarty->display('index.tpl');
            }
        } else { 
            //static lang vars
            $this->smarty->language->loadTranslationTable("sv", "static.index");
            $this->smarty->display('index.tpl');
        }
    }

    /**
     * Authenticate user
     * Checks if the user is online, and sets the userInfo in that case
     * @return boolean true if the user is logged in, false otherwise
     */
    function _auth() {
        $user_access = isset($_SESSION[SITE_SESSION_ID.'SessAccessId']) ?
            $_SESSION[SITE_SESSION_ID.'SessAccessId'] : 0;

        if (isset($_SESSION[SITE_SESSION_ID.'SessUserOnline'])) {
            $this->myUserInfo['userIsOnline'] = $_SESSION[SITE_SESSION_ID.'SessUserOnline'];
        }
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserId'])) {
            $this->userId 				= $_SESSION[SITE_SESSION_ID.'SessUserId'];
            $this->myUserInfo['userId'] = $_SESSION[SITE_SESSION_ID.'SessUserId'];
        }
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserName'])) {
            $this->myUserInfo['userName'] = $_SESSION[SITE_SESSION_ID.'SessUserName'];
        }
        if (isset($_SESSION[SITE_SESSION_ID.'SessAccessId'])) {
            $this->myUserInfo['userAccess'] = $_SESSION[SITE_SESSION_ID.'SessAccessId'];
        }
        if (isset($_SESSION[SITE_SESSION_ID.'SessWorkstation'])) { 
            $this->myUserInfo['userWorkstation'] = $_SESSION[SITE_SESSION_ID.'SessWorkstation'];
        }        
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserLastLogin'])) {
            $this->myUserInfo['userLastLogin'] = $_SESSION[SITE_SESSION_ID.'SessUserLastLogin'];
        }
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserLastIp'])) {
            $this->myUserInfo['userLastIp'] = $_SESSION[SITE_SESSION_ID.'SessUserLastIp'];
        }

        if (isset($_SESSION[SITE_SESSION_ID.'SessTheme'])) {
            $this->myUserInfo['userTheme'] = $_SESSION[SITE_SESSION_ID.'SessTheme'];
        }

        $this->myUserInfo['userIsAdmin'] = $this->_isAdmin();
        $this->myUserInfo['userIsModerator'] = $this->_isModerator();

        if ($this->myUserInfo['userIsOnline'])
            return true;

        return false;
    }


    function login() { 
        // Check if the $id is empty, if it is, do nothing and display a 404
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->smarty->assign('nav', false);
            $this->_error('LO_ER_FIELDS');
        }
        else {
            $row = $this->db->fetchLoginInfo($_POST['username'], $_POST['workstation']);

            if ($row['user_access'] == ACCESS_BANNED) {
                $this->_error('LO_ER_BANNED');
            }
            // If login is succesful, store the session variabels
            else if ($_POST['password'] == $row['user_password'] && ! empty($_POST['workstation'])) {
                $this->_initSession($row['user_id'], $row['user_username'],
                                    $row['user_access'], $_POST['workstation'], $row['lastonline'],
                                    $row['user_ip'], $row['user_theme']);

                // Record the ip of the user
                $this->myUserInfo['userAccess'] = $row['user_access'];
                $ip = $this->_getUserIp();

                // Log and update user-info
                //log_report(130, $_SESSION[SITE_SESSION_ID.'SessUserId'], $ip);
                $this->db->updateLastLogin($row['user_id'], $ip);

                $this->_saveSession();

                // Note! Do not use sendUserBack() here, to prevent deadlock
                $this->_sendUserToIndex("?login=true");
            }
            // Wrong password
            else {
            	$this->smarty->assign('nav', false);
                $this->_error('LO_ER_LOGINFAILURE');
            }
        }
    }

    /**
     * Destroys the session and sends the user back to the index
     */
    function logout() {
        // Todo: Fixa så att man anses som utloggad också när detta skett, inte
        // bara kolla i databasen efter lastactive och lägg till en halvtimme eller
        // vad det nu är.
        $this->_quitSession();
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    function _sendOfflineUserToLogin() {
		if ( ! $this->_isOnline()) { 
			header("Location: login.php");	
			die;
		}
    }

    /**
     * Creates a user
     * Todo: Kanske ska lägga ut detta i en speciell modul, så att man kan byta ut
     * sättet man aktiverar användare från site till site.
     * Todo: Fixa en activate() funktion, så man kan skriva in koden om den skickas
     * via email.
     * Todo: Kolla så denna funktion (speciellt emailaktiveringen) funkar.
     */
    function signup() {
        $email = '';
        $username = '';
        $error = 0;
        $errors = array();
        $signup_ok = false;

        if ($this->myUserInfo['userIsOnline'])
            $this->_error('LO_ER_LOGGED_IN');
            
            if (isset($_POST['signup'])) {
            // The user pressed the "Create account"-button

            // Clean up accounts which has not been activated within the last week
            //$this->db->clean_up_nonactivated_users();

            $username = trim($_POST['username']);
            $password1 = trim($_POST['password1']);
            $password2 = trim($_POST['password2']);
            $email = trim($_POST['email']);

            if ( ! empty($username)) {
                $er_usernamecharacter = false;

                // Make sure the username doesn't contain any weird characters
                // Important: Do this before checking if it exists to prevent
                // sql-injection bugs
                if ( ! ereg(VALID_USER_NAME, $username)) {
                    $error = 1;
                    $er_usernamecharacter  =  1;
                }

                // Make sure the username has atleast 2 characters
                if (strlen($username) > 64 || strlen($username) < 2) {
                    $error = 1;
                    $er_usernamelength = 1;
                }

                // Make sure the username doesn't exist
                // Don't do this if an error occured to prevent sql-inject-bugs
                if ( ! $er_usernamecharacter) {
                    if ($this->db->usernameExists($username)) {
                        $error = 1;
                        $er_usernameexist = 1;
                    }
                }

            }
            else {
                $error = 1;
                $er_username = 1;
            }

            // Make sure the user haven't given an invalid emailadress
            $er_emailtrue = false;
            if ( ! empty($email)) {
                //if ( ! ereg("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$", $email)) {
                if ( ! $this->_valid_email($email)) {
                    $error = 1;
                    $er_emailtrue = 1;
                }
            } else {
                $error = 1;
                $er_email = 1;
            }

            // Make sure the email adress is unique
            // Don't do this if an error occured to prevent sql-inject-bugs
            if ( ! $er_emailtrue) {
                $q = "SELECT user_email FROM users WHERE user_email ='".$email."'";
                $rs = mysql_query($q) or die(mysql_error());
                if (mysql_fetch_array($rs)) {
                    $error = 1;
                    $er_usermailexist = 1;
                }

                $q = "SELECT activate_email FROM users_activate
                      WHERE activate_email ='$email'";
                $rs = mysql_query($q) or die(mysql_error());
                if (mysql_fetch_array($rs)) {
                    $error = 1;
                    $er_usermailexist = 1;
                }
            }

            // Make sure the password entered match
            if (isset($password1) && $password1 != $password2) {
                $error = 1;
                $er_passwd1 = 1;
            }
            if (strlen($password2) < 4) {
                $error = 1;
                $er_passwd2 = 1;
            }

            // If the signup was succesfull
            if (isset($error) && $error == 0) {
                $activation_key = substr(md5(microtime()), 0, 6);

                // If we should send the email, otherwise it is up to the admin to
                // activate the users
                if (USER_ACTIVATE_BY_MAIL == 'true') {

                    // Todo: The LO_MA-constants are not defined anywhere

                    $message = LO_MA_SIGNUP_MESSAGE1;
                    $message .= $activation_key ."\n\n";
                    $message .= LO_MA_SIGNUP_MESSAGE2;
                    $subject = LO_MA_SIGNUP_SUBJECT;

                    // Different methods to send the email
                    if ($mailmethod == 1) {
                        $params["host"] = $smtphost;
                        $params["port"] = $smtpport;
                        if ($smtpuser != '') {
                            $params['auth'] = true;
                            $params['username'] = $smtpuser;
                            $params['password'] = $smtppass;
                        }
                        $mail_object =& Mail::factory('smtp', $params);
                    } else {
                        $mail_object =& Mail::factory('sendmail', $params);
                    }

                    // Set up headers
                    $headers['From']    = ACTIVATE_EMAIL_FROM;
                    $headers['To']      = $email;
                    $headers['Subject'] = ACTIVATE_EMAIL_SUBJECT;

                    // Send the email
                    $mail_result = $mail_object->send($email, $headers, $message);

                    // See if the email was sent successfully
                    //echo '<pre>'; print_r($mail_result); echo '</pre>';
                    if ($mail_result !== true) {
                        $error = 1;
                        $er_send_email = 1;
                        $tmp_msg = $mail_result->message;
                        $er_send_email_message = $this->_localDate().' '.$tmp_msg;
                    }
                }

                // Add this user to a temporary table, for later activation.
                $this->db->preAddUser($username, $email, $password1, $activation_key,
                                      '');

                $signup_ok = true;
            }

            // Show eventual errors
            if (isset($error) && $error != 0) {
                if (isset($er_username) && $er_username == 1)
                    $errors[] = 'LO_ER_BLANK_USERNAME';
                if (isset($er_email) && $er_email == 1)
                    $errors[] = 'LO_ER_BLANK_EMAIL';
                if (isset($er_emailtrue) && $er_emailtrue == 1)
                    $errors[] = 'LO_ER_EMAIL';
                if (isset($er_usermailexist) && $er_usermailexist == 1)
                    $errors[] = 'LO_ER_EMAIL_EXIST';
                if (isset($er_usernamecharacter) && $er_usernamecharacter == 1)
                    $errors[] = 'LO_ER_CHAR';
                if (isset($er_usernamelength) && $er_usernamelength == 1)
                    $errors[] = 'LO_ER_LENGTH';
                if (isset($er_usernameexist) && $er_usernameexist == 1)
                    $errors[] = 'LO_ER_USER_EXISTS';
                if (isset($er_passwd1) && $er_passwd1 == 1)
                    $errors[] = 'LO_ER_PASSWORD_MATCH';
                if (isset($er_passwd2) && $er_passwd2 == 1)
                    $errors[] = 'LO_ER_PASSWORD_LENGTH';
                
                if (isset($er_send_email) && $er_send_email == 1)
                    $errors[] = 'LO_ER_SEND_EMAIL'.$er_send_email_message;
            }
        }

        $this->smarty->assign('signup_ok', $signup_ok);

        $this->smarty->assign('activation_messages', $errors);
        $this->smarty->assign('activation_error', $error);
        $this->smarty->assign('activation_email', $email);
        $this->smarty->assign('activation_username', $username);
        $this->smarty->display('core/signup.tpl');
    }


    /**
     * Divide up pages<br>
     * It divides up pages based on view= (in the querystring)<br>
     * It also sets 3 smarty variabels:<br>
     * $pages //how many pages it got divided into<br>
     * $posts //number of rows in query
     * $currentPage //current page
     * @return integer Number of rows to limit
     * @param int $numRows Number of rows in the query
     * @param int $limit How many rows to show
     */
    function _dividePage ($numRows, $rowsPerPage, $pview = 0) {
        if ($pview == 0) {
            $pview = isset($_GET['view']) ? $_GET['view'] : 1;
        }

        if (isset($pview) && is_numeric($pview)) {
            $limit = ($pview * $rowsPerPage) - $rowsPerPage;
        } else {
            $limit = 0;
        }

        // assign the data to the template
        if ($numRows > $rowsPerPage) {
            //$pages = ceil(($numRows / $rowsPerPage)) - 1;
            $pages = ceil($numRows / $rowsPerPage);
            /*
              ceil(1/15)  = 1
              ceil(15/15) = 1
              ceil(16/15) = 2
             */
        } else {
            $pages = 1;
        }

        if (isset($pview) && $pview == "last" ) {
            $limit = ($pages * $rowsPerPage) - $rowsPerPage;
            $this->smarty->assign("currentPage", $pages);
            $this->smarty->assign("view", $pages);
        } else {
            $this->smarty->assign("view",
                                  (isset($pview)
                                   && ! empty($pview)
                                   && is_numeric($pview))
                                  ? $pview : 1);

            $this->smarty->assign("currentPage",
                                  (isset($pview)
                                   && ! empty($pview)
                                   && is_numeric($pview))
                                  ? $pview : 1);
        }

        $this->smarty->assign("pages", $pages + 1);
        $this->smarty->assign("posts", $numRows);

        return $limit;
    }

    /**
     * @return true if any value in the array corresponds with
     * the page specified in the get string
     */
    function _isPublicPage ($publicPage) {
    
        $count = 0;
		foreach ($publicPage as $key => $val) { 
			if ($_GET["a"] != $val && ! $this->_isAdmin()) {
				$count++;
			} 
		}  
		if (count($publicPage) == $count) 
			return false;
		else 
			return true;
    }


    /**
     * @return true if the user is online
     */
    function _isOnline() {
        return ($this->myUserInfo['userIsOnline']);
    }

    /**
     * @return true if the user is administrator, false otherwise
     */
    function _isAdmin($access = 0) { 
        if ($access != 0)
            return ($access == ACCESS_ADMIN);
        else
            return ($this->myUserInfo['userAccess'] == ACCESS_ADMIN);
    }

    /**
     * @return true if the user is moderator, false otherwise
     */
    function _isModerator($access = 0) { 
        if ($access != 0)
            return ($access == ACCESS_MODERATOR);
        else
            return ($this->myUserInfo['userAccess'] == ACCESS_MODERATOR);
    }

    
    /**
     * @return true if the user is a regular user, false otherwise
     */
    function _isUser() {
        return ($this->myUserInfo['userAccess'] == ACCESS_USER);
    }

    /**
     * @return true if the user is banned, false otherwise
     */
    function _isBanned($access = 0) {
        if ($access != 0)
            return ($access == ACCESS_BANNED);
        else
            return ($this->myUserInfo['userAccess'] == ACCESS_BANNED);
    }


    /**
     * Am I viewing my own profile, gb, diary, etc, or somewone elses?
     * @return boolean true if this is my page (profile, gb, diary, etc)
     */
    function _isMyPage() {
        $this->_setUserId();

        if (isset($this->userId) && isset($this->myUserInfo['userId']) &&
            $this->myUserInfo['userId'] > 0 &&
            $this->userId == $this->myUserInfo['userId'])

            return true;

        return false;
    }


    /**
     * Set internal id
     * Set the id to whatever we got from $_GET['id'] or $_POST['id']
     */ 
    function _setId($name = 'id') {
        if (isset($_GET[$name]) && ! empty($_GET[$name]) && 
        		is_numeric($_GET[$name]) && $_GET[$name] > 0) {
        			
            $this->id = $_GET[$name];
        } else if (isset($_POST[$name])) {
        	
        	if ( is_array($_POST[$name])) {
				$this->id = $_POST[$name][0];
				$this->arrId = $_POST[$name];
				
         	} else if ( ! empty($_POST[$name]) && is_numeric($_POST[$name]) && $_POST[$name] > 0) {
           	 	$this->id = $_POST[$name];
        	}
        } else {
            return false;
        }

        return true;
    }

    /**
     * Set page view
     * Return what we got from $_GET['view'] or $_POST['view'] if it's a number
     */ 
    function _getView($name = 'view') {
        if (isset($_GET[$name]) && ! empty($_GET[$name]) && is_numeric($_GET[$name]) && $_GET[$name] > 0)
            return $_GET[$name];
        else if (isset($_POST[$name]) && ! empty($_POST[$name]) && is_numeric($_POST[$name]) && $_POST[$name] > 0)
            return $_POST[$name];

        return '';
    }

    /**
     * Set internal user-id, the owner of the page we are viewing
     * Set the id to whatever we got from $_GET['userId'] or $_POST['userId']
     */ 
    function _setUserId() {
        $name = 'userId';
        if (isset($_GET[$name]) && ! empty($_GET[$name]) && is_numeric($_GET[$name]) && $_GET[$name] > 0)
            $this->userId = $_GET[$name];
        else if (isset($_POST[$name]) && ! empty($_POST[$name]) && is_numeric($_POST[$name]) && $_POST[$name] > 0)
            $this->userId = $_POST[$name];
        else
            $this->userId = $this->myUserInfo['userId'];

        return $this->userId;
    }

    /**
     * Displays an error-page and exits
     * @param string $page The name of the error-page
     */
    function _errorPage($page) { 
        $this->smarty->display($page.".tpl");
        exit;
    }

    /**
     * Displays an error 404-page and exits
     */
    function _errorPage404() {
        $this->errorPage('404');
    }

    /**
     * Displays an error-message and exist
     */
    function _exitWithError($title, $userdata = '') {
        echo '<h3>Error: '.$title.'</h3>';
        echo $userdata;
        exit;
    }

    /**
     * Remove html from the posts, and creates links
     */
    function _kill_html($post) {
        //print ("DEBUG! kill_html was used");
        $post = ereg_replace("<","&lt;",$post);
        $post = ereg_replace(">","&gt;",$post);
        $post = ereg_replace("((ftp://)|(http://))(([[:alnum:]]|[[:punct:]])*)",
                             "<a href='\\0' target='_blank'>\\0</a>",
                             $post);
        $post = nl2br($post);

        return $post;
    }
    

    /**
     * Get the time/date corrected for a specified time-zone.
     * Mainly useful if your webserver is located in another time-zone than
     * your application is used.
     * (Note that this function is duplicated in Core and Database, for simplicity)
     *
     * Default is a SQL datetime-field, on the format: year-mm-dd hh:mm:ss
     * Examples of results (usable in SQL instead of NOW()):
     *
     * Field        Format          Notes
     * DATETIME:    'Y-m-d H:i:s'   (default)
     * TIMESTAMP:   'YmdHis'
     * DATE:        'Y-m-d'
     * TIME:        'H:i:s'
     *
     * Other fmt-examples:   Format
     * Seconds since 1970:   'U'
     *
     * See also the PHP-manual: http://www.php.net/date
     * And the MySQL-manual: http://www.mysql.com/doc/en/Date_and_time_types.html
     *
    */
    function _localDate($fmt = 'Y-m-d H:i:s') {
        // Todo: In the future, do this (NOTE: needs more testing!!):

          //putenv('TZ=Europe/Stockholm');
          //putenv("TZ=US/Eastern");
          return date($fmt);
        
        
        // Gets the timezone offset from CET in seconds
        // not used: $cet_offset = date('Z') - 7200;
        //$cet_offset = date('Z');
        //return date($fmt, time() - $cet_offset); // local time - offset
        return date($fmt);
    }
    
    /**
     * @desc Displays a module <br>
     * @desc Is always called from _act() in Core
     * @param array Classname and methodname
    
    */
     
    function _displayModule($params) {
        global $server_path;

        //echo "[Core::displayModule()] module: ".$params['module']."<br>";
        //echo "[Core::displayModule()] method: ".$params['action']."<br>";

        if ( ! isset($glob_modules) || ! is_array($glob_modules)) {
            $glob_modules = array();
        }

        $module = null;
        // The module name is the same as the path but Capitalized (path=module, name=Module.php)
        $modulePath = $params['module'] = isset($params['module']) ?
            $params['module'] : '';
        $moduleName = $params['module'] = isset($params['module']) ?
            ucfirst($params['module']) : '';
        $methodName = $params['action'] = isset($params['action']) ?
            $params['action'] : '';
       

        //Assign the lang variabels
        if ($moduleName != "Core") { 
            $this->smarty->language->loadTranslationTable("sv", strtolower($moduleName));
        }
		
        // Create an instance of the module, or use core
        if ( ! empty($moduleName)) {
            if ($moduleName == 'Core') { // Remember that the first letter is Capital
                $module = &$this;
            } else {
                // if file_exists och sånt här...
                $moduleFilename = 'module/'.$modulePath."/".$moduleName.'.php';

                if (file_exists($server_path.'/'.$moduleFilename)) {
                    $module = ModuleStorage::getModule($moduleName,
                                                       $moduleFilename, $params);

                } else {
                    $this->smarty->assign('errormessage',
                                      "MODULE ERROR: Module: $moduleFilename not found");
                    $this->smarty->display('error.tpl');
                    exit;
                }
            }
            //echo "[Core::displayModule()] created module: ".$module."<br>";
        } else {
            $this->smarty->assign('errormessage', 'MODULE ERROR: The <b>module</b> argument is empty');
            $this->smarty->display('error.tpl');
            exit;
        }

        // Call the method on the module
        // The substr-part is to prevent access to "private" methods
        if ( ! empty($methodName) && $module != null && substr($methodName, 0, 1) != '_') { 
			
            $module->$methodName();
        } else {
            // Här ska väl default-metoden anropas, index() eller vad den nu heter /dan
            //$module->showIndex();
            $module->index();
            //echo "MODULE ERROR: The <b>method</b> argument is empty.<br>";
        }
    }


    /**
     * Displays a jspopup alert, and sends the user to a specified page.
     * @param $error_msg string The message to show
     * @param $redirect string Where to redirect the user
     */
    function _error($error_msg, $redirect = 'index.php') {
        $this->smarty->language->loadTranslationTable("sv", "error");       
        $this->smarty->assign('error_message', $error_msg);
        #$this->smarty->assign('redirect', $redirect);
        #$this->smarty->display('errorPopup.tpl');
        $this->smarty->display('error.tpl');
        exit;
    }

    /**
     * Note! Use with care, since used in the wrong place may cause deadlocks.
     */
    function _sendUserBack() {         
        // Sends the user back to were he or she was
        $urlPath = (isset($_SESSION[SITE_SESSION_ID.'SessUrlPath'])) ?
            $_SESSION[SITE_SESSION_ID.'SessUrlPath'] : '';

        echo "<meta http-equiv='refresh' content='0; ".
            "URL=index.php?".$urlPath."'>";
        exit;
    }

    /**
     * Sends the user back to the index-page
     */
    function _sendUserToIndex($querystring = "") {
        $this->_redirectUser('index.php'.$querystring);
    }

    /**
     * Sends the user to a specific page
     */
    function _redirectUser($page) {
        echo "<meta http-equiv='refresh' content='0; URL=".$page."'>";
        exit;
    }

    /**
     */
    function _getUserIp() {
        $ip = 'Hidden';

        //if ($this->myUserInfo['userAccess'] > ACCESS_ADMIN) { //vad e detta bra for??!?
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown')
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else $ip = $_SERVER['REMOTE_ADDR'];
        //}
        return $ip;
    }


    function _debugObject($o) {
        echo "<pre>Debug object: $o\n";
        print_r($o);
        echo '</pre><br>';
    }

    function _valid_email($email) {
        return preg_match('/^[-A-Za-z0-9_.]+[@][A-Za-z0-9_-]+([.][A-Za-z0-9_-]+)*[.][A-Za-z]{2,8}$/i',
                          $email);
    }


    /* Session stuff */

    function _unsetSession() {
        $_SESSION[SITE_SESSION_ID.'SessUserOnline'] = false;
        $_SESSION[SITE_SESSION_ID.'SessUserId'] = '';
        $_SESSION[SITE_SESSION_ID.'SessUserName'] = '';
        $_SESSION[SITE_SESSION_ID.'SessAccessId'] = '';
        unset($_SESSION[SITE_SESSION_ID.'SessUserOnline']);
        unset($_SESSION[SITE_SESSION_ID.'SessUserId']);
        unset($_SESSION[SITE_SESSION_ID.'SessUserName']);
        unset($_SESSION[SITE_SESSION_ID.'SessAccessId']);
    }

    function _initSession($user_id, $user_username, $user_access, $workstation, $last_login = '',
                          $last_ip = '', $defaultTheme = '') { 
        $_SESSION[SITE_SESSION_ID.'SessUserOnline'] = true;
        $_SESSION[SITE_SESSION_ID.'SessUserId'] = $user_id;
        $_SESSION[SITE_SESSION_ID.'SessUserName'] = $user_username;
        $_SESSION[SITE_SESSION_ID.'SessAccessId'] = $user_access;
        $_SESSION[SITE_SESSION_ID.'SessUserLastLogin'] = $last_login;
        $_SESSION[SITE_SESSION_ID.'SessUserLastIp'] = $last_ip;
        $_SESSION[SITE_SESSION_ID.'SessTheme'] = $defaultTheme;
        $_SESSION[SITE_SESSION_ID.'SessWorkstation'] = $workstation;
    }

    function _setSessionCookie($sid) {
        // Params to setcookie: name, value, time-limit, path, (domain, secure)
        setcookie(COOKIE_NAME, $sid, 0, SESSION_COOKIE_PATH, SESSION_COOKIE_DOMAIN);
    }

    function _removeSessionCookie() {
       $this->_setSessionCookie('');
    }

    function _getSavedSession() {
        if ( ! isset($_SESSION[SITE_SESSION_ID.'SessUserId']) && isset($_COOKIE[COOKIE_NAME])) {
            $sid = $this->db->magic_addslashes($_COOKIE[COOKIE_NAME]); 
            $q =
                "SELECT user_id, user_username, user_access, user_theme, user_workstation 
                 FROM users, sessions
                 WHERE user_id = session_user
                 AND session_sid = '$sid' ";
            $result = $this->db->make_query($q);
            if ($result->numRows() != 0) {
                $row = $result->fetchRow();
                $this->_initSession($row['user_id'], $row['user_username'],
                                    $row['user_access'], 
                                    $row['user_workstation'],'', '',
                                    $row['user_theme']);
            }

        } else if (isset($_SESSION[SITE_SESSION_ID.'SessUserId']) &&
                   ! isset($_COOKIE[COOKIE_NAME]) &&
                   is_numeric($_SESSION[SITE_SESSION_ID.'SessUserId']) &&
                   $_SESSION[SITE_SESSION_ID.'SessUserId'] > 0) { 
            // The cookie was lost somehow, re-initialize it.
            $this->_setSessionCookie(session_id());
        }
    }

    function _hasSavedSession() {
        if (isset($_COOKIE[COOKIE_NAME]))
            return true;
        else
            return false;
    }


    function _saveSession() {
        // Save the user's session-id in the db, and set a cookie
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserId']) &&
            $_SESSION[SITE_SESSION_ID.'SessUserId'] > 0) {

            // Remove any old sid from the db first
            $q =
                "DELETE FROM sessions
                 WHERE session_user = '".$_SESSION[SITE_SESSION_ID.'SessUserId']."' ";
            $this->db->make_query($q);

            $sid = session_id();
            if ( ! empty($sid)) {
                           
                // If the INSERT fails, just continue on silently; both fields are
                // unique, and thus if the query fails, it means that we tried to
                // insert a value which already is in the db. This is perfectly ok.
                
                //changed the primary key to the new column session_id as it didn't work
                //otherwise on my comp (old mysql version)
                $q =
                    "INSERT INTO sessions
                       (session_id, session_sid, session_user)
                     VALUES
                       (null, '$sid', ".$_SESSION[SITE_SESSION_ID.'SessUserId'].") "; 
                $this->db->make_query($q);   
                $q = "SELECT * FROM sessions WHERE = '".$_SESSION[SITE_SESSION_ID.'SessUserId']."' ";
                //(echo $q."<br>";
                //print_r($this->db->my_getAll($q) );
                $this->_setSessionCookie($sid);
            }
        }
    }


    function _quitSession() {
        if (isset($_SESSION[SITE_SESSION_ID.'SessUserId']) &&
            $_SESSION[SITE_SESSION_ID.'SessUserId'] > 0) {

            // Remove sid from the db and the cookie
            $q =
                "DELETE FROM sessions
                 WHERE session_user = '".$_SESSION[SITE_SESSION_ID.'SessUserId']."' ";
            $this->db->make_query($q);

            // Note: Use the same arguments here as in saveSession() but set
            // the value to an empty string.
            $this->_setSessionCookie('');
        }

        $this->_unsetSession();
    }



    /**
     * Send some headers to instruct the web-browser to not cache this page.
     */
    function _sendBrowserNoCache() {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache'); // HTTP/1.0
    }        


    /**
     * Checks if the forum is accessible to members only
     */
    function _checkPublicAccess() {
        if (defined(FORUM_MEMBERS_ONLY) && FORUM_MEMBERS_ONLY === 'true') {
            if ( ! $this->myUserInfo['userIsOnline']) {
                $this->_error('LO_ER_NOT_LOGGED_IN', '?');
            }
        }
    }


    function _switchTheme($theme) {
        $_SESSION[SITE_SESSION_ID.'SessTheme'] = $theme;
        $this->myUserInfo['userTheme'] = $theme;
    }



    function _keyGen($len = 32) {
        srand((double)microtime() * 1000000);
        $rand_text = microtime().rand();
        return substr(md5($rand_text), 0, $len);
    }
}

