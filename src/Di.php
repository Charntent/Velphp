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

class Di extends Component implements \ArrayAccess
{
    private $container = null;
    public static $instance = null;
    public static function getInstance()
    {
        if(static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function set($class,$arguments)
    {
       $callback = null;
       if(is_string($arguments)) {
           if(is_callable($arguments))
               $callback = new $arguments();
       }
       if(is_callable($arguments) && $arguments instanceOf  \Closure) {
           $callback = $arguments();
       }

       if($callback != null) {
           $this->container[$class] = $callback;
       }
    }

    public function get($class)
    {
        return isset($this->container[$class]) ? $this->container[$class] : null;
    }

    //设置数据
    public function  __set($name,$value)
    {
        $this->set($name,$value);
    }

    //获取数据
    public function __get($name)
    {
        return $this->get($name);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([Di::getInstance(),$name],$arguments);
    }

    /*AarrayAccess接口实现开始*/
    public function offsetExists($key)
    {
        return $this->get($key) ? true : false;
    }

    public function offsetGet($key)
    {
        $rs = $this->get($key);
        return $rs ? $rs : null;
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key)
    {
        unset($this->container[$key]);
    }


}