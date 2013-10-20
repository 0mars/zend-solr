<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../../../application'));
// define('APPLICATION_ENV','testing');
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

// // require_once 'ModelTestCase.php';
// // require_once 'ControllerTestCase.php';
require_once 'Zend/Loader/ClassMapAutoloader.php';
$loader = new Zend_Loader_ClassMapAutoloader();
$loader->registerAutoloadMap(APPLICATION_PATH . '/../data/classmap/autoload_classmap.php');
$loader->register();

$application = new Zend_Application(
		APPLICATION_ENV,
		APPLICATION_PATH.'/configs/application.ini'
);
$application->bootstrap();
