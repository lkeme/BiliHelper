<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class DataTreating
{
    // 活动关键字
    protected static $active_keys = [];

    /**
     * @use RUN
     */
    public static function run()
    {
        if (empty(self::$active_keys)) {
            self::reacKeys();
        }

    }

    // 读取关键字
    private static function reacKeys()
    {
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
                if (getenv('AUTO_KEYS') == 'false') {
                    foreach (self::$active_keys as $key) {
                        if (strpos($resp['msg'], $key) !== false) {
                            return [
                                'type' => 'active',
                                'title' => $key,
                                'room_id' => $resp['real_roomid']
                            ];
                        }
                    }
                }

                break;
            case 'SYS_MSG':
                /**
                 * 系统消息, 广播
                 */
                // 屏蔽系统公告
                if ((strpos($resp['msg'], '系统公告') !== false)) {
                    break;
                }
                if (getenv('AUTO_KEYS') == 'false') {
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
                }
                break;
            case 'SPECIAL_GIFT':
                /**
                 * 特殊礼物消息 --节奏风暴
                 */
                if (array_key_exists('39', $resp['data'])) {
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
                 * 1 《第五人格》哔哩哔哩直播预选赛六强诞生！
                 * 2 全区广播：<%user_name%>送给<%user_name%>1个嗨翻全城，快来抽奖吧
                 * 3 <%user_name%> 在 <%user_name%> 的房间开通了总督并触发了抽奖，点击前往TA的房间去抽奖吧
                 * 4 欢迎 <%总督 user_name%> 登船
                 * 5 恭喜 <%user_name%> 获得大奖 <%23333x银瓜子%>, 感谢 <%user_name%> 的赠送
                 * 6 <%user_name%> 在直播间 <%529%> 使用了 <%20%> 倍节奏风暴，大家快去跟风领取奖励吧！(只报20的)
                 */

                $msg_type = $resp['msg_type'];
                $real_roomid = $resp['real_roomid'];
                $msg_common = str_replace(' ', '', $resp['msg_common']);

                if ($msg_type == 2) {
                    if (getenv('AUTO_KEYS') != 'true') {
                        break;
                    }
                    $str_gift = explode('，', explode('%>', $msg_common)[2])[0];
                    if (strpos($str_gift, '个') !== false) {
                        $raffle_name = explode('个', $str_gift)[1];
                    } elseif (strpos($str_gift, '了') !== false) {
                        $raffle_name = explode('了', $str_gift)[1];
                    } else {
                        $raffle_name = $str_gift;
                    }
                    return [
                        'type' => 'active',
                        'title' => $raffle_name,
                        'room_id' => $real_roomid
                    ];

                } elseif ($msg_type == 6) {
                    if (strpos($msg_common, '节奏风暴') !== false) {
                        return [
                            'type' => 'storm',
                            'num' => 20,
                            'room_id' => $real_roomid,
                        ];
                    }
                } else {
                    break;
                }

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
