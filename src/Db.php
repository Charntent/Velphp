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

class Db extends Component
{
    public $link, $sql, $queryID, $tablename;
    public $transTimes = 0;
    public static $sqls = array();
    public static $instance = null;

    public static function getInstance()
    {
        if(static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    //读写分离
    public static $connects = [
        'write' => null,
        'read'  => null,
    ];

    public function __construct()
    {
        $db_config = Config::get('db_config');
        $this->options = $db_config;
    }

    private function randOne($ar)
    {
        return $ar[array_rand($ar)];
    }

    public function getConnect($c_type = 'write')
    {
        if( isset(static::$connects[$c_type]) &&  null !== static::$connects[$c_type] ) {
            return $this->randOne(static::$connects[$c_type]);
        }
        $options = $this->randOne($this->options[$c_type]);
        if(!$options) {
            throw new PDOException('数据库配置不正确');
        }
        $dsn = $options['driver'] . ":host=" . $options['dbhost'] . ";dbname=" . $options['dbname'];
        $link = new \PDO($dsn, $options['dbuser'],$options['dbpassword'], array('PDO_ATTR_PERSISTENT' => true, 'MYSQL_ATTR_USE_BUFFERED_QUERY' => true));
        $link->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        static::$connects[$c_type][] = $link;
        $this->database_prex = $options['database_prex'];
        return $link;
    }

    public function getConnectType()
    {
        return 0 === stripos(trim($this->sql), 'select');
    }

    public function init()
    {
        $c_type = 'write';//写
        if($this->getConnectType() == true) {
            //读
            $c_type = 'read';
        }
        $this->link = $this->getConnect($c_type);

        if (!is_object($this->link)) {
            $this->halt('Can not connect to ' . $this->driver . ' server');
            phpinfo();
            exit;
        }
        $this->link->exec('set names utf8');
        $this->link->exec("set sql_mode=''");
        return $this->link;
    }



    public function setquery($sql)
    {
        $this->sql = $sql;
    }

    public function query($sql)
    {
        $this->setquery($sql);
        try {
            $this->init();
            if (!is_object($this->link)) {
                $this->init();
            }
            $start = microtime(TRUE);
            $this->queryID = $this->link->query($this->sql) OR $this->halt();
            self::$sqls[] = array('time' => number_format(microtime(TRUE) - $start, 6), 'sql' => $this->sql);
            //file_put_contents(dirname(__FILE__).'/'.date('YmdH').'.log', var_export(self::$sqls, true), FILE_APPEND);
            return $this->queryID;
        } catch (PDOException $e) {
            echo 'Failed:文件'.$e->getFile().',第'.$e->getLine().'行;' . $e->getMessage() . '<br><br>';
        }
    }

    public function fetch($queryID = '')
    {
        try {
            $rs = $this->link->query($this->sql);
            return $rs->fetch();
        } catch (PDOException $e) {
            echo $this->sql . '<br><br>';
            echo 'Failed: ' . $e->getMessage() . '<br><br>';
        }
    }

    public function select($sql, $key = null)
    {
        $this->query($sql);
        $rs = $this->link->query($this->sql);
        $res = $rs->fetchAll();

        if($res && strstr($this->sql,'COLUMNS')) {
           $temp = [];
           foreach ($res as $k=>$v) {
               $temp[$v['Field']] = $v;
           }
            $res = $temp;
            unset($temp);
        }

        if($key !== null && isset($res[0][$key])) {
            $temp = [];
            foreach ($res as $k=>$v) {
                $temp[$v[$key]] = $v;
            }
            $res = $temp;
            unset($temp);
        }

        return $res;
    }

    public function prepare()
    {
        $rs = $this->link->prepare($this->sql);
        $rs->execute();
        return $rs;
    }

    public function find($sql, $limit = true)
    {
        if ($limit === true) {
            if (stripos($sql, 'limit') === false) $sql = rtrim($sql, ';') . ' limit 1;';
        }
        $this->query($sql);
        $rs = $this->fetch();
        return $rs;
    }

    public function getfield($sql, $limit = true)
    {
        $result = $this->find($sql, $limit);
        return is_array($result) ? array_pop($result) : $result;
    }

    public function data($data)
    {
        set_gpc($data);
        return $this;
    }

    public function save($table, $id = "", $ar = array())
    {
        $config = \vel\Config::get('db_config');
        $prex = $config['read']['database_prex'];
        if(strpos($table,$prex) !== 0) {
            $table = $this->tb($table)->tablename;
        }
        if (empty($ar)) {
            $rs = $this->select("SHOW COLUMNS FROM `$table`");
            if ($id == "") {
                foreach ($rs as $r) {
                    if ($r['Key'] == 'PRI') {
                        $primary = $r['Field'];
                        break;
                    }
                }
            } else {
                $primary = $id;
            }

            if (isset($_REQUEST[$primary]) && $this->find("select `$primary` from `$table` where `$primary`='{$_REQUEST[$primary]}' ")) {
                $value = gpc($primary);
                $where = "`$primary`='$value'";
                $data = array();
                foreach ($rs as $row) {
                    $fieldname = $row['Field'];
                    if (isset($_REQUEST[$fieldname])) {
                        $data[] = "`$fieldname` = '" . $_REQUEST[$fieldname] . "'";
                    }
                }
                if (count($data) > 0) {
                    $sql = "update `$table` set " . join(",", $data) . " where " . $where;
                    return $this->query($sql);
                }
            } else {
                $fields = $values = array();
                foreach ($rs as $row) {
                    $fieldname = $row['Field'];
                    if (isset($_REQUEST[$fieldname])) {
                        $fields[] = "`$fieldname`";
                        $values[] = "'" . $_REQUEST[$fieldname] . "'";
                    }
                }
                if (count($fields) > 0) {
                    $sql = "insert into `$table` (" . join(',', $fields) . ") values (" . join(',', $values) . ") ";
                    $this->query($sql);
                    return $this->insert_id();
                }
            }
        }
        if (!empty($ar)) {
            $fields = $values = array();
            $str = array();
            foreach ($ar as $k => $row) {
                $fields[] = "`$k`";
                $values[] = "'" . $row . "'";
                $str[] = "`{$k}`='{$row}'";
            }
            if ($id) {
                $sql = "update `$table` set " . implode(",", $str) . " where `{$id}`=" . $ar[$id];
                return $this->query($sql);
            } else {
                if (count($fields) > 0) {
                    $sql = "insert into `$table` (" . join(',', $fields) . ") values (" . join(',', $values) . ") ";
                    $this->query($sql);
                    return $this->insert_id();
                }
            }
        }
    }

    public function insert_id()
    {
        return $this->link->lastInsertId();
    }

    public function affected_rows()
    {
        //return mysql_affected_rows($this->link);
        return $this->link->rowCount();
    }

    public function version()
    {
        if (!is_object($this->link)) {
            $this->init();
        }
        return $this->link->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }


    public function errno($msg = '', $query = '', $die = false)
    {

    }

    public function error($msg = '', $query = '', $die = false)
    {
        try {
            $text = "Addr:" . getenv("REMOTE_ADDR");
            $text .= "\r\nData:" . date("Y-m-d H:i:s");
            $text .= "\r\nCode:";
            $text .= "\r\nPage:" . $_SERVER['PHP_SELF'];
            $text .= "\r\nWarning:" . $msg;
            $text .= "\r\nQuery:" . $query . "\r\n\r\n";
            die($text);
        } catch (PDOException $e) {
            echo 'Failed: ' . $e->getMessage() . '<br><br>';
        }
    }

    public function halt($msg = '')
    {
        try {
            $errmsg = '';
            if ($this->sql) $errmsg .= "<b>$this->driver Query : </b> " . $this->sql . " <br>";
            if ($msg) $errmsg .= "<b>Error Message : </b> $msg <br />";
            if (!empty($errmsg)) {
                echo '<div style="padding:10px;border:1px solid #F90;color:#666;font-family:Arial;">' . $errmsg . '</div>';
            }
            exit;
        } catch (PDOException $e) {
            echo 'Failed: ' . $e->getMessage() . '<br><br>';
        }
    }


    public function settable($table)
    {
        $this->tablename = $table;
        $this->sql = "";
        return $this;
    }

    public function t($table)
    {
        $this->sql = '';
        return $this->settable($table);
    }

    public function tb($table)
    {
        $this->sql = '';
        return $this->settable(tb($table));
    }

    public function where($w)
    {

        if (is_array($w)) {

            $htmlbuild = '';
            foreach ($w as $k => $v) {
                if (is_array($v)) {

                    foreach ($v as $k1 => $v1) {
                        //应该是三个数字！
                        if ($k1 == 0)
                            $htmlbuild .= ' AND `' . $v1 . '`';
                        elseif ($k1 == 2)
                            $htmlbuild .= " '" . $v1 . "' ";
                        else
                            $htmlbuild .= $v1;
                    }
                } else {

                    //应该是三个数字！
                    if ($k == 0)
                        $htmlbuild .= ' AND  ' . $v . '';
                    elseif ($k == 2)
                        $htmlbuild .= "  '" . $v . "'  ";
                    else
                        $htmlbuild .= " " . $v . "  ";
                }
            }
            $this->sql = $this->sql . " WHERE 1 " . $htmlbuild;
        } else {
            $this->sql = $this->sql . " WHERE " . $w;
        }
        return $this;
    }

    public function orwhere($w)
    {
        if (is_array($w)) {
            $htmlbuild = '';
            foreach ($w as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k1 => $v1) {
                        //应该是三个数字！
                        if ($k1 == 0)
                            $htmlbuild .= ' OR (`' . $v1 . '`';
                        elseif ($k1 == 2)
                            $htmlbuild .= "'" . $v1 . "' )";
                        else
                            $htmlbuild .= $v1;
                    }
                } else {
                    //应该是三个数字！
                    if ($k == 0)
                        $htmlbuild .= ' OR `' . $v . '`';
                    elseif ($k == 2)
                        $htmlbuild .= "'" . $v . "'";
                    else
                        $htmlbuild .= $v;
                }
            }
            $this->sql = $this->sql . " WHERE 1 " . $htmlbuild;
        } else {
            $this->sql = $this->sql . " WHERE 1 OR " . $w;
        }
        return $this;
    }

