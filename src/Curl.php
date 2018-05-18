<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class Curl
{
    public static $header = array(
        'Accept' => '*/*',
        'Accept-Encoding' => 'gzip',
        'Accept-Language' => 'zh-cn',
        'Connection' => 'keep-alive',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'User-Agent' => 'User-Agent: bili-universal/6670 CFNetwork/897.15 Darwin/17.5.0',
    );

    public static function post($url, $payload = null)
    {
        $url = self::http2https($url);
        $header = array_map(function ($k, $v) {
            return $k . ': ' . $v;
        }, array_keys(self::$header), self::$header);
        $curl = curl_init();
        if (!is_null($payload)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : $payload);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if (($cookie = getenv('COOKIE')) != "") {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        if (getenv('USE_PROXY') == 'true') {
            curl_setopt($curl, CURLOPT_PROXY, getenv('PROXY_IP'));
            curl_setopt($curl, CURLOPT_PROXYPORT, getenv('PROXY_PORT'));
        }
        $raw = curl_exec($curl);
        Log::debug($raw);
        curl_close($curl);
        return $raw;
    }

    public static function get($url, $payload = null)
    {
        if (!is_null($payload)) {
            $url .= '?' . http_build_query($payload);
        }
        return self::post($url, null);
    }

    protected static function http2https($url)
    {
        switch (getenv('USE_HTTPS')) {
            case 'false':
                if (strpos($url, 'ttps://')) {
                    $url = str_replace('https://', 'http://', $url);
                }
                break;
            case 'true':
                if (strpos($url, 'ttp://')) {
                    $url = str_replace('http://', 'https://', $url);
                }
                break;
            default:
                Log::warning('当前协议设置不正确,请检查配置文件!');
                exit();
                break;
        }

        return $url;
    }
}
