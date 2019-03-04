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

class Cookie {


    // 过期时间
    public $expire = 31536000;

    // 有效的服务器路径
    public $path = '/';

    // 有效域名/子域名
    public $domain = '';

    // 仅通过安全的 HTTPS 连接传给客户端
    public $secure = false;

    // 仅可通过 HTTP 协议访问
    public $httponly = false;

    public $cookie;

    function __construct()
    {
        $this->cookie  = $_COOKIE;
    }


    public static $instance = null;

    public static function getInstance()
    {
        if(static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // 取值
    public function get($name = null)
    {
        if($name == null) {
            return $this->cookie;
        }else{
            return isset($this->cookie[$name])?$this->cookie[$name]:null;
        }
    }


    // 赋值
    public function set($name, $value, $expire = null)
    {

        setcookie($name, $value, time() + (is_null($expire) ? $this->expire : $expire), $this->path, $this->domain, $this->secure, $this->httponly);
        if($value == null) {
            unset($this->cookie[$name]);
        }
    }

    // 判断是否存在
    public function has($name)
    {
        return is_null($this->get($name)) ? false : true;
    }

    // 删除
    public function delete($name)
    {
        $this->set($name, null,-3600);
    }

    // 清空当前域所有cookie
    public function clear()
    {
        foreach ($this->cookie as $name => $value) {
            $this->set($name, null);
        }
    }

}