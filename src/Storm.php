<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class Storm
{
    private static $realname_check = true;

    // RUN
    public static function run(array $data)
    {
        if (getenv('USE_STORM') == 'false') {
            return;
        }
        if (!self::$realname_check) {
            Log::notice('该账号没有实名,跳过节奏风暴!');
            return;
        }
        switch ($data['num']) {
            case 1:
                $id = $data['id'];
                self::lotteryStart($id);
                break;
            case 20:
                $id = self::stormCheckId($data['room_id']);
                if (!$id) {
                    return;
                }
                self::lotteryStart($id);
                break;
            default:
                break;
        }
    }

    protected static function lotteryStart($id)
    {
        for ($i = 0; $i < 10; $i++) {
            if (!self::stormLottery($id)) {
                return;
            }
        }
        return;
    }

    // 抽奖
    protected static function stormLottery($id)
    {
        $payload = [
            "id" => $id,
            "color" => "16772431",
            "captcha_token" => "",
            "captcha_phrase" => "",
            "token" => "",
            "csrf_token" => "",
        ];
        $raw = Curl::post('https://api.live.bilibili.com/lottery/v1/Storm/join', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] == 429 || $de_raw['code'] == -429) {
            self::$realname_check = false;
            return false;
        }
        if ($de_raw['code'] == 0) {
            Log::notice($de_raw['data']['mobile_content']);
            return false;
        }
        if ($de_raw['msg'] == '节奏风暴不存在') {
            Log::notice('节奏风暴已结束!');
            return false;
        }
        if ($de_raw['msg'] == '已经领取') {
            Log::notice('节奏风暴已经领取!');
            return false;
        }
        if ($de_raw['msg'] == '访问被拒绝') {
            Log::notice('账号已被封禁!');
            return false;
        }
        return true;
    }

    // 检查ID
    protected static function stormCheckId($room_id)
    {
        $raw = Curl::get('https://api.live.bilibili.com/lottery/v1/Storm/check?roomid=' . $room_id);
        $de_raw = json_decode($raw, true);

        if (empty($de_raw['data']) || $de_raw['data']['hasJoin'] != 0) {
            return false;
        }
        return [
            'id' => $de_raw['data']['id']
        ];
    }

    // 测试 app接口
    public static function testStorm()
    {
        $payload = [
            'roomid' => getenv('ROOM_ID')
        ];
        $raw = Curl::post('https://api.live.bilibili.com/lottery/v1/Storm/join', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        print_r($de_raw);
        exit();
    }
}