<?php
/**
 * 接口公用方法
 */
namespace Xt\Rpc\Services\HY_cos;

class Common
{
    //创建签名
    static public function createSign($data = array(), $signKey = '', $as = '=', $di = '&')
    {
        ksort($data);
        $string = '';
        foreach ($data as $key => $value) {
            $string .= "$key{$as}$value{$di}";
        }
        return md5(rtrim($string, $di) . $signKey);
    }


    static public function create_sign($data = array(), $signKey = '')
    {
        return self::createSign($data, $signKey);
    }
}