    /*
        ON条件
    */
    public function on($w)
    {
        if (is_array($w)) {
            $htmlbuild = '';
            $jflag = 0;
            foreach ($w as $k => $v) {

                if (is_array($v)) {

                    foreach ($w as $k => $v) {

                        //应该是三个数字！
                        if ($k == 0) {
                            if ($jflag == 0) {
                                $htmlbuild .= ' ON ' . $v;
                                $jflag++;
                            } else {
                                $htmlbuild .= ' AND ' . $v;
                            }
                        } elseif ($k == 2)
                            $htmlbuild .= " '" . $v . "' ";
                        else
                            $htmlbuild .= " " . $v;
                    }
                } else {
                    //应该是三个数字！
                    if ($k == 0)
                        $htmlbuild .= ' ON  ' . $v;
                    elseif ($k == 2) {
                        if (strstr($v, '.'))
                            $htmlbuild .= " " . $v . " ";
                        else {
                            $htmlbuild .= " '" . $v . "' ";
                        }
                    } else
                        $htmlbuild .= $v;
                }
            }
            $this->sql = $this->sql . " " . $htmlbuild;
        } else {
            $this->sql = $this->sql . " ON " . $w;
        }
        return $this;
    }

    public function leftjoin($w)
    {
        $this->sql = $this->sql . "  LEFT JOIN ". $w;

        return $this;
    }

