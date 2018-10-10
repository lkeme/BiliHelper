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

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }
        if (getenv('USE_GUARD') == 'true') {
            $guards = self::getGuardList();
            self::guardAction($guards);
        }

        self::$lock = time() + 5 * 60;
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

    // 上船
    protected static function guardAction(array $guards): bool
    {
        foreach ($guards as $guard) {
            if (!$guard['Status']) {
                continue;
            }
            $guard_id = $guard['GuardId'];
            $guard_roomid = $guard['OriginRoomId'];
            $data = self::guardLottery($guard_roomid, $guard_id);
            if ($data['code'] == 0) {
                Log::notice("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['data']['message']}");
            } elseif ($data['code'] == 400 && $data['msg'] == '你已经领取过啦') {
                Log::info("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['msg']}");
            } else {
                Log::warning("房间[{$guard_roomid}]编号[{$guard_id}]上船:{$data['msg']}");
            }
        }
        return true;
    }

    // 获取上船列表
    protected static function getGuardList(): array
    {
        $headers = [
            'User-Agent' => "bilibili-live-tools/" . mt_rand(1000000, 99999999),
        ];
        $raw = Curl::other("http://118.25.108.153:8080/guard", null, $headers);
        $de_raw = json_decode($raw, true);
        return $de_raw;
    }
}