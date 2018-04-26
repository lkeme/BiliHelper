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
use lkeme\BiliHelper\SmallTV;
use lkeme\BiliHelper\Storm;

class DataTreating
{
    // SMALLTV KEY
    protected static $smalltv_keyword = '小电视';
    // STORM KEY
    protected static $storm_keyword = '节奏风暴';
    // ACTIVE KEY
    protected static $active_keyword = [
        '漫天花雨',
        '怦然心动',
    ];

    // PARSE ARRAY
    public static function socketArrayToDispose(array $data)
    {
        switch ($data['type']) {
            case 'storm':
                Storm::run($data);
                break;
            case 'active':
                break;
            case 'smalltv':
                SmallTV::run($data['room_id']);
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
            Log::info('当前直播间有' . $num . '人在线!');
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
                // 20倍 节奏风暴
                if (strpos($resp['msg'], self::$storm_keyword) !== false) {
                    return [
                        'type' => 'storm',
                        'num' => 20,
                        'room_id' => $resp['roomid'],
                    ];
                }
                // TODO 活动抽奖 暂定每期修改
                foreach (self::$active_keyword as $value) {
                    if (strpos($resp['msg'], $value) !== false) {
                        return [
                            'type' => 'active',
                            'room_id' => $resp['real_roomid'],
                        ];
                    }
                }
                break;
            case 'SYS_MSG':
                /**
                 * 系统消息, 广播
                 */
                if (strpos($resp['msg'], self::$smalltv_keyword) !== false) {
                    return [
                        'type' => 'smalltv',
                        'room_id' => $resp['real_roomid'],
                    ];
                }
                var_dump($resp);
                break;
            case 'SPECIAL_GIFT':
                /**
                 * 特殊礼物消息 --节奏风暴
                 */
                //暂时打印节奏风暴包
                var_dump($resp);
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