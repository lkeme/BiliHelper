<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;

class Daily
{
    public static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }
        self::dailyBag();

        self::$lock = time() + 3600;
    }

    protected static function dailyBag()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/live/receive_daily_bag', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日礼包领取失败!', ['msg' => $data['message']]);
        } else {
            Log::info('每日礼包领取成功');
        }
    }

}
