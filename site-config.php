<?php

// A unique "site-id", to prevent session-cookies to leak to other sites
// on the same domain. 
define('SITE_SESSION_ID', '');

// Where to find the main-community-files, modules, configs, etc
define('MODULES_DIR', '');

// Database settings
$db_user = '';
$db_pass = '';
$db_host = '';
$db_name = '';
$db_engine = 'mysql';

// datasource in in this style: dbengine://username:password@host/database
$datasource = $db_engine.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;

