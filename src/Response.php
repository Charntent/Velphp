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

class Response {



    public $responseType = 'html';
    public $data = null;
    // 输出参数
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];



    static function getInstance(){
         return new self();
    }

    public function data($data){
        $this->data = $data;
        return $this;
    }

    public function header($header=''){
        header($header);
    }


    public function out()
    {
        if($this->data === null) {
           $this->data = ob_get_clean();
           //ob_get_contents();
        }

        $type = gettype($this->data);
        switch ($type) {

            case 'array':
                self::json();
                break;

            default:
                self::html();
                break;
        }
    }

    public function json( $json = [] ){
        $this->data = $json?$json:$this->data;
        echo json_encode($this->data,$this->options['json_encode_param']);
        exit();

    }



    function html(){
        echo $this->data;
        exit();
    }
}