    public function rightjoin($w)
    {

        $this->sql = $this->sql . " " . implode(" RIGHT JOIN ", $w);
        return $this;
    }

    public function innerjoin($w)
    {
        $this->sql = $this->sql . " " . implode(" INNER JOIN ", $w);
        return $this;
    }

    public function orderby($by, $xu = "DESC")
    {
        $this->sql = $this->sql . " ORDER BY " . $by . " " . $xu;
        return $this;
    }

    public function limit($limit)
    {
        $this->sql = $this->sql . " LIMIT " . $limit;
        return $this;
    }

    public function all($field = '*')
    {
        return $this->SelectData($field);
    }

    public function get($i, $field = '*')
    {
        if ($i < 1) {
            return false;
        } elseif ($i == 1) {
            return $this->FindData($field);
        }
        return $this->SelectData($field, $i);
    }

    public function SelectData($field, $limit = 0)
    {
        if ($limit != 0) {
            $this->limit($limit);
        }

        if ($this->tablename != '') {
            $this->sql = "SELECT  " . $field . " FROM  `" . $this->tablename . "` " . $this->sql;
        } else {
            $this->sql = "SELECT " . $field . " FROM  " . $this->sql;
        }
        $tempsql = $this->sql;
        $this->sql = "";
        $this->tablename = "";
        return $this->select($tempsql);
    }

