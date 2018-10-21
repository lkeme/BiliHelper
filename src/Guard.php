<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */


namespace lkeme\BiliHelper;

class Guard
{
    public static $lock = 0;

    // 过滤 已经抽奖过
    private static $lottery_list_end = [];
    // 保存 没有抽奖过
    private static $lottery_list_start = [];

    public static function run()
    {
        if (getenv('USE_GUARD') == 'true') {
            if (self::$lock < time()) {
                self::getGuardList();
                self::$lock = time() + 5 * 60;
            }
            if ((time() + 5 * 60 + 10) > static::$lock) {
                self::startLottery();
            }
        }
        return true;
    }

    // 抽奖结束
    protected static function endLottery($guard_id): bool
    {
        if (count(static::$lottery_list_end) > 2000) {
            static::$lottery_list_end = [];
        }
        array_push(static::$lottery_list_end, $guard_id);
        return true;
    }

    // 上船抽奖
    protected static function startLottery(): bool
    {
        $flag = 3;
        while ($flag) {
            $guard = array_shift(static::$lottery_list_start);
            if (is_null($guard)) {
                break;
            }
            if (!$guard['Status']) {
                continue;
            }
            $guard_id = $guard['GuardId'];
            if (in_array($guard_id, static::$lottery_list_end)) {
                continue;
            }
            $guard_roomid = $guard['OriginRoomId'];
            Live::goToRoom($guard_roomid);
            $data = self::guardLottery($guard_roomid, $guard_id);

            if ($data['code'] == 0) {
                Log::notice("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['data']['message']}");
            } elseif ($data['code'] == 400 && $data['msg'] == '你已经领取过啦') {
                Log::info("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['msg']}");
            } else {
                Log::warning("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['msg']}");
            }
            static::endLottery($guard_id);
            $flag--;
        }
        return true;
    }

    // 抽奖
    protected static function guardLottery($guard_roomid, $guard_id): array
    {
        $user_info = User::parseCookies();
        $url = "https://api.live.bilibili.com/lottery/v2/lottery/join";
        $payload = [
            "roomid" => $guard_roomid,
            "id" => $guard_id,
            "type" => "guard",
            "csrf_token" => $user_info['token']
        ];
        $raw = Curl::post($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        return $de_raw;
    }

    // 获取上船列表
    protected static function getGuardList(): bool
    {
        $headers = [
            'User-Agent' => "bilibili-live-tools/" . mt_rand(1000000, 99999999),
        ];
        $raw = Curl::other("http://118.25.108.153:8080/guard", null, $headers);
        $de_raw = json_decode($raw, true);
        static::$lottery_list_start = array_merge(static::$lottery_list_start, $de_raw);
        $guard_num = count(static::$lottery_list_start);
        Log::info("当前队列中共有{$guard_num}个舰长待抽奖");
        return true;
    }
}