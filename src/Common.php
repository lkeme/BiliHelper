<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2019
 */

namespace lkeme\BiliHelper;

class Common
{

//    /**
//     * 解析json串
//     * @param type $json_str
//     * @return type
//     */
//    public static function analyJson($json_str)
//    {
//        $json_str = str_replace('＼＼', '', $json_str);
//        $out_arr = [];
//        preg_match('/{.*}/', $json_str, $out_arr);
//        if (!empty($out_arr)) {
//            return json_decode($json_str, true, JSON_UNESCAPED_UNICODE);
//        } else {
//            return false;
//        }
//
//    }

    /**
     * 判断字符串是否为 Json 格式
     * @param string $data Json 字符串
     * @param bool $assoc 是否返回对象or关联数组，默认返回关联数组
     * @return array|bool|object 成功返回转换后的对象或数组，失败返回 false
     */
    public static function analyJson($data = '', $assoc = true)
    {
        $data = json_decode($data, $assoc);
        if (($data && is_object($data)) || (is_array($data) && !empty($data))) {
            return $data;
        }
        return false;
    }


}