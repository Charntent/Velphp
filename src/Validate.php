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

class Validate
{

    /**
     * 两者相等
     * @param string $str
     * @return boolean
     */
    public static function equal($value,$value2)
    {
        return $value == $value2 ? true : false;
    }

    /**
     * 必须
     * @param string $str
     * @return boolean
     */
    public static function required($value)
    {
        $value = trim($value);
        return empty($value) ? false : true;
    }

    /**
     * 必须是金钱格式
     * @param string $str
     * @return boolean
     */
    public static function money($value)
    {
        return (preg_match('/^\d+\.\d+/$',$value) || preg_match('/^((\d{1,3})|(\d{1,3},)?((\d{3},)*\d{3}))\.\d+$/',$value))? true : false;
    }

    /**
     * 必须是数字格式
     * @param string $str
     * @return boolean
     */
    public static function numeric($value)
    {
        return is_numeric($value);
    }




    /**
     * IP地址
     * @param string $str
     * @return boolean
     */
    public static function ip($ip)
    {
        if(preg_match('/((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)(\.((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)){3}/',$ip) === false){
          return false;
        }
        return true;
    }

    /**
     * 手机号码
     *
     * @param string $str
     * @return boolean
     */
    public static function mobile($str)
    {
        //return preg_match("/^[0]?(18[0|1|2|3|4|5|6|7|8|9]|13[0-9]{1}|15[0-9]{1}+)(\d{8})$/", $str);
		return preg_match("/^1[347589]{1}\d{9}$/", $str) ? true : false;
		
    }

    
    /**
     * 电话号码
     *
     * @param string $phone
     * @return boolean
     */
    public static function phone($phone)
    {
        $regex = "/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/";
        return preg_match($regex,$phone) ? true : false;
    }
    
    /**
     * 邮政编码
     *
     * @param string $str
     * @return boolean
     */
    public static function zip($str)
    {
        return preg_match("/^[1-9]\d{5}$/", $str) ? true : false;
    }

    /**
     * 邮件地址
     * @param string $str
     * @return boolean
     */
    public static function email($str)
    {
        return preg_match('/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*@([a-z0-9]+[\-]?[a-z0-9]+\.)+[a-z]{2,6}$/i', $str) ? true : false;
    }

    /**
     * QQ号码
     * @param <type> $str
     * @return <type>
     */
    public static function qq($str)
    {
        return preg_match("/^[1-9]{1}[0-9]{4,13}$/i", $str) ? true : false;
    }
    
    /**
     * 验证URL地址
     *
     * @param string $str
     * @return boolean
     */
    public static function url($str)
    {
        return preg_match("|^http://[_=&/?\.a-z0-9-]+$|i", $str) ? true : false;
    }

    /**
     * 全英文字母
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function alpha($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([a-z]{".$len."})$/i", $str) ? true : false;
        } else {
            return preg_match("/^([a-z])+$/i", $str) ? true : false;
        }
    }

    /**
     * 全数字
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function number($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([0-9]{".$len."})$/", $str) ? true : false;
        } else {
            return preg_match("/^([0-9])+$/", $str) ? true : false;
        }
    }

    /**
     * 数字或字母
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function num_alpha($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([a-z0-9]{".$len."})$/i", $str) ? true : false;
        } else {
            return preg_match("/^([a-z0-9])+$/i", $str) ? true : false;
        }
    }

    public static function len($str, $len, $max_len)
    {
        return (strlen($str) > $max_len || strlen($str) < $len) ? false : true;
    }

    /**
     * 数字和字母的组合
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function blend($str, $len = 0 ,$max_len = 0)
    {
        if(is_int($max_len) && ($max_len > 0)) {
            if(!self::len($str, $len, $max_len)) {
                return false;
            }
        } elseif (is_int($len) && ($len > 0) && !$max_len) {
            if(strlen($str) > $len) {
                return false;
            }
        }
        return preg_match("/^(((\d+[a-z]+)|([a-z]+\d+))[0-9a-z_]*)$/i", $str) ? true : false;
    }

    /**
     * 数字和字母或上划线,下划线
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function dash($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([_a-z0-9-]{".$len."})$/i", $str) ? true : false;
        } else {
            return preg_match("/^([_a-z0-9-])+$/i", $str) ? true : false;
        }
    }

    /**
     * 浮点数
     *
     * @param string $str
     * @return boolean
     */
     
    public static function float($str)
    {
        return preg_match("/^[0-9]+\.[0-9]+$/", $str) ? true : false;
    }

    /**
     * 中文
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function chinese($str)
    {
        return preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $str) ? true : false;
    }

    /**
     *  身份证
     *
     * @param string $id
     * @return boolean
     */
    public static function id_card( $id )
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx,$id))
        {
            return false;
        }
        if(15==strlen($id)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth))
            {
                return false;
            }else{
                return true;
            }
        }
        else           //检查18位
        {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth))  //检查生日日期是否正确
            {
                return false;
            }
            else
            {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ( $i = 0; $i < 17; $i++ )
                {
                    $b = (int) $id{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n  = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id,17, 1))
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
        }

    }

    /**
     * 域名
     *
     * @param string $domain
     * @return boolean
     */
    public static function domain($domain)
    {
        return preg_match('#^([0-9a-z]+(\-?[0-9a-z]+)*\.)+[a-z]{2,6}$#i', $domain) ? true : false;
    }

    /**
     * 用户名
     *
     * @param string $user_name
     * @return bool
     */
    public static function user_name($user_name)
    {
        return (preg_match('/^[a-z0-9\x{4e00}-\x{9fa5}]+(_*[a-z0-9\x{4e00}-\x{9fa5}]+)*$/iu', $user_name)
        && strlen($user_name) >= 0 && mb_strlen($user_name, 'UTF-8') <= 30) ? true : false;
    }
    
    /**
     * 自定义正则验证
     *
     * @param string $str
     * @param string $type
     * type为正则表达示格式，如 /[a-z]+[\d]{3,5}/i
     * @return boolean
     */
    public static function custom($str, $type)
    {
         return preg_match($type, $str) ? true : false;
    }

    /**
     * 时间格式
     *
     * @param string $time
     * @return bool
     */
    public static function time($time){

        $res = strtotime($time);
        if($res) {
            return true;
        }

        if(class_exists('\DateTime')) {
            $d = new \DateTime($time);
            //$res = ($time == $d->format('Y-m-d H:i')?true:false);
            $res = ($d->format('U')?true:false);
            if($res == true) {
                //$d->format('Y-m-d-H-i-m')
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

    /**
     * 可有可没有
     *
     * @param string $auto
     * @return bool
     */
    public static function auto($auto = '')
    {
        return  true;
    }

}

?>