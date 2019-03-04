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

class Checkcode{

	private $width  = 70;
	private $height = 28;

    public function __construct(){
		header("Content-type: image/PNG");
    }
    
    public function randstr(){
        $chars='ABCDEFGHJKMNPQRSTUVWXYZabcdefghjklmnpqrstuvwxyz23456789';
        $i = 4;
        $str = '';
        $len = strlen($chars);
        while($i--){
            $r = mt_rand(0,$len-1);
            $str .= $chars[$r];
        }
        $_SESSION['checkcode'] = $str;
        return $str;
    }
    
	public function show() {
		$im = imagecreate($this->width, $this->height);

		$gray = imagecolorallocate($im, 238,238,238); 
		$randcolor = imagecolorallocate($im, rand(0,150),rand(0,150),rand(0,150)); 
		
		imagefill($im,0,0,$gray);
		$randstr = $this->randstr();

		imagettftext($im, 14, 0, 7, 22, $randcolor, dirname(__FILE__).'/elephant.ttf', $randstr);
		
		for($i=0; $i<200; $i++) { 
     		$randcolor = imagecolorallocate($im,rand(0,255),rand(0,255),rand(0,255));
     		imagesetpixel($im, rand()%100 , rand()%30 , $randcolor); 
		}
		
		$a = imagepng($im); 
		imagedestroy($im);
		return $a;
	}
	
}

?>