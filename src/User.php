<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.1
 *  License: The MIT License
 *  Updated: 2018-4-26 19:25:08
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;

class User
{
    // RUN
    public static function run()
    {
    }

    // 实名检测
    public static function realnameCheck(): bool
    {
        $payload = [];
        $raw = Curl::get('https://account.bilibili.com/identify/index', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        //检查有没有名字，没有则没实名
        if (!$de_raw['data']['memberPerson']['realname']) {
            return false;
        }
        return true;
    }
}