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
use vel\Cache;
use vel\Validate;


class Model extends  Component implements \ArrayAccess
{
    public $db;
    public static $instance = null;
    public $data;
    public $table;
    public $pri = [];
    public $cache_key = 'VEL_TABLE_';
    public $cache_table = '';
    public $sences = null,$postField = null;
    public $errorMsg = [];
    public $Wls_cache = null;

    function __construct($pri = 0)
    {
        $this->Wls_cache = Cache::getInstance();
        $database_prex = Config::get('db_config.database_prex');

        $this->db = Db::getInstance();
        $getClassName = get_class($this);
        $classNameAr = explode(DS,$getClassName);

        $className = humpToLine(end($classNameAr));
        if(substr($className,0,1) == '_') {
            $className = substr($className,1);
        }
        $this->table = strtolower($database_prex.$className);
        $tables = $this->getTables();

        if(in_array($this->table,$tables)) {
            $this->cache_table = $this->getCacheKey();
            $this->setPri();
            $priKey = $this->getPri();
            if($pri > 0) {
                $list = $this->db->tb($className)->where("`".$priKey."`='$pri'")->get(1,'*');
                if($list) {
                    $this->data = $list;
                }
            }

        }
    }

    //设置table
    public function table($tb)
    {
        $database_prex = Config::get('db_config.read.database_prex');
        $this->table = strtolower($database_prex.$tb);
        return $this;
    }

    //获取主键
    public function getPri()
    {
        if(!isset($this->pri[$this->table])) {
            $this->resetTableCache();
            $this->setPri();
            if(!isset($this->pri[$this->table])){
                return false;
            }
        }
        return $this->pri[$this->table];
    }

    public function setPri()
    {
        $cacheData =  $this->Wls_cache -> GetDataById($this->cache_table);
        if(!$cacheData) {
            $cacheData = $this->cacheTable();
        }
        $this->pri[$this->table] = $cacheData['pri'];
    }

    public function resetTableCache()
    {
        $this->Wls_cache->Delete($this->cache_key.'tables');
        $this->getTables();
    }

    //获取所有的表
    public function getTables()
    {
        $dbname  = getDbInfo('read')['dbname'];
        $Wls_cache = Cache::getInstance();
        $tables = $Wls_cache->GetDataById($this->cache_key.'tables');
        if(!$tables) {
            $tlist = $this->db->select("select table_name from information_schema.tables where table_schema='$dbname' and table_type='base table'");
            foreach ($tlist as $k=>$v) {
                $tables[] = $v['table_name'];
            }
            $Wls_cache->autoSet($this->cache_key.'tables',$tables,8640000);
        }
        return $tables;
    }

    //缓存当前表
    public function cacheTable()
    {
        $fieldsAr = $this->db->select("SHOW COLUMNS FROM `$this->table`","Field");
        $fields = array_keys($fieldsAr);
        $pri = '';
        foreach ($fieldsAr as $k=>$v) {
            if($v['Key'] == 'PRI') {
                $pri = $k;
                break;
            }
        }
        $data = array(
            'pri' => $pri,
            'fields'=> $fields
        );
        $this->Wls_cache->autoSet($this->cache_table,$data,8640000);
        return $data;
    }

    //获取表的字段
    public function getFeilds()
    {
        $Wls_cache = Cache::getInstance();
        $fields = $Wls_cache->GetDataById($this->cache_table);
        if(isset($fields['fields'])) {
            return $fields['fields'];
        }
        return false;
    }

    //获取表保存的名称
    function getCacheKey()
    {
        return $this->cache_key.$this->table;
    }

    //设置数据
    public function  __set($name,$value)
    {
        $this->data[$name] = $value;
    }

    //获取数据
    public function __get($name)
    {
        return isset($this->data[$name])?$this->data[$name]:null;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([Db::getInstance(),$name],$arguments);
    }

    /*AarrayAccess接口实现开始*/
    public function offsetExists($key){
        return isset($this->data[$key]) ? true : false;
    }

    public function offsetGet($key){
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function offsetSet($key, $value){
        $this->data[$key] = $value;
    }

    public function offsetUnset($key){
        unset($this->data[$key]);
    }
    /*AarrayAccess接口实现开始结束*/

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return json_encode($this->data);
    }

    //设置所有的数据
    public function getData()
    {
        return $this->data;
    }

    //设置单个数据
    public function data( $data = array() )
    {
        foreach ($data as $k=>$v) {
            $this->data[$k] = $v;
        }
        return $this;
    }

    //重写数据处理
    public function prepData()
    {

    }

    //自动保存数据
    public function save()
    {
        $this->prepData();
        if(empty($this->data)) {
            $this->data = $this->postField;
        }
        $key = isset($this->pri[$this->table])?$this->pri[$this->table]:'';
        if($key && isset($this->data[$key])) {
            $where = ["`$key`='".$this->data[$key]."'"];
            unset($this->data[$key]);
            return $this->db->t($this->table)->update($this->data,$where);
        }else{
            //添加
            return $this->db->t($this->table)->add($this->data);
        }
    }

