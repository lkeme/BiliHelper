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

class Live
{
    // RUN
    public static function run()
    {
    }

    // GET RANDOW ROOM_ID
    public static function getUserRecommend()
    {
        while (1) {
            $page = rand(1, 10);
            $raw = Curl::get('https://api.live.bilibili.com/area/liveList?area=all&order=online&page=' . $page);
            $de_raw = json_decode($raw, true);
            if ($de_raw['code'] != '0') {
                continue;
            }
            break;
        }
        $rand_num = rand(1, 29);
        return $de_raw['data'][$rand_num]['roomid'];

    }

    // GET REALROOM_ID
    public static function getRealRoomID(int $room_id): int
    {
        $raw = Curl::get('https://api.live.bilibili.com/room/v1/Room/room_init?id=' . $room_id);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code']) {
            Log::warning($room_id . ' : ' . $de_raw['msg']);
            return false;
        }
        if ($de_raw['data']['is_hidden']) {
            return false;
        }
        if ($de_raw['data']['is_locked']) {
            return false;
        }
        if ($de_raw['data']['encrypted']) {
            return false;
        }
        return $de_raw['data']['room_id'];

    }

    // Fishing Detection
    public static function fishingDetection($room_id): bool
    {
        //钓鱼检测
        if (!self::getRealRoomID($room_id)) {
            return false;
        }
        return true;
    }

    // RANDOM DELAY
    public static function randFloat($min = 3, $max = 5): bool
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        sleep($rand);
        return true;
    }

    //TO ROOM
    public static function goToRoom($room_id): bool
    {
        $payload = [
            'room_id' => $room_id,
        ];
        Curl::post('https://api.live.bilibili.com/room/v1/Room/room_entry_action', Sign::api($payload));
        Log::info('进入直播间[' . $room_id . ']抽奖!');
        return true;
    }

}