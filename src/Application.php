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
use vel\Route;
use vel\Config;

class Application extends Component
{
    /**
     * @var bool 应用调试模式
     */
    public static $debug = true;
    /**
     * @var bool 是否初始化过
     */
    protected static $init = false;

    public static function initApp()
    {
        if(self::$init == false) {
            header("Content-type: text/html; charset=utf-8");
            //设置时区
            function_exists('date_default_timezone_set') && date_default_timezone_set(Config::get('default_timezone'));

            self::$debug = Config::get('debug')?true:false;
            if(self::$debug === true){
                error_reporting(E_ALL);
                ini_set('display_errors', '1');
            }else{
                error_reporting(0);
            }
            \vel\Error::register();
            //加载系统的helper
            include __DIR__.DS.'Helper.php';
            //过滤
            \vel\Filter::input(true);
            //注册模板
            if (self::checkTpl()) {
                //域名相同，看是否是手机
                $tpl = 'pc';
                if(is_mobile()) {
                    $tpl =  'mobile';
                }
                $thisTpl = self::getTpl($tpl);
                if($thisTpl == null) {
                    die('TPL IS NOT EXITS');
                }
                define('TPL',$thisTpl);

            }else{
                //域名不相同，域名决定模板
                $domain = $_SERVER['HTTP_HOST'];
                $thisTpl = self::getTpl('pc',$domain);
                if($thisTpl == null) {
                    die('THIS DOMAIN IS NOT IN WEB');
                }
                define('TPL',$thisTpl);
            }

            self::$init = true;
        }
    }

    public static function checkTpl()
    {
        $tempaltes = Config::get('template');
        return $tempaltes['pc']['domain'] == $tempaltes['mobile']['domain'];
    }

    public static function getTpl($zd = 'pc', $domain = null)
    {
        $tempaltes = Config::get('template');
        if ($domain == null) {
            return $tempaltes[$zd]['tpl'];
        } else {
            foreach ($tempaltes as $k => $v) {
                if ($v['domain'] == $domain) {
                    return $v['tpl'];
                    break;
                }
            }
            return null;
        }
    }

    public static function run()
    {
        self::initApp();

        $routes = Route::registerRoute();

        $class = self::parseCtl($routes[0]);

        $action = $routes[1];
        define('VEL_ACTION',$action);
        if(!class_exists($class)) {
            $namespace = Config::get('app_namespace');
            $default_module = Config::get('default_module');
            $class = $namespace.'\\'.'modules'.'\\'.$default_module.'\\EmptyCtl';
            $action = 'index';
        }

        if (is_callable($class) || class_exists($class)) {
            try {
                $response =  self::voteClass($class,$action,[]);
                Response::getInstance()->data($response)->out();
            }catch (\Exception $e) {
                dump($e->getFile().$e->getLine().$e->getMessage());
            }

        } else {
            Response::getInstance()->data(s404())->out();
        }

    }

    protected static function parseCtl($class)
    {
        $ctlArs = explode('\\',trim($class,'\\'));
        $ctlArs[2] = ucfirst($ctlArs[2]);
        define('VEL_MODULE',$ctlArs[1]);
        define('VEL_CONTROLLER',$ctlArs[2]);
        $rec = ['modules'];
        array_splice($ctlArs,1,0,$rec);
        return implode('\\',$ctlArs);
    }

    public static function voteClass($cotroller,$action,$paramsInput){

        # 获取类的反射
        $controllerReflection = new \ReflectionClass($cotroller);
        # 不能实例化，就是不能new一个的话，这个游戏就玩不下去了啊
        if (!$controllerReflection->isInstantiable()) {
            //throw new \RuntimeException("{$controllerReflection->getName()}不能被实例化");
            Response::getInstance()->data(s404())->out();
        }

        if(empty($paramsInput)) {
            $request     = Request::instance()->onLoad();
            $paramsInput = Request::instance()->request();
        }

        # 获取对应方法的反射
        if (!$controllerReflection->hasMethod($action)) {
            //throw new RuntimeException("{$controllerReflection->getName()}没有指定的方法:{$action}");
            Response::getInstance()->data(s404())->out();
        }

        $actionReflection = $controllerReflection->getMethod($action);

        # 获取方法的参数的反射列表（多个参数反射组成的数组）
        $paramReflectionList = $actionReflection->getParameters();

        # 参数，用于action
        $params = [];
        # 循环参数反射
        # 如果存在路由参数的名称和参数的名称一致，就压进params里面
        # 如果存在默认值，就将默认值压进params里面
        # 如果。。。没有如果了，异常
        foreach ($paramReflectionList as $paramReflection) {
            # 是否存在同名字的路由参数
            if (isset($paramsInput[$paramReflection->getName()])) {
                $params[] = $paramsInput[$paramReflection->getName()];
                continue;
            }
            # 是否存在默认值
            if ($paramReflection->isDefaultValueAvailable()) {
                $params[] = $paramReflection->getDefaultValue();
                continue;
            }
            # 异常
            throw new \RuntimeException(
                "{$controllerReflection->getName()}::{$actionReflection->getName()}的参数{$paramReflection->getName()}必须传值"
            );
        }
        # 调起
        $instance = $controllerReflection->newInstance();
        $response = null;

        foreach (['onLoad','beforeController','afterController'] as $k => $v ) {
            if ($controllerReflection->hasMethod($v)) {
                $onLoadReflection = $controllerReflection->getMethod($v);
                $response = $onLoadReflection->invokeArgs($instance, []);
                if($response !== null) {
                    break;
                }
            }
        }

        if($response !== null) {
            return $response;
        }

        return $actionReflection->invokeArgs($instance, $params);
    }

}
