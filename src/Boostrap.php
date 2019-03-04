<?php

namespace vel;


define('VEL_VERSION', '1.0.0');
define("VEL_ROOT",dirname(__FILE__));
if(!defined('DS')) define('DS',DIRECTORY_SEPARATOR);

define('VEL_START_TIME', microtime(true));
define('VEL_START_MEM', memory_get_usage());
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
define('VEL_LIBARIES', APP_PATH . DS . 'libraries'.DS);

defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
define("VEL_RUNTIMES",ROOT_PATH.'runtimes'.DS);
define("VEL_CACHEROOT",ROOT_PATH.'runtimes'.DS.'Cache'.DS);
//模块控制器
define("VEL_MODULES",APP_PATH.DS.'modules'.DS);
//模板
define("VEL_VIEWS",APP_PATH.DS.'views'.DS);
session_start();