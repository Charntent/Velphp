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




class Http
{
    private $ch = null; // curl handle
    private $headers = array();// request header
    private $proxy = null; // http proxy
    private $timeout = 5;    // connnect timeout
    private $httpParams = null;


    public function __construct()
    {
        $this->ch = curl_init();
    }

    /**
     * 设置http header
     * @param $header
     * @return $this
     */
    public function setHeader($header) {
        if(is_array($header)){
            curl_setopt($this->ch, CURLOPT_HTTPHEADER  , $header);
        }
        return $this;
    }

    /**
     * 设置http 超时
     * @param int $time
     * @return $this
     */
    public function setTimeout($time) {
        // 不能小于等于0
        if($time <= 0) {
            $time = 5;
        }
        //只需要设置一个秒的数量就可以
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $time);
        return $this;
    }


    /**
     * 设置http 代理
     * @param string $proxy
     * @return $this
     */
    public function setProxy($proxy) {
        if($proxy){
            curl_setopt ($this->ch, CURLOPT_PROXY, $proxy);
        }
        return $this;
    }

    /**
     * 设置http 代理端口
     * @param int $port
     * @return $this
     */
    public function setProxyPort($port) {
        if(is_int($port)) {
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $port);
        }
        return $this;
    }

    /**
     * 设置来源页面
     * @param string $referer
     * @return $this
     */
    public function setReferer($referer = ""){
        if (!empty($referer))
            curl_setopt($this->ch, CURLOPT_REFERER , $referer);
        return $this;
    }

    /**
     * 设置用户代理
     * @param string $agent
     * @return $this
     */
    public function setUserAgent($agent = "") {
        if ($agent) {
            // 模拟用户使用的浏览器
            curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
        }
        return $this;
    }

    /**
     * http响应中是否显示header，1表示显示
     * @param $show
     * @return $this
     */
    public function showResponseHeader($show) {
        curl_setopt($this->ch, CURLOPT_HEADER, $show);
        return $this;
    }


    /**
     * 设置http请求的参数,get或post
     * @param array $params
     * @return $this
     */
    public function setParams($params) {
        $this->httpParams = $params;
        return $this;
    }

    /**
     * 设置证书路径
     * @param $file
     */
    public function setCainfo($file) {
        curl_setopt($this->ch, CURLOPT_CAINFO, $file);
    }

    /**
     * 设置COOKIE
     * @param $cookie_file
     */
    public function setCookieFile($cookie_file) {
        if($cookie_file){
            curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        return $this;
    }



    /**
     * 模拟GET请求
     * @param string $url
     * @param string $dataType
     * @return bool|mixed
     */
    public function get($url, $dataType = 'text') {

        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($this->ch, CURLOPT_SSLVERSION, 1);
        }
        // 设置get参数
        if(!empty($this->httpParams) && is_array($this->httpParams)) {
            if(strpos($url, '?') !== false) {
                $url .= http_build_query($this->httpParams);
            } else {
                $url .= '?' . http_build_query($this->httpParams);
            }
        }

        // end 设置get参数
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1 );
        $content = curl_exec($this->ch);
        $status = curl_getinfo($this->ch);

        curl_close($this->ch);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($dataType == 'json') {
                $content = json_decode($content, true);
            }
            return $content;
        } else {
            return FALSE;
        }
    }




    /**
     * 模拟POST请求
     *
     * @param string $url
     * @param array $fields
     * @param string $dataType
     * @return mixed
     *
     * HttpCurl::post('http://api.example.com/?a=123', array('abc'=>'123', 'efg'=>'567'), 'json');
     * HttpCurl::post('http://api.example.com/', '这是post原始内容', 'json');
     * 文件post上传
     * HttpCurl::post('http://api.example.com/', array('abc'=>'123', 'file1'=>'@/data/1.jpg'), 'json');
     */
    public function post($url, $dataType='text') {
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($this->ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        // 设置post body
        if(!empty($this->httpParams)) {
            if(is_array($this->httpParams)) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->httpParams));
            } else if(is_string($this->httpParams)) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->httpParams);
            }
        }
        // end 设置post body
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($this->ch, CURLOPT_POST, true);
        $content = curl_exec($this->ch);
        $status = curl_getinfo($this->ch);
        curl_close($this->ch);

        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($dataType == 'json') {
                $content = json_decode($content, true);
            }
            return $content;
        } else {
            return FALSE;
        }
    }

    public function saveCookie($url,$cookie_file){
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);//获取到的内容作为变量存储，设置为1或者TRUE就不直接输出
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);//链接时间
        curl_setopt($this->ch,CURLOPT_COOKIEJAR,$cookie_file);//获取COOKIE并存储，把获得到的cookie存储为文件
        $contents = curl_exec($this->ch);
        curl_close($this->ch);
        return $this;
    }

    public function saveCodeImg($verify_code_url,$cookie_file,$codeType){

        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $verify_code_url);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $img = curl_exec($this->ch);
        curl_close($this->ch);

        $tempName = WL_CACHEROOT.DS."verifyCode_".time().mt_rand(1000,20000).".jpg";
        $fp = fopen($tempName,"w");
        fwrite($fp,$img);
        fclose($fp);

        $image = file_get_contents($tempName);
        $base64str= base64_encode($image);
        @unlink($tempName);
        $code = $this->getCodeByJuhe($base64str,$codeType);

        return $code;
    }

    public function getCodeByJuhe($base64str,$codeType = 4005){

        $this->ch = curl_init();
        $params = array(
            'codeType' => $codeType,
            'base64Str' => $base64str,
            'key' => 'b9be7774d858b943eb10eb06458d6805'
        );

        $results = $this->setParams($params)->post('http://op.juhe.cn/vercode/index','json');


        if(isset($results['error_code']) &&  $results['error_code'] == 0) {
            return $results['result'];
        }
        return '';
    }






}