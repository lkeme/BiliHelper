<?php

trait liveGlobal
{
    //发送弹幕
    public $_liveSendMsg = 'https://api.live.bilibili.com/msg/send';
    //直播状态查询
    public $_liveStatusApi = 'http://api.live.bilibili.com/room/v1/Room/room_init?id=';
    //查询中奖信息
    public $_liveWinningApi = 'http://api.live.bilibili.com/lottery/v1/award/award_list?page=1&month=';
    //获取用户信息
    public $_getUserInfoApi = 'http://live.bilibili.com/user/getuserinfo';
    //默认房间id
    public $_defaultRoomId = 3;
    //当前活动关键字
    public $_activeKeyWord = [
        '漫天花雨',
        '怦然心动',
    ];
    //小电视关键字
    public $_smallTvKeyWord = '小电视';
    //节奏风暴关键字
    public $_stormKeyWord = '节奏风暴';
    //弹幕flag
    public $_danmuFlag = 0;

    public function liveGlobalStart($resp)
    {
        //解析数据，顺便调用查询中奖
        //TODO 可能需要修改逻辑
        if (time() > $this->lock['wincheck']) {
            $data = $this->winningRecord();
            if (empty($data['list'])) {
                $this->log("WIN: " . $data['month'] . '|没有中奖记录 ~', 'magenta', 'LIVE');
            } else {
                $init_time = strtotime(date("y-m-d h:i:s")); //当前时间
               foreach ($data['list'] as $gift){
                    $next_time = strtotime($gift['create_time']);  //礼物时间
                    $day = ceil(($init_time - $next_time) / 86400);  //60s*60min*24h

                    if ($day <= 2 && $gift['update_time'] == '') {
                        $data_info = '您在: ' . $gift['create_time'] . '抽中[' . $gift['gift_name'] . 'X' . $gift['gift_num'] . ']未查看!';

                        //推送实物信息
                        $this->infoSendManager('winIng', $data_info);
                        //TODO 详细写入信息没做
                        $this->writeFileTo('./record/', $this->_userDataInfo['name'] . '-Winning.txt', $data_info);

                        $this->log("Win:" . $data['month'] . '有中奖记录,注意查看 ~', 'cyan', 'LIVE');
                    }
                }
            }
            //暂定24小时查询一次
            $this->lock['wincheck'] = time() + 24 * 60 * 60;
        }

        return $this->parseRespJson($resp);
    }

