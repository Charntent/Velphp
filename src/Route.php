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
use vel\Request;

class Route extends Component
{
    public static $controler = null;
    public static $actioner = null;

    public static $routesAr = [];

    // 路由规则
    private static $rules = [
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'delete'  => [],
        'patch'   => [],
        'head'    => [],
        'options' => [],
        '*'       => [],
        'alias'   => [],
        'domain'  => [],
        'pattern' => [],
        'name'    => [],
    ];

    // REST路由操作方法定义
    private static $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/:id/edit', 'edit'],
        'read'   => ['get', '/:id', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/:id', 'update'],
        'delete' => ['delete', '/:id', 'delete'],
    ];

    // 不同请求类型的方法前缀
    private static $methodPrefix = [
        'get'    => 'get',
        'post'   => 'post',
        'put'    => 'put',
        'delete' => 'delete',
        'patch'  => 'patch',
    ];

    // 子域名
    private static $subDomain = '';
    // 域名绑定
    private static $bind = [];
    // 当前分组信息
    private static $group = [];
    // 当前子域名绑定
    private static $domainBind;
    private static $domainRule;
    // 当前域名
    private static $domain;
    // 当前路由执行过程中的参数
    private static $option = [];

    /**
     * 注册变量规则
     * @access public
     * @param string|array  $name 变量名
     * @param string        $rule 变量规则
     * @return void
     */
    public static function pattern($name = null, $rule = '')
    {
        if (is_array($name)) {
            self::$rules['pattern'] = array_merge(self::$rules['pattern'], $name);
        } else {
            self::$rules['pattern'][$name] = $rule;
        }
    }

    public static function getPathInfo()
    {
        if(isset($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }elseif(isset($_SERVER['ORIG_PATH_INFO'])) {
            return  $_SERVER['ORIG_PATH_INFO'];
        }elseif(isset($_SERVER['REDIRECT_URL'])) {
            return  $_SERVER['REDIRECT_URL'];
        }elseif(isset($_SERVER['REDIRECT_QUERY_STRING'])){
            return  $_SERVER['REDIRECT_QUERY_STRING'];
        }
        return null;
    }

    //去除目录
    public static function removeLastFolder()
    {
        $phpSelfArs = explode('/',$_SERVER['PHP_SELF']);
        array_pop($phpSelfArs);
        $sss = implode('/',$phpSelfArs);
        return $sss;

    }

    //去除跟目录到目录的
    public static function removeFolder()
    {
        $pathInfo = self::getPathInfo();
        $pathInfo = preg_replace('/\/+/','/',$pathInfo);
        $remove = self::removeLastFolder();
        return str_replace($remove,'',$pathInfo);
    }

    //注册
    public static function registerRoute()
    {
        $request = Request::instance();
        $method  = $request->getMethod();
        $routes  = Config::get('routes');
        if($routes) {
            self::$routesAr =  array_merge(self::$routesAr,$routes);
        }

        $pathInfo = gpc_addslashes(self::removeFolder());

        //获取控制器和动作
        $curentRouts = [];
        if(isset(self::$routesAr[$pathInfo])) {
            $curentRouts = self::$routesAr[$pathInfo];
        }else{
            $tmInfo = \rtrim($pathInfo,'/');
            if(isset(self::$routesAr[$tmInfo])) {
                $curentRouts = self::$routesAr[$tmInfo];
            }else{
                //去除后缀
                $pathInfo  = self::removeSuffix($pathInfo);
                define('VELPATHINFO',trim($pathInfo,'/'));
                //开始正则匹配
                $pathInfoPreg = preg_replace('/(\d+)/',':id',$pathInfo);
                if(isset(self::$routesAr[$pathInfoPreg])) {
                    $curentRouts = self::$routesAr[$pathInfoPreg];
                }
            }
        }
        if(!empty($curentRouts)) {
            if(strtolower($method) == strtolower($curentRouts[0])) {
                return [$curentRouts[1],$curentRouts[2]];
            }
        }
        return self::parseInfo($pathInfo);
    }

    public static function removeSuffix ($pathInfo = null)
    {
        $urlPrefix = Config::get('urlSuffix');
        $l  = strlen($urlPrefix);
        $pl = strlen($pathInfo);
        if($pl < $l) {
            return $pathInfo;
        }
        $urlSuffix = substr($pathInfo,$pl-$l,$pl);
        if($urlSuffix == $urlPrefix) {
            return substr($pathInfo,0,$pl-$l);
        }else{
            return $pathInfo;
        }
    }

    public static function parseInfo($pathInfoStr = null)
    {
        $namespace = Config::get('app_namespace');
        if($pathInfoStr == '' || $pathInfoStr == '/') {
            return [
                '\\'.$namespace.'\\'.Config::get('default_module').'\\'.Config::get('default_controller'),
                Config::get('default_action')
            ];
        }
        $pathInfo = explode('/',trim($pathInfoStr,'/'));
        $l = count($pathInfo);

        switch ($l) {
            case 0:
                return null;
                break;
            case 1:
                return [
                    '\\'.$namespace.'\\'.array_shift($pathInfo).'\index',
                    'index'
                ];
                break;
            case 2:
                return [
                    '\\'.$namespace.'\\'.array_shift($pathInfo).'\\'.array_shift($pathInfo),
                    'index'
                ];
                break;
            case 3:
                return [
                    '\\'.$namespace.'\\'.array_shift($pathInfo).'\\'.array_shift($pathInfo),
                    array_shift($pathInfo)
                ];
                break;
            default :

                for($i=3;$i<$l;$i=$j+1){
                    $j = $i+1;
                    set_gpc($pathInfo[$i],isset($pathInfo[$j])?$pathInfo[$j]:'');
                }

                //大于3个
                return [
                    '\\'.$namespace.'\\'.$pathInfo[0].'\\'.$pathInfo[1],
                    $pathInfo[2]
                ];
                break;
        }
    }
}