    public function FindData($field)
    {

        if ($this->tablename != '') {
            $this->sql = "SELECT  " . $field . " FROM  `" . $this->tablename . "` " . $this->sql;
        } else {
            $this->sql = "SELECT " . $field . " FROM  " . $this->sql;
        }
        $tempsql = $this->sql;
        $this->sql = "";
        $this->tablename = "";
        return $this->find($tempsql);
    }

    public function FieldData($field)
    {

        $this->sql = "SELECT " . $field . " FROM " . $this->tablename . $this->sql;
        $tempsql = $this->sql;
        $this->sql = "";
        $this->tablename = "";
        return $this->getfield($tempsql);
    }

    public function get_field($field)
    {
        return $this->FieldData($field);
    }

    public function UpdateTable($values, $where, $tabler = 'admin', $orderby = array(), $limit = FALSE)
    {
        $table = ($this->tablename == "") ? $tabler : $this->tablename;
        foreach ($values as $key => $val) {
            $valstr[] = "`" . $key . "`" . " = '" . $val . "'";
        }

        $limit = (!$limit) ? '' : ' LIMIT ' . $limit;

        $orderby = (count($orderby) >= 1) ? ' ORDER BY ' . implode(", ", $orderby) : '';

        $sql = "UPDATE `" . $table . "` SET " . implode(', ', $valstr);

        $sql .= ($where != '' AND count($where) >= 1) ? " WHERE " . implode(" ", $where) : '';

        $sql .= $orderby . $limit;

        return $this->query($sql);
    }

    public function AddData($value, $tabler = 'admin', $sqlmap = false)
    {
        $table = ($this->tablename == "") ? $tabler : $this->tablename;
        foreach ($value as $key => $val) {
            $keys[] = "`" . $key . "`";
            $values[] = "'" . $val . "'";
        }
        $sql = "INSERT INTO `" . $table . "` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";

        if ($sqlmap == true) {
            $this->topSql = "INSERT INTO `" . $table . "` (" . implode(', ', $keys) . ") VALUES ";
            $this->sql = "(" . implode(', ', $values) . "),";
            return $this;
        }

        if ($this->query($sql))
            return $this->insert_id();
        else
            return false;
    }

    public function add($value, $tabler = 'admin', $sqlmap = false) {
        return $this->AddData($value, $tabler, $sqlmap);
    }

