<?php
/*
 * $Id: Database.php,v 1.12 2003/10/04 15:26:34 dan Exp $
 */
require_once("DB.php");
require_once("config/config.php");

/**
 * $Id: Database.php,v 1.12 2003/10/04 15:26:34 dan Exp $
 * Superclass for database-connections
 *
 */
class Database {
    var $_sql = 0;
    var $_debug_on = false;
    var $_show_errors = false;
    var $cache_update_interval = 60; // seconds

    /**
     * Constructor
     * Initiates the database. Exits on error.
     */
    function Database() {
        $this->_debug_on = (defined('DB_DEBUG_QUERIES') &&
                            DB_DEBUG_QUERIES == 'true') ? true : false;
        $this->_show_errors = (defined('DB_SHOW_ERRORS') &&
                               DB_SHOW_ERRORS == 'true') ? true : false;

        global $datasource;
        $options = array(
            'debug'       => 2,
            'portability' => DB_PORTABILITY_NONE, 
        );            
        $this->_sql = DB::connect($datasource, $options);
        if (DB::isError($this->_sql)) {
            echo $this->_sql->getMessage();
            exit;
        }
        $this->_sql->setFetchMode(DB_FETCHMODE_ASSOC);
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
    function localDate($fmt = 'Y-m-d H:i:s') {
        // Todo: In the future, do this (NOTE: needs more testing!!):
        /*
          putenv('TZ=Europe/Stockholm');
          return date($fmt);
        */
        
        // Gets the timezone offset from CET in seconds
        // not used: $cet_offset = date('Z') - 7200;
        //$cet_offset = date('Z');
        //return date($fmt, time() - $cet_offset); // local time - offset
        return date($fmt);
    }


    /**
     * @return array user_id and user_username
     */
    function getUserName($userId) {
        if (is_numeric($userId)) {
            $q =
                "SELECT user_id, user_username ".
                "FROM users ".
                "WHERE user_id = '$userId'";
            $res = $this->make_query($q);
            return $res->fetchRow(DB_FETCHMODE_ASSOC);
        }
        return '';
    }

    /**
     * @return array user_id and user_username
     */
    function usernameExists($username) {
        // First check for users to be activated
        $q = "SELECT activate_username FROM users_activate
              WHERE activate_username = LOWER('$username')";
        $res = $this->make_query($q);
        if ($res->numRows() > 0) {
            return true;
        }

        // Then check real members
        $q = "SELECT user_username FROM users
              WHERE user_username = LOWER('$username')";
        $res = $this->make_query($q);
        if ($res->numRows() > 0) {
            return true;
        }

        // No users found
        return false;
    }

    /**
     * Returns true if a specified time (default 60 sec) has [förflutit]
     * since the last time this function returned true.
     * Returns false if the time limit has not been reached yet.
     * Used to limit the number of queries to once in a minute.
     * The $timer_name is used to have different timers for different queries.
     */
    function cache_update($timer_name) {
        // Should really be in config.php, but fix that later...
        $update_interval = $this->cache_update_interval;
        $now = date('U'); // Todo: Fixa local_date() som en funktion (gamla swedish_date() )
        
        //DEBUG: echo "cache_update, now: $now<br>";
        
        // Update the timer if the time limit has been reached
        if (! isset($_SESSION[SITE_SESSION_ID.$timer_name]) ||
            $now > ($_SESSION[SITE_SESSION_ID.$timer_name] + $update_interval)) {
            
            // Update cache time
            $_SESSION[SITE_SESSION_ID.$timer_name] = $now;
            //DEBUG: echo "Updating cache for $timer_name, ";
            //DEBUG: echo "Value: ".$_SESSION[SITE_SESSION_ID.$timer_name]."<br>";
            return true;
        }
        
        //DEBUG: echo "NOT updating cache for $timer_name, ";
        //DEBUG: echo "Value: ".$_SESSION[SITE_SESSION_ID.$timer_name]."<br>";
        return false;
    }

    /**
     * Debugging
     * Make a query easier to read by inserting linebreaks at selected places
     * @param string $q The query which should be beautified
     * @return string The query formatted for easy reading
     */
    function queryToHtml($q) {
        $q = str_replace('FROM', '<br>FROM', $q);
        $q = str_replace('AND', '<br>AND', $q);
        $q = str_replace('WHERE', '<br>WHERE', $q);
        $q = str_replace('CASE', '<br>CASE', $q);
        $q = str_replace('LEFT', '<br>LEFT', $q);
        $q = str_replace('ORDER', '<br>ORDER', $q);
        $q = str_replace('[nativecode', '<br><br>[nativecode', $q);
        return "<span style=\"font-size: 10px\">$q</span>";
    }

    /**
     * Debugging
     * Returns the result from a query as a tree structure for easy reading
     * @param array $res The result set from a getAll() call
     * @return string The result as html code
     */
    function resultToHtml($res) {
        ob_start();
        print_r($res);
        $r = ob_get_contents();
        ob_end_clean(); 
        return "<pre>$r</pre>";
    }

    /**
     * Debugging
     * Writes the query (or whatever text entered) directly to the webpage
     * @param string $q The query (or text) which should be written
     * @param boolean $printResult If the query won't return a result (such as an INSERT- or UPDATE-query), then set this to false
     */
    function debug($q, $printResult = true) {
        if ($this->_debug_on) {
            echo
                '<div style="background-color: white;">
                 <span style="color: red;">SQL-query:<br></span>
                 <span style="color: green;">'.
                $this->queryToHtml($q).
                '</span>
                 </div>';

          if ($printResult) {
            echo
                '<div style="background-color: white;">
                 <span style="color: red;">Result:</span><br><span style="color: green;">'.
                $this->resultToHtml($this->_sql->getAll($q)).
                '</span></div>';
          }

          echo '</table><br>';
        }
    }

    /**
     * Error-reporting
     * Writes the error-message directly to the webpage
     * @param string $q The error-message which should be written
     */
    function showError($q) {
        if ($this->_show_errors) {
            echo
                '<div style="background-color: white;">
                 <span style="color: red;">SQL-error:<br></span>
                 <font color="green">'.
                $this->queryToHtml($q).
                '</span></div><br>';
        }
    }

    /**
     * A simple query interface
     * Executes a query on the database and returns the result.<br>
     * Catches an eventual error and prints it.
     * @param string $q The query
     * @return A result set
     */
    function make_query($q) {
        $res = $this->_sql->query($q);
        if (DB::isError($res)) {
            $this->showError($res->userinfo);
            exit;
        }
        return $res;
    }

    /**
     * A simple query interface
     * Executes a query on the database and returns the result.<br>
     * Does not print error messages.
     * @param string $q The query
     * @return A result set
     */
    function make_query_silent($q) {
        $res = $this->_sql->query($q);
        return $res;
    }

    /**
     * A get-all query interface
     * Executes a query on the database and returns the result as an
     * associative array.<br>
     * Catches an eventual error and prints it.
     * @param string $q The query
     * @return The whole result as an asssociative array
     */
    function my_getAll($q) {
        $res = $this->_sql->getAll($q, DB_FETCHMODE_ASSOC);
        if (DB::isError($res)) {
            $this->showError($res->userinfo);
            exit;
        }
        return $res;
    }


    /**
     * Gets the specified column of a table
     * Note: this function is not tested

     */
    function getCol($q, $col = 0) {
        $res = $this->_sql->getCol($q, $col);
        if (DB::isError($res)) {
            $this->showError($res->userinfo);
            exit;
        }
        return $res;
    }


    /**
     * Converts all * to % in the query
     */
    function wildcard($q) {
        return strtr($q, '*', '%');
    }


    /**
     * We only need to slashify stuff if magic_quotes_gpc is off.
     * This function is used to prepare text to be inserted into the database.
     */
    function magic_addslashes($text) {
        if (get_magic_quotes_gpc()) {
            return $text;
        } else {
            return addslashes($text);
        }
    }


    /* MySQL vs. PgSQL wrapper functions below */

    /**
     * Replacement for the mysql_insert_id() function, for use with PgSQL
     * Todo: Not tested yet!!
     */
    /*
    function last_insert_id($tablename, $fieldname) {
        global $db_engine;
        switch ($db_engine) {
            case 'pgsql':
                $q = "SELECT last_value AS last
                      FROM ${tablename}_${fieldname}_seq";
                $res = $this->make_query($q);
                $row = $res->fetchRow($res);
                return $row['last'];
            case 'mysql':
            default:
                return mysql_insert_id();
        }
    }
    */

    /**
     * Creates the correct limit-syntax for different db-engines
     * Defaults to MySQL-syntax
     */
    function smartLimit($offset, $limit) {
        global $db_engine;
        switch ($db_engine) {
            case 'pgsql':
                return " LIMIT $limit OFFSET $offset ";

            case 'mysql':
            default:
                return " LIMIT $offset , $limit ";
        }
    }

    /**
     * Creates the correct date_format-syntax for different db-engines
     * Defaults to MySQL-syntax
     * @param string $fmt A date-format string meant for MySQL, it is replaced with PgSQL-format if applicable
     */
    function dateFormat($field, $fmt) {
        global $db_engine;
        switch ($db_engine) {
            case 'pgsql':
                return " TO_CHAR( $field, '".$this->mysqlDateFormatToPgsql($fmt)."' ) ";
            case 'mysql':
            default:
                return " DATE_FORMAT( $field, '".$fmt."' ) ";
        }
    }

    function mysqlDateFormatToPgsql($fmt) {
        return str_replace(array('%y', '%Y', '%m', '%d', '%H', '%i', '%s'),
                           array('YY', 'YYYY', 'MM', 'DD', 'HH24', 'MI', 'SS'),
                           $fmt);
    }


    function userIsOnline() {
        global $db_engine;
        global $member_online; // In minutes, how long a user is considered logged in
        $activetime = $this->localDate('U') - ($member_online * 60);

        switch ($db_engine) {
            case 'pgsql':
                return "
                 CASE
                   WHEN ( EXTRACT( EPOCH FROM user_lastactive ) > $activetime
                    AND user_id = session_user )
                   THEN 1
                   ELSE 0
                 END ";
            case 'mysql':
            default:
                return "
                 CASE
                   WHEN ( UNIX_TIMESTAMP(user_lastactive) > $activetime
                    AND user_id = session_user )
                   THEN 1
                   ELSE 0
                 END ";
        }
    }

    function extractEpoch($field) {
        global $db_engine;

        switch ($db_engine) {
            case 'pgsql':
                return " EXTRACT( EPOCH FROM $field ) ";
            case 'mysql':
            default:
                return " UNIX_TIMESTAMP( $field ) ";
        }
    }
	
	
	function getMySqlColumnCode ($columnName, $param) {
		$q = "(";
		if (is_array($param)) {
			foreach ($param as $v) {
				if ( ! empty($v) ) $q .= "$columnName = $v OR ";
			}	
			//get rid of last OR
			$q = substr($q, 0, strlen($q)-3).")";
		} elseif ( ! empty($param)) {
			$q = "$columnName = $v";	
		}
		return $q;
	}   

	function getMySqlData ($tabel, $id = "") {
		$q = "SELECT * FROM $tabel ";
		if (! empty($id)) {
			$q .= "WHERE id = $id ORDER BY name";
	        $res = $this->make_query($q);
	        return $res->fetchRow();
		}
		$q .= "ORDER BY name";
		return $this->my_getAll($q);
	}
}

?>
