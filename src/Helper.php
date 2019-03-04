<?php
use vel\Config;

//助手函数
function gpc($key, $method = 'REQUEST')
{

    $method = strtoupper($method);
    switch ($method) {
        case 'GET':
            $var = &$_GET;
            break;
        case 'POST':
            $var = &$_POST;
            break;
        case 'COOKIE':
            $var = &$_COOKIE;
            break;
        case 'REQUEST':
            $var = &$_REQUEST;
            break;
    }
    return isset($var[$key]) ? $var[$key] : null;
}

// 浏览器友好的变量输出
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        exit();
    } else
        return $output;
}


// URL重定向
function redirect($url, $time = 0, $msg = '')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

if (!function_exists('set_gpc')) {
    function set_gpc($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $r) {
                set_gpc($k, $r);
            }
        } else {
            $_GET[$key] = $_POST[$key] = $_REQUEST[$key] = $_COOKIE[$key] = $value;
        }
    }
}


function gpc_addslashes($string)
{
    if (!is_array($string)) return addslashes($string);
    foreach ($string as $key => $val) $string[$key] = gpc_addslashes($val);
    return $string;
}

function gpc_stripslashes($string)
{
    if (!is_array($string)) return stripslashes($string);
    foreach ($string as $key => $val) $string[$key] = gpc_stripslashes($val);
    return $string;
}


function tpl($tpl, $sys = false)
{
    $tpl = str_replace('\\',DS,$tpl);
    $path = VEL_VIEWS . TPL;
    $sypath = VEL_ROOT .DS. 'tpl';
    $cachepath = VEL_RUNTIMES . 'Tplcache' . DS;
    $tpl = $tpl . Config::get('tplSuffix');

    $cusTpl = $path.DS.$tpl;
    $flag = false;
    if(file_exists($cusTpl)) {
        $flag = true;
    }

    $ins = new \vel\View($tpl, $sys ? $sypath:($flag?$path:$sypath), $cachepath);
    return $ins->view();
}

function s404()
{
    header("HTTP/1.1 404 Not Found");
    if(\vel\Request::instance()->isAjax()) {
        return [
            'error'=> 1,
            'code' => 404,
            'msg'  => '404，页面找不到了',
            'data' => []
        ];
    }
    include tpl('404',true);
}



function  U( $url = '',$host=null)
{
    if( ! is_null($host) ) {
        $temp = ($url=='' || $url=='/')?'':$url;
        return $host.'/'.$temp;
    }
    $folder = \vel\Route::removeLastFolder();
    $host = $_SERVER['HTTP_HOST'];
    return (strpos($host,'http') !== false ?$host :'//'.$host) .$folder.'/'.$url.Config::get('urlSuffix');
}

if (!function_exists('randOne')) {
    function randOne($ar = [])
    {
        return $ar[array_rand($ar)];
    }
}

if (!function_exists('tb')) {
    function tb($tbName = '')
    {
        $config = \vel\Config::get('db_config');
        $res = randOne($config['read']);
        return $res['database_prex'].$tbName;
    }
}

if (!function_exists('getDbInfo')) {
    function getDbInfo($type = 'read')
    {
        $config = \vel\Config::get('db_config');
        return randOne($config[$type]);
    }
}

if(!function_exists('randomkeys'))
{
    function randomkeys( $length , $type = 0)
    {
        $pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key ="";
        for ($i=0;$i<$length;$i++) {
            $key .= $pattern{mt_rand(0,$type==0?35:$type)};    //生成php随机数
        }
        return $key;
    }
}

if(!function_exists('message'))
{
    function message($msg,$redirect=-1,$time=1250,$e=1,$tpl='',$sys=0){

        if((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") || \vel\Request::instance()->isAjax())      {
            // ajax 请求的处理方式
            show_json(array("error"=>$e,"msg"=>$msg,"tourl"=>$redirect,'times'=>$time));
        }else{
            if($redirect=='-1') $redirect = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'javascript:history.go(-1);';
            if($redirect=='back') $redirect = 'javascript:history.go(-1);';
            extract($GLOBALS);
            $GLOBALS['is_user_tpl'] = true;

            $tpl_now =  'message';
            if($e ==0){
                $tpl_now =  'success';
            }
            require tpl($tpl?$tpl:$tpl_now,$sys);
            exit;
        }
    }
}

if(!function_exists('show_json'))
{
    function show_json($ar)
    {
        echo json_encode($ar, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

if(!function_exists('web_local'))
{
    function web_local()
    {
        if(PHP_SAPI == 'cli') {
            return '';
        }
        return Config::get('request.host');
    }
}


if (!function_exists('alert')) {

    function alert($msg, $redirect = -1, $time = 1250)
    {
        if ($redirect == '-1') $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'javascript:history.go(-1);';
        if ($redirect == 'back') $redirect = 'javascript:history.go(-1);';
        die('<script type="text/javascript">window.top.message("' . javascript($msg) . '",' . $time . ');window.location="' . $redirect . '";</script>');
    }

}
if (!function_exists('javascript')) {
    function javascript($string)
    {
        return addslashes(str_replace(array("\r", "\n"), array('', ''), $string));
    }
}

if (!function_exists('session')) {

    function session($name, $v = null)
    {
        $access_token = gpc('access_token');

        if($access_token && in_array($name,['user_id','username','openid_rm','openid_sym'])) {

            $Tokens = new \app\models\Tokens();
            $timestamp = time();
            //自动登录
            $token_info = $Tokens->get("(token = '$access_token' OR token_id='$access_token')",'token_id,token_time,user_id,username,openid_rm,openid_sym',false);

            if($token_info) {
                if ($token_info['token_time'] > $timestamp) {
                    //更新一下
                    //$token_id = $Tokens->createTokenId();
                    //$Tokens->token = $token_id;
                    $Tokens->token_id   = $token_info['token_id'];
                    $Tokens->token_time = $timestamp+604800;
                    $Tokens->save();
                    return isset($token_info[$name])?$token_info[$name]:false;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            if($v === null){
                if(isset($_SESSION[$name]))
                    return $_SESSION[$name];
                else
                    return null;
            }else{
                $_SESSION[$name] = $v;
            }
        }
    }

}


function is_mobile()
{
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $mobile_agents = ["240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte"];
    $is_mobile = false;
    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }
    return $is_mobile;
}



function ucNose($str)
{
    $str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
        return strtoupper($matches[2]);
    },$str);
    return ucfirst($str);
}


function humpToLine($str){
    $str = preg_replace_callback('/([A-Z]{1})/',function($matches){
        return '_'.strtolower($matches[0]);
    },$str);
    return $str;
}


if(!function_exists('get_thumb'))
{
    function get_thumb($headimg = ''){
        if($headimg == '') {
            return BASEURL.'/resources/images/login_bg.jpg';
        }
        if(strstr($headimg,'http')) {
            return $headimg;
        }else{
            return BASEURL.'/'.$headimg;
        }
    }
}

if(!function_exists('db'))
{
    function db($table = null)
    {
        $instance = \vel\Db::getInstance();
        return $table ? $instance->tb($table) : $instance;
    }
}

if(!function_exists('dateFormat'))
{
    function dateFormat($time,$format = 'Y-m-d H:i:s')
    {
        return $time == 0 ? 0 :date($format,$time);
    }
}
