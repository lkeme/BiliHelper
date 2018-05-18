<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class Base
{
    protected static $instance = [];
    protected static $data = [];


    public static function getInstance()
    {
        $calledClass = static::PLUGIN_NAME;
        if (!isset(static::$instance[$calledClass])) {
            static::$instance[$calledClass] = new static;
        }
        return static::$instance[$calledClass];
    }

    public static function run()
    {
        static::init();
        static::work();
    }

    protected static function data($key, $value = null)
    {
        $calledClass = static::PLUGIN_NAME;
        if (!is_null($value) || !isset(static::$data[$calledClass][$key])) {
            static::$data[$calledClass][$key] = $value;
        }
        return static::$data[$calledClass][$key];
    }

    protected static function sign($payload)
    {
        # iOS 6680
        $appkey = '27eb53fc9058f8c3';
        $appsecret = 'c2ed53a74eeefe3cf99fbd01d8c9c375';
        # Android
        // $appkey = '1d8b6e7d45233436';
        // $appsecret = '560c52ccd288fed045859ed18bffd973';
        # 云视听 TV
        // $appkey = '4409e2ce8ffd12b8';
        // $appsecret = '59b43e04ad6965f34319062b478f83dd';

        $default = [
            'access_key' => getenv('ACCESS_TOKEN'),
            'actionKey' => 'appkey',
            'appkey' => $appkey,
            'build' => '6680',
            'device' => 'phone',
            'mobi_app' => 'iphone',
            'platform' => 'ios',
            'ts' => time(),
        ];

        $payload = array_merge($payload, $default);
        if (isset($payload['sign'])) {
            unset($payload['sign']);
        }
        ksort($payload);
        $data = http_build_query($payload);
        $payload['sign'] = md5($data . $appsecret);
        return $payload;
    }
}
