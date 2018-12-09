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
use lkeme\BiliHelper\Storm;

class DataTreating
{
    // 节奏风暴关键字
    protected static $storm_keys = [];
    // 活动关键字
    protected static $active_keys = [];

    /**
     * @use RUN
     */
    public static function run()
    {
        if (empty(self::$storm_keys) && empty(self::$active_keys)) {
            self::reacKeys();
        }

    }

    // 读取关键字
    private static function reacKeys()
    {
        $temp = getenv('STORM_KEYS');
        $keys = explode('|', $temp);
        foreach ($keys as $key) {
            if ($key != '') {
                array_push(self::$storm_keys, $key);
            }
        }

        $temp = getenv('ACTIVE_KEYS');
        $keys = explode('|', $temp);
        foreach ($keys as $key) {
            if ($key != '') {
                array_push(self::$active_keys, $key);
            }
        }

    }

    // PARSE ARRAY
    public static function socketArrayToDispose(array $data)
    {
        switch ($data['type']) {
            case 'storm':
                Storm::run($data);
                break;
            case 'active':
                RaffleHandler::run($data['room_id'], 'USE_ACTIVE', $data['title']);
                break;
            case 'unkown':
                break;
            default:
                break;
        }
        return;
    }

    // PARSE JSON
    public static function socketJsonToArray($resp)
    {
        if (strlen($resp) == 4) {
            $num = unpack('N', $resp)[1];
            Log::info("当前直播间现有[{$num}]人聚众搞基!");
            return false;
        }

        $resp = json_decode($resp, true);

        switch ($resp['cmd']) {
            case 'DANMU_MSG':
                /**
                 * 弹幕消息
                 */
                break;
            case 'SEND_GIFT':
                /**
                 * 礼物消息, 用户包裹和瓜子的数据直接在里面, 真是窒息
                 */
                break;
            case 'ACTIVITY_EVENT':
                /**
                 * 活动
                 */
                //var_dump($resp);
                break;
            case 'GUARD_MSG':
                /**
                 * 舰队消息
                 */
                break;
            case 'LIVE':
                /**
                 * 开始直播
                 */
                break;
            case 'PREPARING':
                /**
                 * 准备直播
                 */
                break;
            case 'WELCOME_GUARD':
                /**
                 * 欢迎消息-舰队
                 */
                break;
            case 'WELCOME':
                /**
                 * 欢迎消息
                 */
                break;
            case 'SYS_GIFT':
                /**
                 * 系统礼物消息, 广播
                 */
                // TODO 节奏风暴暂时搁置
                // 20倍 风暴
                foreach (self::$storm_keys as $key) {
                    if (strpos($resp['msg'], $key) !== false) {
                        return [
                            'type' => 'storm',
                            'num' => 20,
                            'room_id' => $resp['roomid'],
                        ];
                    }
                }

                // TODO 活动抽奖 暂定每期修改
                foreach (self::$active_keys as $key) {
                    if (strpos($resp['msg'], $key) !== false) {
                        return [
                            'type' => 'active',
                            'title' => $key,
                            'room_id' => $resp['real_roomid']
                        ];
                    }
                }
                break;
            case 'SYS_MSG':
                /**
                 * 系统消息, 广播
                 */
                // 关键词屏蔽
                if ((strpos($resp['msg'], '系统公告') !== false)) {
                    break;
                }
                // TODO 小电视|摩天大楼|高能活动统一检测
                foreach (self::$active_keys as $key) {
                    if (strpos($resp['msg'], $key) !== false) {
                        return [
                            'type' => 'active',
                            'title' => $key,
                            'room_id' => $resp['real_roomid']
                        ];
                    }
                }
                var_dump($resp);
                break;
            case 'SPECIAL_GIFT':
                /**
                 * 特殊礼物消息 --节奏风暴
                 */
                //暂时打印节奏风暴包
                //var_dump($resp);
                if (array_key_exists('39', $resp['data'])) {
                    //TODO
                    if ($resp['data']['39']['action'] == 'start') {
                        return [
                            'type' => 'storm',
                            'num' => 1,
                            'id' => $resp['data']['39']['id'],
                        ];
                    }
                }
                var_dump($resp['data']);
                break;
            case 'WELCOME_ACTIVITY':
                /**
                 * 欢迎消息-活动
                 */
                break;
            case 'GUARD_BUY':
                /**
                 * 舰队购买
                 */
                break;
            case 'RAFFLE_START':
                /**
                 * 抽奖开始
                 */
                break;
            case 'RAFFLE_END':
                /**
                 * 抽奖结束
                 */
                break;
            case 'TV_START':
                /**
                 * 小电视抽奖开始
                 */
                break;
            case 'TV_END':
                /**
                 * 小电视抽奖结束
                 */
                break;
            case 'ENTRY_EFFECT':
                /**
                 *  进入房间提示
                 */
                break;
            case 'EVENT_CMD':
                /**
                 * 活动相关
                 */
                break;
            case 'ROOM_SILENT_ON':
                /**
                 * 房间开启禁言
                 */
                break;
            case 'ROOM_SILENT_OFF':
                /**
                 * 房间禁言结束
                 */
                break;
            case 'ROOM_SHIELD':
                /**
                 * 房间屏蔽
                 */
                break;
            case 'COMBO_END':
                /**
                 * COMBO结束
                 */
            case 'ROOM_BLOCK_MSG':
                /**
                 * 房间封禁消息
                 */
                break;
            case 'ROOM_ADMINS':
                /**
                 * 管理员变更
                 */
                break;
            case 'CHANGE_ROOM_INFO':
                /**
                 * 房间设置变更
                 */
                break;
            case 'WISH_BOTTLE':
                /**
                 * 许愿瓶
                 */
                break;
            case 'CUT_OFF':
                /**
                 * 直播强制切断
                 */
                break;
            case  'ROOM_RANK':
                /**
                 * 周星榜
                 */
                break;
            case 'NOTICE_MSG':
                /**
                 * 分区通知
                 */
                break;
            default:
                // 新添加的消息类型
                if (!is_null($resp)) {
                    var_dump($resp);
                }
                return [
                    'type' => 'unkown',
                    'raw' => $resp['cmd'],
                ];
                break;
        }
        return false;
    }
}
