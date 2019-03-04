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
use vel\Image;

class Upload
{
    
    public $dir;
    
    public function __construct()
    {
        $this->dir = ENTER_PATH.DS.'uploads'.DS.TMLSTYLE.DS.date('Ymd');
        if(!is_dir($this->dir)) mkdir($this->dir,0777,true);
    }

    public function isImage($extension)
    {
        $extension = strtolower($extension);
        if(!in_array($extension,array('png','jpg','jpeg','bmp','gif'))) {
            return false;
        }
        return true;
    }
    
    public function upload($name,$isImage = false)
    {
        $upfile = $_FILES[$name];
		$error_ar = array(
		   1=>'超过了文件大小php.ini中即系统设定的大小'.ini_get('upload_max_filesize').'。',
           2=>'超过了文件大小MAX_FILE_SIZE 选项指定的值。',
           3=>'文件只有部分被上传。',
           4=>'没有文件被上传。',
           5=>'上传文件大小为0。',
		   6=>'权限不够，临时文件夹无法写入！!'
		);
		if(!isset($upfile['tmp_name']) || $upfile['tmp_name']==''){
			return '无法使用上传!原因是：'.$error_ar[$upfile['error']];
		}
        $upfilename = $upfile['name'];
        $fileInfo   = pathinfo($upfilename);
        $extension  = strtolower($fileInfo['extension']);
        if(in_array($extension,array('php','asp','aspx'))) {
			return false;
		}
		if($isImage) {
		    $res = $this->isImage($extension);
            if(!$res) return false;
        }
        $relname = randomkeys(15).date("H-i-s").'.'.$extension;
        $relfile = $this->dir.DS.$relname;
        move_uploaded_file($upfile['tmp_name'],$relfile);


		$returnimg = str_replace(DS,'/',str_replace(ENTER_PATH.DS,'',$relfile));

		//取得图片大小
		$filesize = filesize($returnimg);
		if($filesize>=2097152){
			//大于2M的就开始截取
			$imageH = new Image();
			$returnimg = $imageH->equalThumb($returnimg,$returnimg,'',1000,1000);
		}
		
		
		if(Config::get('system.water.iswater') == true && Config::get('system.water.waterpic') !=''){
			$wt = '../'.Config::get('system.water.waterpic');
			$img = new Image();
			$img->set_watermark($wt);
			$rr = $img->watermark('../'.$returnimg);
		}
		
        return $returnimg;
    }
	
	
	public function upload_ar($name)
    {
        $upfile = $_FILES[$name];
		$returns = array();
		foreach($upfile['name'] as $k=>$filev){
			if($upfile['name'][$k]!=''){
				$upfilename = $upfile['name'][$k];
				$fileInfo=pathinfo($upfilename);
				$extension= strtolower($fileInfo['extension']);
				if(in_array($extension,array('php','asp','aspx'))) {
					return false;
				}
				$relname = randomkeys(15).date("H-i-s").'.'.$extension;
				$relfile = $this->dir.DS.$relname;
				move_uploaded_file($upfile['tmp_name'][$k],$relfile);
				$returns[$k] = str_replace(DS,'/',str_replace(WL_ROOT.DS,'',$relfile));
				$filesize = filesize($returns[$k]);
				if($filesize >= 2097152){
					//大于2M的就开始截取
					$imageH = new Image();
					$returns[$k] = $imageH->equalThumb($returns[$k],$returns[$k],'',1000,1000);
				}
			}
		}
		return $returns;
        
    }
	
    
    public function autoupload()
    {
        $rs = array();
        foreach($_FILES as $k => $r){
            if(!empty($r['name'])){
                $rs[$k] = $this->upload($k);
            }
        }
        return $rs;
    }
    
}

?>