    public function update($values, $where, $tabler = 'admin', $orderby = array(), $limit = FALSE) {
        return $this->UpdateTable($values, $where, $tabler, $orderby, $limit);
    }

    public function DeleteData($value, $pt = 'id', $tabler = '')
    {
        if (empty($this->tablename) and $tabler == '') {
            return false;
        } else {
            if ($tabler != '') {
                $this->tablename = $tabler;
            }
            $sql = "DELETE FROM " . $this->tablename . "  WHERE  `" . $pt . "`='$value'" ;
            return $this->query($sql);
        }
    }

    public function delete($value, $pt = 'id', $tabler = '')
    {
       return $this->DeleteData($value, $pt, $tabler);
    }


    public function GetCount($sql)
    {
        return count($this->select($sql));
    }

    public function getCol($sql, $temp)
    {
        $res = $this->query($sql);

        if ($res !== false) {
            $arr = array();
            while ($row = $this->fetch()) {
                if (isset($row[$temp]))
                    $arr[] = $row[$temp];
                else {
                    break;
                }
            }

            return $arr;
        } else {
            return false;
        }
    }

    public function setField($field, $v)
    {
        if (!$this->tablename) return false;
        $sql = "UPDATE `" . $this->tablename . "` SET `" . $field . "` = '" . $v . "' " . $this->sql;
        return $this->query($sql);
    }

    public function startMap()
    {
        $this->sqlMaps = '';
    }

    public function ready()
    {
        $this->sqlMaps .= $this->sql;
        return $this;
    }

    public function getMap()
    {
        $this->sqlMaps = trim($this->sqlMaps, ',');
        return $this->topSql . $this->sqlMaps;
    }

    public function getTopSql()
    {
        return $this->topSql;
    }


    // 开始事务
    public function beginTransaction()
    {
        if(static::$connects['write'] == null) {
            $this->link = $this->getConnect('write');
        }
        $this->link = static::$connects['write'];
        $this->link = static::$connects['write'];
        if ( !$this->link->inTransaction() ) {
            $this->link->beginTransaction();
            $this->transTimes = 1;
        } else {
            $this->halt('不能同时开启两个事务');
        }
    }
    // 提交事务
    public function commit()
    {
        $this->link->commit();
        //$this->transTimes = 0;
    }

    // 回滚事务
    public function rollBack()
    {
        $this->link->rollback();
    }
    // 自动事务
    public function transaction($closure)
    {
        $this->beginTransaction();
        try {
            $results = $closure();
            // 提交事务
            $this->commit();
            return $results;
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollBack();
            throw $e;
        }
    }

    //分页
    public function makePage($field = '*',$nums = 10)
    {
        if ($this->tablename != '') {
            $this->sql = "SELECT  " . $field . " FROM  `" . $this->tablename . "` " . $this->sql;
        } else {
            $this->sql = "SELECT " . $field . " FROM  " . $this->sql;
        }
        $tempsql = $this->sql;
        $this->sql = "";
        $this->tablename = "";
        return new Page($tempsql,$nums);
    }


    //统计数据的数量
    public  function count($countWhere = "*")
    {
        if ($this->tablename != '') {
            $this->sql = "SELECT  COUNT(`" . $countWhere . "`) as total FROM  `" . $this->tablename . "` " . $this->sql;
        } else {
            $this->sql = "SELECT COUNT(`" . $countWhere . "``) as total FROM  " . $this->sql;
        }
        return $this->getfield($this->sql);
    }

    //相加数据
    public  function sum($sumWhere="*"){
        if ($this->tablename != '') {
            $this->sql = "SELECT  SUM(`" . $sumWhere . "`) as total FROM  `" . $this->tablename . "` " . $this->sql;
        } else {
            $this->sql = "SELECT SUM(`" . $sumWhere . "``) as total FROM  " . $this->sql;
        }
        return $this->getfield($this->sql);
    }



}

?>