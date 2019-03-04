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
use vel\Db;

class Controller extends Component
{
    public $request = null;
    public $response;
    public $className;
    public $tempDatas = [];
    public $db = null;
    public $title = '',$keywords = '',$description = '';


    public function onLoad()
    {
        $this->request = Request::instance();
        $this->request->onLoad();
        $this->db = Db::getInstance();
    }

    function getRequest()
    {
        return $this->request;
    }
    public function __set($key,$value)
    {

    }

    function assign($data,$value = '')
    {
        if(is_array($data)) {
            $this->makeAssign($data);
        }elseif(is_string($data)){
            $this->tempDatas[$data] = $value;
        }
    }

    public function hasAssign($assignName)
    {
        return isset($this->tempDatas[$assignName])?true:false;
    }

    function makeAssign($ar)
    {
        if(empty($ar)) return $this;
        foreach ($ar as $k=>$v) {
            $this->tempDatas[trim($k)] = $v;
        }
        return $this;
    }

    public function initRequest()
    {
        if($this->request == null) {
            $this->request = Request::instance();
        }
    }

    public function success($msg,$data = [],$redirect = null,$time = 1000)
    {
        $this->initRequest();
        if($this->request->isAjax() || $this->request->_request('format') == 'json') {
            return [
                'error' => 0,
                'code'  => 200,
                'msg' => $msg,
                'data' => $data
            ];
        } else {
            $this->transData(['redirect'=>$redirect,'time'=>$time,'seoname'=>'成功提示_','msg' => '成功提示_'.$msg]);
            return $this->fetch('success',[],true);
        }

    }

    public function error($msg,$redirect = null,$data = [],$time = 1000)
    {
        if($this->request == null) {
            $this->request = Request::instance();
        }
        if($this->request->isAjax() || $this->request->request('format') == 'json') {
            return [
                'error'=> 1,
                'code' => 400,
                'msg'  => $msg,
                'data' => $data
            ];
        } else {
            $redirect = ($redirect == null?self::getRefer():$redirect);
            $this->transData(['redirect'=>$redirect,'time'=>$time,'seoname'=>'错误提示_','msg' => '错误提示_'.$msg]);
            return $this->fetch('message',[],true);
        }
    }

    function fetch( $temp = '' ,$assigns = [],$sys = false)
    {

        if(!$this->hasAssign('seoname')) {
            $this->assign('seoname','');
        }
        if(is_array($temp)) {
            $assigns = array_merge($assigns,$temp);
        }

        if(Request::instance()->isAjax()) {
            return $this->success('获取成功',$assigns);
        }

        $this->className = strtolower(get_class($this));

        $this->transData($assigns);
        $tplName = $this->pasreTpl($this->className);
        $this->getMca($tplName);

        extract($this->tempDatas,EXTR_SKIP);
        $tplName .= '\\'.VEL_ACTION;
        $tplName = str_replace('\\',DS,$tplName);

        ob_start();
        if($temp == null || is_array($temp)) {
            include tpl($tplName,$sys);
        }else{
            $temp = self::checkTemp($temp,$sys);
            include tpl($temp,$sys);
        }
        return ob_get_clean();
    }

    private function pasreTpl($className)
    {
        return str_replace(Config::get('app_namespace').'\\modules\\','',$className);
    }

    private static function getRefer()
    {
        return isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
    }

    private  function getMca($tplName)
    {
        /*$classNameArs = explode('\\',$tplName);
        if(count($classNameArs) == 1) {
            $classNameArs[1] = 'index';
            $classNameArs[2] = 'index';
        }
        if(count($classNameArs) == 2) {
            $classNameArs[2] = 'index';
        }
        list($m,$c,$a) = $classNameArs;*/

        $this->assign([
            'm'=>  VEL_MODULE,
            'c'=>  VEL_CONTROLLER,
            'a' => VEL_ACTION,
            'debug'=>VEL_DEBUG
        ]);

    }

    private  function checkTemp($temp = '',$sys = false)
    {
        $classNameArs = explode('\\',$this->className);
        $tempAr = explode('/',$temp);
        if($sys == false && count($tempAr) <= 2) {
            $tempAr = array_merge([$classNameArs[2]],$tempAr);
        }
        return implode('\\',$tempAr);
    }

    function redirect($url)
    {
        return redirect($url);
    }

    public function transData($assigns = [])
    {
        return $this->makeAssign($assigns);
    }


}