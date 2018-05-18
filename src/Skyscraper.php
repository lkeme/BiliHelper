<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class Skyscraper
{
    // ROOM ID
    private static $room_id = 0;

    public static $lock = 0;

    private static $list = [];
    private static $lottery_list = [];

    // RUN
    public static function run($room_id)
    {
        if (getenv('USE_SKYSCRAPER') == 'false') {
            return;
        }
        self::$room_id = $room_id;
        if (!Live::fishingDetection(self::$room_id)) {
            Log::warning('当前直播间[' . self::$room_id . ']存在敏感行为!');
            return;
        }
        Live::goToRoom(self::$room_id);

        if (!($skyscrapers = self::check())) {
            return;
        }

        self::lottery($skyscrapers);
    }

    private static function check()
    {
        if (count(self::$list) > 200) {
            self::$list = null;
            self::$list = [];
        }
        $payload = [
            'roomid' => self::$room_id,
        ];
        $raw = Curl::get('https://api.live.bilibili.com/gift/v3/smalltv/check', Sign::api($payload));
        $de_raw = json_decode($raw, true);

        $total = count($de_raw['data']['list']);
        if (!$total) {
            Log::warning("直播间 [" . self::$room_id . "] 摩天大楼列表为空，放弃抽奖");
            return false;
        }

        $temp = [];
        for ($j = 0; $j < $total; $j++) {
            $raffle_id = $de_raw['data']['list'][$j]['raffleId'];
            if (in_array($raffle_id, self::$list)) {
                continue;
            }
            self::$list[] = $raffle_id;
            $temp[] = $raffle_id;
        }

        if ($total <= 0 || empty($temp)) {
            return false;
        }

        return $temp;
    }

    private static function join($raffle_id)
    {
        $payload = [
            'raffleId' => $raffle_id,
            'roomid' => self::$room_id,
        ];
        $raw = Curl::get('https://api.live.bilibili.com/gift/v3/smalltv/join', Sign::api($payload));
        $de_raw = json_decode($raw, true);

        if (isset($data['code']) && $raw['code']) {
            Log::error("摩天大楼 #{$raffle_id} 抽奖加入失败");
            print_r($de_raw);
        } else {
            Log::notice('参与了房间[' . self::$room_id . ']的摩天大楼[' . $raffle_id . ']抽奖,抽奖状态: ' . $de_raw['msg']);
            self::$lottery_list[] = [
                'raffle_id' => $raffle_id,
                'room_id' => self::$room_id,
            ];
        }
        return;
    }

    private static function lottery(array $skyscrapers): bool
    {
        $total = count($skyscrapers);
        if (empty($skyscrapers) || $total <= 0) {
            return false;
        }
        Live::randFloat();
        foreach ($skyscrapers as $raffle_id) {
            if ($total < 10) {
                usleep(0.2 * 1000000);
            } else {
                sleep(10 / $total);
            }
            self::join($raffle_id);
        }
        return true;
    }

    public static function result()
    {
        if (self::$lock > time()) {
            return;
        }

        if (empty(self::$lottery_list)) {
            self::$lock = time() + 30;
            return;
        }
        for ($i = 0; $i < 5; $i++) {
            if (!isset(self::$lottery_list[$i])) {
                break;
            }
            $room_id = self::$lottery_list[$i]['room_id'];
            $raffle_id = self::$lottery_list[$i]['raffle_id'];
            $payload = [
                'type' => 'small_tv',
                'raffleId' => $raffle_id,
            ];
            $raw = Curl::get('https://api.live.bilibili.com/gift/v3/smalltv/notice', Sign::api($payload));
            $de_raw = json_decode($raw, true);

            if (isset($de_raw['msg']) && $de_raw['msg'] != 'ok') {
                Log::error("摩天大楼 #{$raffle_id} 抽奖失败");
                // 删除id
                unset(self::$lottery_list[$i]);
                self::$lottery_list = array_values(self::$lottery_list);
                continue;
            }

            switch ($de_raw['data']['status']) {
                case 3:
                    break;
                case 2:
                    $temp_info = '直播间[' . $room_id . ']摩天大楼[' . $raffle_id . ']获得[' . $de_raw['data']['gift_name'] . 'X' . $de_raw['data']['gift_num'] . ']';
                    Log::notice($temp_info);

                    // 推送活动抽奖信息
                    if ($de_raw['data']['gift_name'] != '辣条' && $de_raw['data']['gift_name'] != '') {
                        Notice::run('smallTv', $temp_info);
                    }

                    // 删除id
                    unset(self::$lottery_list[$i]);
                    self::$lottery_list = array_values(self::$lottery_list);
                    break;
                default:
                    break;
            }
        }
        self::$lock = time() + 30;
        return;
    }

}