    //客户端心跳
    public function appHeart()
    {
        if (time() < $this->lock['appHeart']) {
            return true;
        }

        $data = [
            'access_key' => $this->_accessToken,
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '414000',
            'device' => 'android',
            'mobi_app' => 'android',
            'platform' => 'android',
            'ts' => time(),
        ];
        ksort($data);
        $data['sign'] = $this->createSign($data);
        $url = $this->prefix . 'mobile/userOnlineHeart?' . http_build_query($data);
        $payload = [
            'roomid' => $this->_roomRealId,
            'scale' => 'xhdpi'
        ];
        $raw = $this->curl($url, $payload);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] != 0) {
            $this->log($de_raw['msg'], 'bg_red', 'HEART');
            return false;
        }
        $this->lock['appHeart'] = time() + 5 * 60;

        $this->log('AppHeart: OK!', 'magenta', 'HEART');

        $info = '昵称: ' . $this->_userDataInfo['name'] . '|等级: ' . $this->_userDataInfo['level'] . '|';
        $info .= '金瓜子: ' . $this->_userDataInfo['gold'] . '|';
        $info .= '硬币: ' . $this->_userDataInfo['billCoin'];

        $info1 = '银瓜子: ' . $this->_userDataInfo['silver'] . '|经验值: ';
        $info1 .= $this->_userDataInfo['user_intimacy'] . '/' . $this->_userDataInfo['user_next_intimacy'];

        $this->log($info, 'magenta', 'HEART');
        $this->log($info1, 'magenta', 'HEART');

        return true;
    }

    //中奖查询
    public function winningRecord(): array
    {
        $raw = $this->curl($this->_liveWinningApi);
        $de_raw = json_decode($raw, true);

        $month = $de_raw['data']['month_list'][0]['Ym'];

        $url = $this->_liveWinningApi . $month;
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);
        //TODO 暂时么有实际中奖参数
        if (empty($de_raw['data']['list'])) {
            return [
                'month' => $month,
                'list' => [],
            ];
        } else {
            return [
                'month' => $month,
                'list' => $de_raw['data']['list'],
            ];
        }
    }

    //发送弹幕通用模块
    public function sendMsg($info)
    {
        $url = $this->_liveStatusApi . $info['roomid'];
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);

        $headers = array(
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Content-Type: application/x-form-urlencoded',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Host: api.live.bilibili.com',
            'Origin: http://live.bilibili.com',
            'Referer: http://live.bilibili.com/' . $de_raw['data']['room_id'],
        );

        $data = [
            'color' => '16777215',
            'fontsize' => '25',
            'mode' => '1',
            'msg' => $info['content'],
            'rnd' => time(),
            'roomid' => $de_raw['data']['room_id'],
            'csrf_token' => $this->token,
        ];

        return $this->curl($this->_liveSendMsg, $data, true, $headers);

    }

    //app发送弹幕
    public function appSendMsg(array $info)
    {
        $api = 'https://api.live.bilibili.com/api/sendmsg?';
        $data = [
            'access_key' => $this->_accessToken,
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '5220001',
            'device' => 'android',
            'mobi_app' => 'android',
            'platform' => 'android',
            'ts' => time(),
        ];
        ksort($data);
        $data['sign'] = $this->createSign($data);

        $url = $api . http_build_query($data);
        $payload = [
            'cid' => $info['roomid'],
            'mid' => $this->uid,
            'msg' => $info['msg'],
            'rnd' => time(),
            'mode' => '1',
            'pool' => '0',
            'type' => 'json',
            'color' => '16777215',
            'fontsize' => '25',
            'playTime' => '0.0',
        ];

        $raw = $this->appCurl($url, $payload);
        $de_raw = json_decode($raw, true);

        if ($de_raw['code'] == '0') {
            $this->log('Danmu: 自定义弹幕发送成功!', 'yellow', 'SENDMSG');
            return true;
        }
        $this->log('Danmu: 自定义弹幕发送失败!', 'red', 'SENDMSG');
        return true;
    }

    //使用发送弹幕模块
    public function privateSendMsg()
    {
        //TODO 暂时性功能 有需求就修改
        if (time() < $this->lock['privateSendMsg']) {
            return true;
        }
        foreach ($this->_privateSendMsgInfo as $value) {
            if ($value == '') {
                return true;
            }
        }
        $raw = $this->sendMsg($this->_privateSendMsgInfo);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] == '0') {
            $this->log('Danmu: 自定义弹幕发送成功!', 'yellow', 'SENDMSG');
            $this->lock['privateSendMsg'] = time() + $this->_privateSendMsgInfo['time'];
            return true;
        }
        $this->log('Danmu: 自定义弹幕发送失败!', 'red', 'SENDMSG');
        //如果失败一小时重试一次
        $this->lock['privateSendMsg'] = time() + 3600;
        return true;
    }

    //查询有效直播间
    public function liveCheck()
    {
        for ($i = 0; $i < 1000; $i++) {
            $url = $this->_liveStatusApi . $i;
            $raw = $this->curl($url);
            $raw = json_decode($raw, true);
            if ($raw['code'] == 0 || $raw['msg'] == 'ok' && $raw['message'] == 'ok') {
                return $raw['data']['room_id'];
            }
        }
        //3 的真实id
        return 23058;

    }

    //查询直播间有效
    public function liveRoomStatus($roomid)
    {
        $url = $this->_liveStatusApi . $roomid;
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        if ($raw['code'] == 0) {
            if ($raw['data']['is_hidden']) {
                return false;
            }
            if ($raw['data']['is_locked']) {
                return false;
            }
            if ($raw['data']['encrypted']) {
                return false;
            }
            return $raw['data']['room_id'];
        }
        return false;
    }

    //写入文件
    public function writeFileTo($path, $filename, $data)
    {
        if (!file_exists($path)) {
            mkdir($path);
            chmod($path, 0777);
        }
        $completePath = $path . $filename;
        file_put_contents($completePath, $data . PHP_EOL, FILE_APPEND);
        return true;
    }

    //cmd数据解析
    public function parseRespJson($resp)
    {
        if (strlen($resp) == 4) {
            $num = unpack('N', $resp)[1];
            return 'ONLINE: 当前直播间有' . $num . '人在线';
        }
        $resp = json_decode($resp, true);
        switch ($resp['cmd']) {
            case 'DANMU_MSG':
                /**
                 * 弹幕消息
                 */
                $info = strlen($resp['info'][1]) > 10 ? substr($resp['info'][1], 0, 9) : $resp['info'][1];
                return 'DANMU_MSG: ' . $resp['info'][2][1] . " : " . $info;
                break;
            case 'SEND_GIFT':
                /**
                 * 礼物消息, 用户包裹和瓜子的数据直接在里面, 真是窒息
                 */
                $data = $resp['data'];
                return 'SEND_GIFT: ' . $data['uname'] . ' 赠送' . $data['num'] . '份' . $data['giftName'];
                break;
            case 'ACTIVITY_EVENT':
                /**
                 * 活动
                 */
                //var_dump($resp);
                return 'ACTIVITY_EVENT: ' . $resp['data']['keyword'] . ' | ' . $resp['data']['type'];
                break;
            case 'GUARD_MSG':
                /**
                 * 舰队消息
                 */
                return 'GUARD_MSG: ' . $resp['msg'];
                break;

            case 'LIVE':
                /**
                 * 开始直播
                 */
                return 'LIVE: [房间]' . $resp['roomid'] . ' | 开始直播';
                break;
            case 'PREPARING':
                /**
                 * 准备直播
                 */
                return 'PREPARING: [房间]' . $resp['roomid'] . ' | 准备直播';
                break;
            case 'WELCOME_GUARD':
                /**
                 * 欢迎消息-舰队
                 */
                return 'WELCOME_GUARD: [房间]' . $resp['data']['username'];
                break;
            case 'WELCOME':
                /**
                 * 欢迎消息
                 */
                return 'WELCOME: ' . $resp['data']['uname'] . '进入房间';
                break;
            case 'SYS_GIFT':
                /**
                 * 系统礼物消息, 广播
                 */
                //TODO 节奏风暴暂时搁置
                if (strpos($resp['msg'], $this->_stormKeyWord) !== false) {
                    $this->writeFileTo(' ./tmp / ', 'storm . txt', json_encode($resp));
                    return [
                        'type' => 'storm',
                        'roomid' => $resp['roomid'],
                        'msg' => $resp['msg'],
                    ];
                }
                //TODO 活动抽奖 暂定每期修改
                foreach ($this->_activeKeyWord as $value) {
                    if (strpos($resp['msg'], $value) !== false) {
                        return [
                            'type' => 'active',
                            'real_roomid' => $resp['real_roomid'],
                            'msg' => $resp['msg'],
                        ];
                    }
                }

                return 'SYS_GIFT: ' . $resp['msg'];
                break;
            case 'SYS_MSG':
                /**
                 * 系统消息, 广播
                 */

                if (strpos($resp['msg'], $this->_smallTvKeyWord) === false) {
                    var_dump($resp);
                    return 'SYS_MSG: ' . isset($resp['msg']) ? $resp['msg'] : '不知道是什么消息';
                }
                return [
                    'type' => 'smalltv',
                    'real_roomid' => $resp['real_roomid'],
                    'msg' => $resp['msg'],
                ];
                break;
            case 'SPECIAL_GIFT':
                /**
                 * 特殊礼物消息 --节奏风暴
                 */
                //暂时打印节奏风暴包
                var_dump($resp);
                if (array_key_exists('39', $resp['data'])) {
                    if ($resp['data']['39']['action'] == 'end') {
                        return 'SPECIAL_GIFT: ' . $resp['data']['39']['id'] . ' | 节奏风暴结束';
                    }
                    //TODO
                    if ($resp['data']['39']['action'] == 'start') {
                        return [
                            'type' => 'storm',
                            'id' => $resp['data']['39']['id'],
                            'msg' => $resp['data']['39']['content'],
                        ];
                    }
                }
                var_dump($resp['data']);
                return 'SPECIAL_GIFT: ' . $resp['data']['39']['id'] . ' | ' . $resp['data']['39']['content'] . ' | 不知道是什么东西';
                break;
            case 'WELCOME_ACTIVITY':
                /**
                 * 欢迎消息-活动
                 */
                return 'WELCOME_ACTIVITY: ' . $resp['data']['type'] . ' | ' . $resp['data']['uname'];
                break;
            case 'GUARD_BUY':
                /**
                 * 舰队购买
                 */
                return 'GUARD_BUY: [房间]' . $resp['roomid'] . ' | ' . $resp['data']['username'] . '购买舰队';
                break;
            case 'RAFFLE_START':
                /**
                 * 抽奖开始
                 */
                return 'RAFFLE_START: [房间]' . $resp['roomid'] . ' | ' . $resp['data']['raffleId'];
                break;
            case 'RAFFLE_END':
                /**
                 * 抽奖结束
                 */
                return 'RAFFLE_END: [房间]' . $resp['roomid'] . ' | ' . $resp['data']['raffleId'];
                break;
            case 'TV_START':
                /**
                 * 小电视抽奖开始
                 */
                return 'TV_START: [房间]' . $resp['data']['msg']['real_roomid'] . ' | ' . $resp['data']['msg']['msg'];
                break;
            case 'TV_END':
                /**
                 * 小电视抽奖结束
                 */
                return 'TV_END: ' . $resp['data']['id'] . ' | ' . $resp['data']['mobileTips'];
                break;
            case 'EVENT_CMD':
                /**
                 * 活动相关
                 */
                return 'EVENT_CMD: [房间]' . $resp['roomid'] . ' | ' . $resp['data']['event_type'];
                break;
            case 'ROOM_SILENT_ON':
                /**
                 * 房间开启禁言
                 */
                return 'ROOM_SILENT_ON: [房间]' . $resp['roomid'] . ' | 开启禁言';
                break;
            case 'ROOM_SILENT_OFF':
                /**
                 * 房间禁言结束
                 */
                return 'ROOM_SILENT_OFF: [房间]' . $resp['roomid'] . ' | 禁言结束';
                break;
            case 'ROOM_SHIELD':
                /**
                 * 房间屏蔽
                 */
                return 'ROOM_SHIELD: [房间]' . $resp['roomid'] . ' | 屏蔽';
                break;
            case 'ROOM_BLOCK_MSG':
                /**
                 * 房间封禁消息
                 */
                return 'ROOM_BLOCK_MSG: [房间]' . $resp['roomid'] . ' | 封禁:' . $resp['uname'];
                break;
            case 'ROOM_ADMINS':
                /**
                 * 管理员变更
                 */
                return 'ROOM_ADMINS: [房间]' . $resp['roomid'] . ' | 管理员变更';
                break;
            case 'CHANGE_ROOM_INFO':
                /**
                 * 房间设置变更
                 */
                return 'CHANGE_ROOM_INFO: [房间]' . $resp['roomid'] . ' | 设置变更';
                break;
            case 'WISH_BOTTLE':
                /**
                 * 许愿瓶
                 */
                return 'WISH_BOTTLE: [房间]' . $resp['data']['wish']['uid'] . ' | ' . $resp['data']['wish']['content'];
                break;
            case 'CUT_OFF':
                /**
                 * 直播强制切断
                 */
                return 'CUT_OFF: [房间]' . $resp['roomid'] . ' | ' . $resp['msg'];
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
    }

    //分享sign生成
    public function shareSign()
    {
        $this->uid = $this->getUserInfo();
        $temp = md5($this->uid . $this->_roomRealId . 'bilibili');
        $temp .= 'bilibili';
        $temp = sha1($temp);
        return $temp;
    }

    //获取用户UID
    public function getUserInfo()
    {
        $url = $this->prefix . 'i / api / liveinfo';
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        //TODO 暂时返回uid
        return $raw['data']['userInfo']['uid'];
    }

    //内存检测
    public function checkMemory($msg)
    {
        $size = memory_get_usage();
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $memory = @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
        $data = $msg . '时内存: ' . $memory;
        $this->writeFileTo(' ./tmp / ', 'memory . log', $data);
    }

    //随机延时
    public function randFloat($min = 2, $max = 5)
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        sleep($rand);
        return;
    }

    //访问一次抽奖直播间
    public function goToRoom($roomid)
    {
//        $url = 'http://live.bilibili.com/' . $roomid;
//        $this->curl($url);
//        return;

        $url = 'https://api.live.bilibili.com/room/v1/Room/room_entry_action';
        $data = [
            "room_id" => $roomid,
            "csrf_token" => $this->token,
        ];
        $this->curl($url, $data);
        return;
    }

    //延迟任务 防止一定程度被ban
    public function delayTasks()
    {
        $hour = date('H');
        if ($hour >= 2 && $hour < 6) {
            $sleeptime = 60 * 60 * 5;
            $this->log('Sleep: 本宝宝睡眠时间|(2:00 - 7:00不营业)!', 'yellow', 'SLEEP');
            sleep($sleeptime);
            return true;
        }
        return true;
    }

    //删除cookie的空格和回车
    public function trimAll($str)
    {
        $rule = array("\r\n", " ", "　", "\t", "\n", "\r");
        return str_replace($rule, '', $str);
    }

}
