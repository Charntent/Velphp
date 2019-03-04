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





class Filter extends Component
{
	private static $_allowtags = 'p|br|b|strong|hr|a|object|param|form|input|label|dl|dt|dd|div|font|blockquote|span',
	               $_allowattrs = 'id|class|align|valign|src|border|href|target|width|height|title|alt|name|action|method|value|type',
	               $_disallowattrvals = 'expression|javascript:|behaviour:|vbscript:|mocha:|livescript:';
	
	function __construct($allowtags = null, $allowattrs = null, $disallowattrvals = null)
	{
		if ($allowtags) self::$_allowtags = $allowtags;
		if ($allowattrs) self::$_allowattrs = $allowattrs;
		if ($disallowattrvals) self::$_disallowattrvals = $disallowattrvals;
	}
	
	static function input($cleanxss = true)
	{
        if (!get_magic_quotes_gpc())
        {
           $_POST = gpc_addslashes($_POST);
           $_GET = gpc_addslashes($_GET);
           $_COOKIE = gpc_addslashes($_COOKIE);
           $_REQUEST = gpc_addslashes($_REQUEST);
        }
	
        if (!defined('IS_ADMIN') && $cleanxss)
        {
        	$_POST = self::xss($_POST);
        	$_GET = self::xss($_GET);
        	$_COOKIE = self::xss($_COOKIE);
        	$_REQUEST = self::xss($_REQUEST);

        }
		
	}
	
	static function xss($string)
	{
		if (is_array($string))
		{
			$string = array_map(array('self', 'xss'), $string);
		}
		else 
		{
			if (strlen($string) > 20)
			{  
                if (get_magic_quotes_gpc()){
                    $string = gpc_addslashes( self::_strip_tags(gpc_stripslashes($string)));
                }else{
                   $string = self::_strip_tags( $string );
                }
			}
		}
		return $string;
	}
	
	static function _strip_tags($string)
	{
		return preg_replace_callback("|(<)(/?)(\w+)([^>]*)(>)|", array('self', '_strip_attrs'), $string);
	}
	
	static function _strip_attrs($matches)
	{   
		if (preg_match("/^(".self::$_allowtags.")$/", $matches[3]))
		{   
			if ($matches[4])
			{
				preg_match_all("/\s(".self::$_allowattrs.")\s*=\s*(['\"]?)(.*?)\\2/i", $matches[4], $m, PREG_SET_ORDER);
				
				$matches[4] = '';
				
				foreach ($m as $k=>$v)
				{
					if (!preg_match("/(".self::$_disallowattrvals.")/", $v[3]))
					{
						$matches[4] .= $v[0];
					}
				}
				
			}
		}
		else 
		{
			$matches[1] = '&lt;';
			$matches[5] = '&gt;';
		}
		unset($matches[0]);
		return implode('', $matches);
	}
}