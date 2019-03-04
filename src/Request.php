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

class Request
{

    // ROUTE参数
    protected $_route = [];

    // GET参数
    protected $_get = [];

    // POST参数
    protected $_post = [];

    // PUT参数
    protected $_put = [];

    // FILES参数
    protected $_files = [];

    // COOKIE参数
    protected $_cookie = [];

    // SERVER参数
    protected $_server = [];

    // REQUEST参数
    protected $_request = [];

    // _params参数
    protected $_params = [];

    // HEADER参数
    protected $_header = [];

    public static $instance = null;

    public static function instance()
    {
        if(static::$instance === null) {
            static::$instance  = new static();
            static::$instance->onLoad();
        }
        return static::$instance;
    }

    // 初始化事件
    public function onLoad()
    {

        $this->_get     = $_GET;
        $this->_post    = $_POST;
        $this->_request = $_REQUEST;
        $this->_files   = $_FILES;
        $this->_cookie  = $_COOKIE;
        $input = file_get_contents('php://input');
        if($input) {
            parse_str($input, $dataToArr);
            $this->_put = $dataToArr;
        }

        $this->setServer();
        $this->setHeader();
    }

    public function getMethod() {
        return $this->fetch(strtolower('REQUEST_METHOD'), $this->_server);
    }

    // 设置ROUTE值
    public function setRoute($route)
    {
        $this->_route = $route;
    }

    // 设置SERVER值
    protected function setServer()
    {
        $this->_server = array_change_key_case($_SERVER, CASE_LOWER);

    }

    // 设置HEADER值
    protected function setHeader()
    {
        $header = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $header[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
            }
        }
        $this->_header = $header;

    }

    // 提取GET值
    public function get($name = null)
    {
        return $this->fetch($name, $this->_get);
    }

    // 提取POST值
    public function post($name = null)
    {
        return $this->fetch($name, $this->_post);
    }

    // 提取PUT值
    public function put($name = null)
    {
        return $this->fetch($name, $this->_put);
    }

    // 提取REQUEST值
    public function request($name = null)
    {
        $data = $this->getRawBody();
        if(!is_array($data)) {
            $data = json_decode($data,true);
        }
        if(is_array($data)) {
            $this->_request = array_merge($this->_request,$data);
        }
        return $this->fetch($name, $this->_request);
    }

    // 提取FILES值
    public function files($name = null)
    {
        return $this->fetch($name, $this->_files);
    }

    // 提取ROUTE值
    public function route($name = null)
    {
        return $this->fetch($name, $this->_route);
    }

    // 提取COOKIE值
    public function cookie($name = null)
    {
        return $this->fetch($name, $this->_cookie);
    }

    // 提取SERVER值
    public function server($name = null)
    {
        return $this->fetch($name, $this->_server);
    }

    // 提取HEADER值
    public function header($name = null)
    {
        return $this->fetch($name, $this->_header);
    }

    // 提取数据
    public  function fetch($name, $container)
    {

        return is_null($name) ? $container : (isset($container[$name]) ? $container[$name] : null);
    }

    // 返回原始的HTTP包体
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * 当前是否Ajax请求
     * @access public
     * @param bool $ajax  true 获取原始ajax请求
     * @return bool
     */
    public function isAjax($ajax = false)
    {
        $resType = $this->server(strtolower('HTTP_X_REQUESTED_WITH'));
        $value  = strtolower($resType);
        //$result = ('xmlhttprequest' == $value) ? true : false;
        if ( 'xmlhttprequest' == $value || $this->isJson() || $ajax == true) {
            return true;
        } else {
            return false;
        }
    }

    public function isJson()
    {
        $format = $this->request('format');
        return $format == 'json'?true:false;
    }

    /**
     * 当前是否Pjax请求
     * @access public
     * @param bool $pjax  true 获取原始pjax请求
     * @return bool
     */
    public function isPjax($pjax = false)
    {
        $result = !is_null($this->server('HTTP_X_PJAX')) ? true : false;
        if (true === $pjax) {
            return $result;
        } else {
            return $result;
        }
    }

    /**
     * 获取客户端IP地址
     * @param integer   $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean   $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    public function ip()
    {
        return get_client_ip();
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测顶级域名
     * @access public
     * @return bool
     */
    public  function getTopHost(){

        $url   = $_SERVER['HTTP_HOST'];
        $data = explode('.', $url);
        $co_ta = count($data);
        //判断是否是双后缀
        $zi_tow = true;
        $host_cn = 'com.cn,net.cn,org.cn,gov.cn';
        $host_cn = explode(',', $host_cn);
        foreach($host_cn as $host){
            if(strpos($url,$host)){
                $zi_tow = false;
            }
        }
        //如果是返回FALSE ，如果不是返回true
        if($zi_tow == true){
            $host = $data[$co_ta-2].'.'.$data[$co_ta-1];
        }else{
            $host = $data[$co_ta-3].'.'.$data[$co_ta-2].'.'.$data[$co_ta-1];
        }
        return $host;
    }


    public function isPost()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'])== 'POST';
    }

    public function isGet()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'])== 'GET';
    }

    //获取当前请求的时间
    public function nowTime($isSys = false)
    {
        $time = $this->server('REQUEST_TIME');
        return $isSys === true ? time():($time ? $time : time());
    }

    /**
     * 获取请求的参数
     * @param mixed   $sence 场景ID
     * @return mixed
     */
    public function params($sence = null)
    {
        return $this->request();
    }

}