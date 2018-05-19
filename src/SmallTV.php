<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class SmallTV
{
    // ROOM ID
    private static $room_id = 0;

    public static $lock = 0;

    private static $smalltv_list = [];
    private static $smalltv_lottery_list = [];

    // RUN
    public static function run($room_id)
    {
        if (getenv('USE_SMALLTV') == 'false') {
            return;
        }
        self::$room_id = $room_id;
        if (!Live::fishingDetection(self::$room_id)) {
            Log::warning('当前直播间[' . self::$room_id . ']存在敏感行为!');
            return;
        }
        Live::goToRoom(self::$room_id);
        $small_tvs = self::smallTvCheck();
        self::smallTvLottery($small_tvs);
    }

    // SMALLTV CHECK
    protected static function smallTvCheck(): array
    {
        if (count(self::$smalltv_list) > 200) {
            self::$smalltv_list = null;
            self::$smalltv_list = [];
        }
        $payload = [
            'roomid' => self::$room_id,
        ];
        $raw = Curl::get('https://api.live.bilibili.com/AppSmallTV/index', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        $total = count($de_raw['data']['unjoin']);

        $temp = [];
        for ($j = 0; $j < $total; $j++) {
            $raffle_id = $de_raw['data']['unjoin'][$j]['id'];
            if (in_array($raffle_id, self::$smalltv_list)) {
                continue;
            }
            self::$smalltv_list[] = $raffle_id;
            $temp[] = $raffle_id;
        }
        if ($total <= 0 || empty($temp)) {
            return [];
        }
        return $temp;
    }

    //SMALLTV LOTTERY
    protected static function smallTvLottery(array $small_tvs): bool
    {
        $total = count($small_tvs);
        if (empty($small_tvs) || $total <= 0) {
            return false;
        }
        Live::randFloat();
        foreach ($small_tvs as $raffle_id) {
            if ($total < 10) {
                usleep(0.2 * 1000000);
            } else {
                sleep(10 / $total);
            }
            self::smallTVJoin($raffle_id);
        }
        return true;
    }

    // SMALLTV JOIN
    protected static function smallTVJoin($raffle_id)
    {
        $payload = [
            'id' => $raffle_id,
            'roomid' => self::$room_id,
        ];
        $raw = Curl::get('https://api.live.bilibili.com/AppSmallTV/join', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        Log::notice('参与了房间[' . self::$room_id . ']的小电视[' . $raffle_id . ']抽奖,抽奖状态: ' . $de_raw['msg']);
        if ($de_raw['code'] == 0) {
            self::$smalltv_lottery_list[] = [
                'raffle_id' => $raffle_id,
                'room_id' => self::$room_id,
            ];
        } else {
            print_r($de_raw);
        }
    }

    // WIN
    public static function smallTvResult()
    {
        if (self::$lock > time()) {
            return;
        }
        if (empty(self::$smalltv_lottery_list)) {
            self::$lock = time() + 30;
            return;
        }
        for ($i = 0; $i < 10; $i++) {
            if (!isset(self::$smalltv_lottery_list[$i])) {
                break;
            }
            $room_id = self::$smalltv_lottery_list[$i]['room_id'];
            $raffle_id = self::$smalltv_lottery_list[$i]['raffle_id'];
            $payload = [
                'roomid' => $room_id,
                'raffleId' => $raffle_id,
            ];
            $raw = Curl::get('https://api.live.bilibili.com/gift/v2/smalltv/notice', Sign::api($payload));
            $de_raw = json_decode($raw, true);

            switch ($de_raw['data']['status']) {
                case 3:
                    break;
                case 2:
                    $temp_info = '直播间[' . $room_id . ']小电视[' . $raffle_id . ']获得[' . $de_raw['data']['gift_name'] . 'X' . $de_raw['data']['gift_num'] . ']';
                    Log::notice($temp_info);

                    // 推送活动抽奖信息
                    if ($de_raw['data']['gift_name'] != '辣条' && $de_raw['data']['gift_name'] != '') {
                        Notice::run('smallTv', $temp_info);
                    }

                    // 删除id
                    unset(self::$smalltv_lottery_list[$i]);
                    self::$smalltv_lottery_list = array_values(self::$smalltv_lottery_list);
                    break;
                default:
                    break;
            }
        }
        self::$lock = time() + 30;
        return;
    }
}