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

class MaterialObject
{
    protected static $lock = 0;

    // RUN
    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }
        self::drawLottery();

        self::$lock = time() + 5 * 60;
    }

    // DRAW LOTTERY
    protected static function drawLottery(): bool
    {
        $block_key_list = ['测试', '加密', 'test', 'TEST'];
        for ($i = 70; $i < 90; $i++) {
            $payload = [
                'aid' => $i,
            ];
            $raw = Curl::get('https://api.live.bilibili.com/lottery/v1/box/getStatus', Sign::api($payload));
            $de_raw = json_decode($raw, true);

            if ($de_raw['code'] != '0') {
                continue;
            }

            $title = $de_raw['data']['title'];
            foreach ($block_key_list as $block_key) {
                if (strpos($title, $block_key) !== false) {
                    continue;
                }
            }
            $lotterys = $de_raw['data']['typeB'];
            $num = 1;
            foreach ($lotterys as $lottery) {
                $join_end_time = $lottery['join_end_time'];
                $join_start_time = $lottery['join_start_time'];
                if ($join_end_time > time() && time() > $join_start_time) {
                    switch ($lottery['status']) {
                        case 3:
                            Log::info('实物抽奖: 当前轮次已经结束!');
                            break;
                        case 1:
                            Log::info('实物抽奖: 当前轮次已经抽过了!');
                            break;
                        case -1:
                            Log::info('实物抽奖: 当前轮次暂未开启!');
                            break;
                        case 0:
                            Log::info('实物抽奖: 当前轮次正在抽奖中!');

                            $payload = [
                                'aid' => $i,
                                'number' => $num,
                            ];
                            $raw = Curl::get('https://api.live.bilibili.com/lottery/v1/box/draw', Sign::api($payload));
                            $de_raw = json_decode($raw, true);

                            if ($de_raw['code'] == 0) {
                                Log::info('实物抽奖: 成功!');

                            }
                            $num++;
                            break;

                        default:
                            Log::info('实物抽奖: 当前轮次状态码[' . $lottery['status'] . ']未知!');
                            break;
                    }
                }
            }
        }
        return true;
    }
}