    //自动获取条件数据
    public  function get($where,$fields = "*", $iskey = false,$orderby = null)
    {
        $pri = $this->getPri();
        if($iskey === true) {
            $where = "`$pri`='$where'";
        }
        if($orderby == null) {
            $orderby = $pri;
        }

        return $this->db->t($this->table)->where($where)->orderby($orderby)->get(1,$fields);
    }

    //统计数据的数量
    public  function count($where,$countWhere = "*")
    {
        return $this->db->getfield("SELECT COUNT($countWhere) as total FROM `$this->table` WHERE $where");
    }

    //相加数据
    public  function sum($where,$sumWhere="*"){
        return $this->db->getfield("SELECT SUM($sumWhere) as total  FROM `$this->table` WHERE $where");
    }

    //添加数据
    public function create($data)
    {
        $fields = $this->getFeilds();
        $datas = array();
        foreach ($data as $k=>$v) {
            if(in_array($k,$fields)) {
                $datas[$k] = $v;
            }
        }
        return $this->db->tb($this->table)->add($datas);
    }

    //删除数据
    public function delete($priValue = null)
    {
        $this->syncData();
        $pri = $this->getPri();
        if(is_null($priValue)) {
            //根据主键删除
            if(isset($this->data[$pri]) && $this->data[$pri]) {
                $value = $this->data[$pri];
                return $this->db->t($this->table)->DeleteData($value,$pri);
            }
            return false;
        } elseif (is_array($priValue)) {

            $valstr = '';
            //数组组成的条件
            foreach ($priValue as $key => $val) {
                $valstr  .= ($valstr?" AND ":"")."`" . $key . "`" . " = '" . $val . "'";
            }
            if($valstr != '') {
                $sql = "DELETE FROM `" . $this->table . "`  WHERE  $valstr ";
                return $this->db->query($sql);
            }
            return false;
        }else{
            //根据主键来穿入一个值删除
            return $this->db->t($this->table)->DeleteData($priValue,$pri);
        }
    }

    //同步数据
    public function syncData()
    {
        $this->data($this->postField);
    }
    //单例模式
    public static function getInstance()
    {
        if(self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    //验证数据
    public function autoValidate($sence = null)
    {
        if(!isset($this->sences[$sence])) {
            return '不存在该场景';
        }
        $fields = $this->sences[$sence];
        return $this->validate($fields);
    }

    //新版本验证控制器
    public function validate($fields)
    {
        $results = true;
        $Validate = new Validate();
        foreach ($fields as $k=>$v) {
            $value = gpc($k);
            $this->postField[$k] = $value;
            if(is_string($v)) {
                $func = 'validate'.ucNose($v);
                //有限匹配模型里面的
                if(is_callable([$this,$func])) {
                    $results  = call_user_func([$this,$func],$value);
                    if($results !== true) {
                        break;
                    }
                }

                if(method_exists($Validate,$v)) {
                    $results  = call_user_func([$Validate,$v],$value);
                    if($results !== true && $results !== 1) {
                        if(isset($this->errorMsg[$k.'.'.$v])) {
                            $results = $this->errorMsg[$k.'.'.$v];
                        }else{
                            $results = $k.' IS '.$v;
                        }
                        break;
                    }
                }
            }
            //匹配系统自带的Validate
            if (is_array($v)) {

                foreach ($v as $vKey=>$vValue)
                {
                    if($vKey === 'equal') {
                        $valueEq = gpc($vValue);
                        $this->postField[$vValue] = $valueEq;
                        $results  = call_user_func_array([$Validate,'equal'],[$value,$valueEq]);
                    }else{
                        if(method_exists($Validate,$vValue)) {
                            $results  = call_user_func([$Validate,$vValue],$value);
                        }
                    }
                    if($results !== true) {
                        if(isset($this->errorMsg[$k.'.'.$vValue])) {
                            $results = $this->errorMsg[$k.'.'.$vValue];
                        }else{
                            $results = $k.' IS '.$vValue;
                        }
                        break;
                    }
                }
                if($results !== true) {
                    break;
                }
            }
        }
        return $results;
    }

    //旧版本
    public function validate0($fields)
    {
        $results = true;
        foreach ($fields as $k=>$v) {
            $value = gpc($v);
            $func = 'validate'.ucNose($v);
            if(is_callable([$this,$func])) {
                $this->postField[$v] = $value;
                $results  = call_user_func([$this,$func],$value);
                if($results !== true) {
                    break;
                }
            }else{
                $results = '方法'.$func.'不存在';
                break;
            }
        }
        return $results;
    }

    public function autoGetValue($sence = null)
    {
        if(!isset($this->sences[$sence])) {
            return '不存在该场景';
        }
        $fields = $this->sences[$sence];
        return $this->setGpcValue($fields);
    }

    private function setGpcValue($fields)
    {
        foreach ($fields as $k=>$v) {
            $value = gpc($v);
            $this->postField[$v] = $value;
        }
        return true;
    }

}