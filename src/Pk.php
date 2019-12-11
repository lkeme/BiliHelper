<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2019
 */


namespace lkeme\BiliHelper;

use function Swlib\Http\str;

class Pk
{
    public static $lock = 0;

    // 过滤 已经抽奖过
    private static $lottery_list_end = [];
    // 保存 没有抽奖过
    private static $lottery_list_start = [];

    public static function run()
    {
        if (getenv('USE_PK') == 'true') {
            if (self::$lock < time()) {
                self::getPkList();
                self::$lock = time() + 30;
            }
            self::startLottery();
        }
        return true;
    }

    // 抽奖结束
    protected static function endLottery($pk_id): bool
    {
        if (count(static::$lottery_list_end) > 2000) {
            static::$lottery_list_end = [];
        }
        array_push(static::$lottery_list_end, $pk_id);
        return true;
    }

    // 大乱斗抽奖
    protected static function startLottery(): bool
    {
        $flag = 100;
        while ($flag) {
            $pk = array_shift(static::$lottery_list_start);
            if (is_null($pk)) {
                break;
            }
            $pk_id = $pk['Id'];
            if (in_array($pk_id, static::$lottery_list_end) || $pk_id == 0) {
                continue;
            }
            $pk_roomid = $pk['RoomId'];
            Live::goToRoom($pk_roomid);
            $data = self::pkLottery($pk_roomid, $pk_id);

            if ($data['code'] == 0) {
                Log::notice("房间[{$pk_roomid}]编号[{$pk_id}]大乱斗:" . (!empty($data['data']['award_text'])  ? $data['data']['award_text'] : "{$data['data']['award_name']}x{$data['data']['award_num']}"));
            } elseif ($data['code'] == -2 && $data['message'] == '您已参加过抽奖') {
                Log::info("房间[{$pk_roomid}]编号[{$pk_id}]大乱斗:{$data['message']}");
            } else {
                Log::warning("房间[{$pk_roomid}]编号[{$pk_id}]大乱斗:{$data['message']}");
            }
            static::endLottery($pk_id);
            $flag--;
        }
        return true;
    }

    // 抽奖
    protected static function pkLottery($pk_roomid, $pk_id): array
    {
        $user_info = User::parseCookies();
        $url = "https://api.live.bilibili.com/xlive/lottery-interface/v1/pk/join";
        $payload = [
            "roomid" => $pk_roomid,
            "id" => $pk_id,
            "csrf_token" => $user_info['token'],
            'csrf' => $user_info['token'],
            'visit_id' => null,
        ];
        $raw = Curl::post($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        return $de_raw;
    }

    // 获取大乱斗列表
    protected static function getPkList(): bool
    {
        $headers = [
            'User-Agent' => "bilibili-live-tools/" . mt_rand(1000000, 99999999),
        ];
        $raw = Curl::other("http://118.25.108.153:8080/pk", null, $headers, null, '118.25.108.153:8080');
        $de_raw = Common::analyJson($raw, true);
        if (!$de_raw) {
            Log::info("大乱斗服务器返回为空或暂时宕机");
            return false;
        }
        static::$lottery_list_start = array_merge(static::$lottery_list_start, $de_raw);
        $pk_num = count(static::$lottery_list_start);
        Log::info("当前队列中共有{$pk_num}个大乱斗待抽奖");
        return true;
    }
}