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

    public static function post($url, $payload = null, $timeout = 30)
    {
        $url = self::http2https($url);
        Log::debug($url);
        $header = array_map(function ($k, $v) {
            return $k . ': ' . $v;
        }, array_keys(self::$header), self::$header);

        // 重试次数
        $ret_count = 5;
        while ($ret_count) {
            try {
                $curl = curl_init();
                if (!is_null($payload)) {
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : $payload);
                }
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
                curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                // 超时 重要
                curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
                if (($cookie = getenv('COOKIE')) != "") {
                    curl_setopt($curl, CURLOPT_COOKIE, $cookie);
                }
                if (getenv('USE_PROXY') == 'true') {
                    curl_setopt($curl, CURLOPT_PROXY, getenv('PROXY_IP'));
                    curl_setopt($curl, CURLOPT_PROXYPORT, getenv('PROXY_PORT'));
                }
                $raw = curl_exec($curl);

                if ($err_no = curl_errno($curl)) {
                    throw new \Exception(curl_error($curl));
                }

                if ($raw === false || strpos($raw, 'timeout') !== false) {
                    Log::warning('重试，获取的资源无效!');
                    $ret_count--;
                    continue;
                }

                Log::debug($raw);
                curl_close($curl);
                return $raw;

            } catch (\Exception $e) {
                Log::warning("重试,Curl请求出错,{$e->getMessage()}!");
                $ret_count--;
                continue;
            }
        }
        exit('重试次数过多，请检查代码，退出!');
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
                die();
                break;
        }

        return $url;
    }
}
