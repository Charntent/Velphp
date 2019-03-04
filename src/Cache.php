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

class Cache {
	
	protected $_cache_path ='';
    protected $_cache_ext ='.json';
    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_cache_path = VEL_CACHEROOT;
        if(!is_dir($this->_cache_path)) {

            mkdir($this->_cache_path, 0777);
        }
	}

    public static $instance = null;

    public static function getInstance()
    {
        if(static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public  function GetDataById($id){
		if ( ! file_exists($this->_cache_path.$id.$this->_cache_ext)){
			return false;
		}
		
		$data = self::read_file($this->_cache_path.$id.$this->_cache_ext);
		$data = json_decode($data,true);
		
		if (time() >  $data['time'] + $data['ttl'])
		{
			unlink($this->_cache_path.$id.$this->_cache_ext);
			return false;
		}
		
		return $data['data'];
		
	}
	
	public  function SaveById($id,$data,$ttl=60)
    {
		$contents = array(
				'time'		=> time(),
				'ttl'		=> $ttl,			
				'data'		=> $data
			);
		
		if (self::write_file($this->_cache_path.$id.$this->_cache_ext, json_encode($contents)))
		{
			chmod($this->_cache_path.$id.$this->_cache_ext, 0777);
			return true;
		}

		return false;
		
	}
	
	public  function Delete($id)
    {
	    if(file_exists($this->_cache_path.$id.$this->_cache_ext)){
            return unlink($this->_cache_path.$id.$this->_cache_ext);
        }
		return true;
	}
	
	public   function Clean($file ='' )
	{   
		return self::delete_files(($file ==''?$this->_cache_path:$file));
	}
	
	public function autoSet($cache_name,$data ='',$time=''){
		if($data == ''){
			return $this->GetDataById($cache_name);
		}else{
			$this->Delete($cache_name);
			$this->SaveById($cache_name,$data,$time);
			return $data;
		}
	}


    static function delete_files($path, $del_dir = FALSE, $level = 0)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        if (!$current_dir = opendir($path)) {
            return false;
        }
        while (false !== ($filename = readdir($current_dir))) {
            if ($filename != "." and $filename != "..") {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename)) {
                    if (substr($filename, 0, 1) != '.') {
                        self::delete_files($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $level + 1);
                    }
                } else {
                    unlink($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
        closedir($current_dir);
        if ($del_dir == true &&  $level > 0) {
            return rmdir($path);
        }
        return true;
    }

    static function write_file($path, $data, $mode = 'wb')
    {
        if (!$fp = fopen($path, $mode)) {
            return false;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }


    static function read_file($file)
    {
        if (!file_exists($file)) {
            return false;
        }
        if (function_exists('file_get_contents')) {
            return file_get_contents($file);
        }
        if (!$fp = fopen($file, 'rb')) {
            return false;
        }
        flock($fp, LOCK_SH);
        $data = '';
        if (filesize($file) > 0) {
            $data =& fread($fp, filesize($file));
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return $data;
    }
}