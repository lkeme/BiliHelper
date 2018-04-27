<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.1
 *  License: The MIT License
 *  Updated: 20180425 18:47:50
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;

class Silver2Coin
{
    public static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }
        if (self::appSilver2coin() && self::pcSilver2coin()) {
            self::$lock = time() + 24 * 60 * 60;
            return;
        }
        self::$lock = time() + 3600;
    }


    // APP API
    protected static function appSilver2coin(): bool
    {
        $payload = [];
        $raw = Curl::get('https://api.live.bilibili.com/AppExchange/silver2coin', Sign::api($payload));
        $de_raw = json_decode($raw, true);

        if (!$de_raw['code'] && $de_raw['msg'] == '兑换成功') {
            Log::info('[APP]硬币兑换瓜子: ' . $de_raw['msg']);
        } elseif ($de_raw['code'] == 403) {
            Log::info('[APP]硬币兑换瓜子: ' . $de_raw['msg']);
        } else {
            Log::warning('[APP]硬币兑换瓜子: ' . $de_raw['msg']);
            return false;
        }
        return true;
    }

    // PC API
    protected static function pcSilver2coin(): bool
    {
        $payload = [];
        $raw = Curl::get('https://api.live.bilibili.com/exchange/silver2coin', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] == -403) {
            return false;
        }
        Log::info('[PC]硬币兑换瓜子: ' . $de_raw['msg']);
        // TODO
        return true;
    }
}