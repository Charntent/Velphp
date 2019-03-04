<?php
namespace vel;
// +----------------------------------------------------------------------
// | VelPHP [ WE CAN DO IT JUST Vel ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2018 http://velphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: VelGe <VelGe@gmail.com>
// +----------------------------------------------------------------------
use vel\Component;

class Config extends Component
{

    public  static  $appConfig = null;

    public static function get($keys = null)
    {
        if(self::$appConfig == null) {
            self::init();
        }
        if($keys == null) {
            return self::$appConfig;
        }
        if(isset(self::$appConfig[$keys])) {
            return self::$appConfig[$keys];
        }

        //如果存在点
        $ret = null;
        if(strpos($keys,'.') !== false) {

           $keysAr = explode('.',$keys);
           foreach ($keysAr as $k => $v) {
               if($k == 0) {
                   $ret = isset(self::$appConfig[$v]) ? self::$appConfig[$v] : null;
               } else {
                   $ret = isset($ret[$v]) ? $ret[$v] : null;
               }
           }
        }

        return $ret;
    }

    public static function init()
    {
        $configFile = APP_PATH.DS.'config'.DS.'config.php';

        if(is_file($configFile)) {
            self::$appConfig = include $configFile;
        }
    }

    public static $instance = null;

    public static function getInstance()
    {
        if(static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

}