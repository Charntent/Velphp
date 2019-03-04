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

class Crypt{


    public static function encrypt($data)
    {
        srand((double)microtime() * 1000000);
        $rand = md5(rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($data); $i++){
            $ctr = $ctr == strlen($rand) ? 0 : $ctr;
            $tmp .= $rand[$ctr].($data[$i] ^ $rand[$ctr++]);
        }
        return rtrim(base64_encode(self::proc($tmp)),'=');
    }

    public static function decrypt($data)
    {
        $data = self::proc(base64_decode($data));
        $tmp = '';
        for($i = 0; $i < strlen($data); $i++){
            $tmp .= $data[$i] ^ $data[++$i];
        }
        return $tmp;
    }

    protected static function proc($data)
    {

        $authkey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCa4KHNwDX44gGmmIAtRu4gjVYtGWZzcm4t+1wjUD4dn7fMLPvuK7ai4UrfDeEJE1RPwudJw+lJ6crql8wSIg7/DbTlG3ihsCT6dT9H5B9OoeR7K9VWUesaW/iyVL6HXiYOANabW14pvJATDmdq91Tfgp6PSQyvdfiRdV4r07crpQIDAQAB';

        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($data); $i++){
            $ctr = $ctr == strlen($authkey) ? 0 : $ctr;
            $tmp .= $data[$i] ^ $authkey[$ctr++];
        }
        return $tmp;
    }

}

?>