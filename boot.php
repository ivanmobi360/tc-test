<?php

 
define('PHPUNIT_ENV', true);
define('TEST_ENV', true);
require_once '../website/config.php';

//If we want to override some problematic class (like cache) we place it here (this could be more dynamic)
function register_override_classes(){
    $loader = function($className){
        $file_name = SITE_PATH . '../test/override/' . str_replace('\\', '/', $className) . '.php';
        //Utils::log("Lookin for $file_name");
        if (file_exists($file_name)) {
            include_once( $file_name );
            return;
        }
    };
    spl_autoload_register($loader, false);
}


//register_override_classes(); //Cache did speed up the page. The problem was with the anitivirus.

// Run the init file
require_once PATH_INCLUDES . 'init.php';
Utils::clearLog();
file_put_contents('c:\wamp\logs\genquery.log', '');

//test suite
function testLoader($className) {
    if(file_exists(SITE_PATH.'../test/'. $className . '.php')){
        include_once(SITE_PATH.'../test/'. $className . '.php');
        return;
      }
  
    //for helper domain-like classes
    if(file_exists(SITE_PATH.'../test/includes/'. $className . '.php')){
        include_once(SITE_PATH.'../test/includes/'. $className . '.php');
        return;
    }
  
    //admin360 classes
	$file_name = SITE_PATH . '../admin360/' . str_replace('\\', '/', $className) . '.php';
	//Utils::log("Lookin for $file_name");
	if (file_exists($file_name)) {
		include_once( $file_name );
	}
  
}
spl_autoload_register('testLoader', false);

//error handler function
function customError($errno, $errstr, $error_file, $error_line) {
  $msg = "Error: [$errno] $errstr. File: $error_file. Line: $error_line ";
  Utils::log($msg);
  echo $msg;
  die();
  return false;
  }

//set_error_handler("customError");

// For Ext ???
$GLOBALS['version'] = '1.4';
$GLOBALS['api_debug'] = false;
$GLOBALS['api_expire'] = 300;