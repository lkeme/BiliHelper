<?php

trait liveGlobal
{
    //发送弹幕
    public $_liveSendMsg = 'https://live.bilibili.com/msg/send';
    //直播状态查询
    public $_liveStatusApi = 'http://api.live.bilibili.com/room/v1/Room/room_init?id=';
    //查询中奖信息
    public $_liveWinningApi = 'http://api.live.bilibili.com/lottery/v1/award/award_list?page=1&month=';
    //获取用户信息
    public $_getUserInfoApi = 'http://live.bilibili.com/user/getuserinfo';
    //默认房间id
    public $_defaultRoomId = 3;
    //全局用户名
    public $_userName = '';

    public function liveGlobalStart($resp)
    {
        //解析数据，顺便调用查询中奖
        //TODO 可能需要修改逻辑
        if (time() > $this->lock['wincheck']) {
            $data = $this->winningRecord();
            if ($data['list'] == '') {
                $this->log("WIN: " . $data['month'] . '|No Winning ~', 'blue', 'LIVE');
            } else {
                $path = './record/' . $this->_userName . '-Winning.txt';
                file_put_contents($path, date("Y-m-d H:i:s") . '|' . $data['list'] . "\r\n", FILE_APPEND);
                //TODO 详细写入信息没做
                $this->log("Win:" . $data['month'] . '有中奖记录 ~', 'cyan', 'LIVE');
            }
            $this->lock['wincheck'] += 12 * 60 * 60;
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
        $raw = $this->curl($url,$payload);
        $data = json_decode($raw, true);
        if ($data['code'] != 0) {
            $this->log($data['msg'], 'bg_red', '心跳');
            return false;
        }
        $this->lock['heart'] += 5 * 60;

        $this->log('appHeart: OK!', 'magenta', '心跳');
        return true;
    }

    //中奖查询
    public function winningRecord()
    {
        $raw = $this->curl($this->_liveWinningApi);
        $raw = json_decode($raw, true);

        $month = $raw['data']['month_list'][0]['Ym'];

        $url = $this->_liveWinningApi . $month;
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        //TODO 暂时么有实际中奖参数
        if (empty($raw['data']['list'])) {
            return [
                'month' => $month,
                'list' => '',
            ];
        } else {
            return [
                'month' => $month,
                'list' => $raw['data']['list'],
            ];
        }
    }

    //发送弹幕
    public function sendMsg($info)
    {
        $headers = array(
            'Content-Type: application/x-form-urlencoded',
        );
        $data = [
            'color' => '#7c1482',
            'fontsize' => '25',
            'mode' => '1',
            'msg' => $info['content'],
            'rnd' => time(),
            'roomid' => $info['roomid'],
        ];
        return $this->curl($this->_liveSendMsg, $data, true, $headers);
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
        if ($raw['code'] == 0 || $raw['msg'] == 'ok' && $raw['message'] == 'ok') {
            return $raw['data']['room_id'];
        }
        return false;
    }

    //cmd数据解析
    public function parseRespJson($resp)
    {
        $resp = json_decode($resp, true);
        switch ($resp['cmd']) {
            case 'DANMU_MSG':
                /**
                 * 弹幕消息
                 * {"info":[[0,5,25,16738408,1517306023,1405289835,0,"c23b254e",0],"好想抱回家",[37089851,"Dark2笑",0,1,1,10000,1,"#7c1482"],[17,"言叶","枫言w",367,16752445,"union"],[35,0,10512625,">50000"],["title-140-2","title-140-2"],0,1,{"uname_color":"#7c1482"}],"cmd":"DANMU_MSG","_roomid":1175880}
                 */
                $info = strlen($resp['info'][1]) > 10 ? substr($resp['info'][1], 0, 9) : $resp['info'][1];
                return 'DANMU_MSG: ' . $resp['info'][2][1] . " : " . $info;
                break;
            case 'SEND_GIFT':
                /**
                 * 礼物消息, 用户包裹和瓜子的数据直接在里面, 真是窒息
                 * {"cmd":"SEND_GIFT","data":{"giftName":"B坷垃","num":1,"uname":"Vilitarain","rcost":28963232,"uid":2081485,"top_list":[{"uid":3091444,"uname":"丶你真难听","face":"http://i1.hdslb.com/bfs/face/b1e39bae99efc6277b95993cd2a0d7c176b52ce2.jpg","rank":1,"score":1657600,"guard_level":3,"isSelf":0},{"uid":135813741,"uname":"EricOuO","face":"http://i2.hdslb.com/bfs/face/db8cf9a9506d2e3fe6dcb3d8f2eee4da6c0e3e2d.jpg","rank":2,"score":1606200,"guard_level":2,"isSelf":0},{"uid":10084110,"uname":"平凡无奇迷某人","face":"http://i2.hdslb.com/bfs/face/df316f596d7dcd8625de7028172027aa399323af.jpg","rank":3,"score":1333100,"guard_level":3,"isSelf":0}],"timestamp":1517306026,"giftId":3,"giftType":0,"action":"赠送","super":1,"price":9900,"rnd":"1517301823","newMedal":1,"newTitle":0,"medal":{"medalId":"397","medalName":"七某人","level":1},"title":"","beatId":"0","biz_source":"live","metadata":"","remain":0,"gold":100,"silver":77904,"eventScore":0,"eventNum":0,"smalltv_msg":[],"specialGift":null,"notice_msg":[],"capsule":{"normal":{"coin":68,"change":1,"progress":{"now":1100,"max":10000}},"colorful":{"coin":0,"change":0,"progress":{"now":0,"max":5000}}},"addFollow":0,"effect_block":0},"_roomid":50583}
                 */
                $data = $resp['data'];
                return 'SEND_GIFT: ' . $data['uname'] . ' 赠送' . $data['num'] . '份' . $data['giftName'];
                break;
            case 'ACTIVITY_EVENT':
                /**
                 * 活动
                 * {"cmd":"ACTIVITY_EVENT","data":{"keyword":"newspring_2018","type":"cracker","limit":300000,"progress":41818},"_roomid":14893}
                 */
                //var_dump($resp);
                return 'ACTIVITY_EVENT: [房间]' . $resp['_roomid'] . '|' . $resp['data']['keyword'];
                break;
            case 'GUARD_MSG':
                /**
                 * 舰队消息
                 * {"cmd":"GUARD_MSG","msg":"欢迎 :?总督 Tikiあいしてる:? 登船","roomid":237328,"_roomid":237328}
                 */
                return 'GUARD_MSG: [房间]' . $resp['_roomid'] . '|' . $resp['msg'];
                break;

            case 'LIVE':
                /**
                 * 开始直播
                 * {"cmd":"LIVE","roomid":66688,"_roomid":66688}
                 */
                return 'LIVE: [房间]' . $resp['_roomid'] . '|开始直播';
                break;
            case 'PREPARING':
                /**
                 * 准备直播
                 * {"cmd":"PREPARING","round":1,"roomid":"66287","_roomid":66287}
                 */
                return 'PREPARING: [房间]' . $resp['_roomid'] . '|准备直播';
                break;
            case 'WELCOME_GUARD':
                /**
                 * 欢迎消息-舰队
                 * {"cmd":"WELCOME_GUARD","data":{"uid":33401915,"username":"按时咬希尔","guard_level":3,"water_god":0},"roomid":1374115,"_roomid":1374115}
                 */
                return 'WELCOME_GUARD: [房间]' . $resp['_roomid'] . '|' . $resp['username'];
                break;
            case 'WELCOME':
                /**
                 * 欢迎消息
                 * {"cmd":"WELCOME","data":{"uid":42469177,"uname":"还是森然","isadmin":0,"vip":1},"roomid":10248,"_roomid":10248}
                 * {"cmd":"WELCOME","data":{"uid":36157605,"uname":"北熠丶","is_admin":false,"vip":1},"_roomid":5096}
                 */
                return 'WELCOME: ' . $resp['data']['uname'] . '进入房间';
                break;
            case 'SYS_GIFT':
                /**
                 * 系统礼物消息, 广播
                 * {"cmd":"SYS_GIFT","msg":"叫我大兵就对了:?  在贪玩游戏的:?直播间5254205:?内赠送:?109:?共225个","rnd":"930578893","uid":30623524,"msg_text":"叫我大兵就对了在贪玩游戏的直播间5254205内赠送红灯笼共225个","_roomid":23058}
                 * {"cmd":"SYS_GIFT","msg":"亚瑟不懂我心在直播间26057开启了新春抽奖，红包大派送啦！一起来沾沾喜气吧！","msg_text":"亚瑟不懂我心在直播间26057开启了新春抽奖，红包大派送啦！一起来沾沾喜气吧！","tips":"亚瑟不懂我心在直播间26057开启了新春抽奖，红包大派送啦！一起来沾沾喜气吧！","url":"http://live.bilibili.com/26057","roomid":26057,"real_roomid":26057,"giftId":110,"msgTips":0,"_roomid":23058}
                 */
                //TODO 节奏风暴暂时搁置
                $flag = '节奏风暴';
                if (strpos($resp['msg'], $flag) !== false) {
                    //file_put_contents('./tmp/storm.txt', json_encode($resp), FILE_APPEND);

                }
                return 'SYS_GIFT: ' . $resp['msg'];
                break;
            case 'SYS_MSG':
                /**
                 * 系统消息, 广播
                 * {"cmd":"SYS_MSG","msg":"亚军主播【赤瞳不是翅桶是赤瞳】开播啦，一起去围观！","msg_text":"亚军主播【赤瞳不是翅桶是赤瞳】开播啦，一起去围观！","url":"http://live.bilibili.com/5198","_roomid":23058}
                 * {"cmd":"SYS_MSG","msg":"【国民六妹】:?在直播间:?【896056】:?赠送 小电视一个，请前往抽奖","msg_text":"【国民六妹】:?在直播间:?【896056】:?赠送 小电视一个，请前往抽奖","rep":1,"styleType":2,"url":"http://live.bilibili.com/896056","roomid":896056,"real_roomid":896056,"rnd":1517304134,"tv_id":"36676","_roomid":1199214}
                 */
                $flag = '小电视';
                if (strpos($resp['msg'], $flag) === false) {
                    var_dump($resp);
                    return 'SYS_MSG: [房间]' . $resp['_roomid'] . '|' . isset($resp['msg']) ? $resp['msg'] : '不知道是什么消息';
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
                 * {"cmd":"SPECIAL_GIFT","data":{"39":{"id":169666,"time":90,"hadJoin":0,"num":1,"content":"啦噜啦噜","action":"start","storm_gif":"http://static.hdslb.com/live-static/live-room/images/gift-section/mobilegift/2/jiezou.gif?2017011901"}},"_roomid":5096}
                 * {"cmd":"SPECIAL_GIFT","data":{"39":{"id":169666,"action":"end"}},"_roomid":5096}
                 */
                //暂时打印节奏风暴包
                var_dump($resp);
                if (array_key_exists('39', $resp['data'])) {
                    if ($resp['data']['39']['action'] == 'end') {
                        return 'SPECIAL_GIFT: [房间]' . $resp['_roomid'] . '|节奏风暴结束';
                    }

                    if ($resp['data']['39']['action'] == 'start') {
                        return [
                            'type' => 'storm',
                            'msg' => 'SPECIAL_GIFT: [房间]' . $resp['_roomid'] . '|节奏风暴开始',
                            'id' => $resp['data']['39']['id'],
                            'time' => $resp['data']['39']['time'],
                            'hadJoin' => $resp['data']['39']['hadJoin'],
                            'num' => $resp['data']['39']['num'],
                            'content' => $resp['data']['39']['content'],
                            'roomid' => $resp['_roomid'],
                        ];
                    }

                }
                var_dump($resp['data']);
                return 'SPECIAL_GIFT: [房间]' . $resp['_roomid'] . '|不知道是什么东西';
                break;
            case 'WELCOME_ACTIVITY':
                /**
                 * 欢迎消息-活动
                 * {"cmd":"WELCOME_ACTIVITY","data":{"uid":38728279,"uname":"胖橘喵_只听歌不聊骚","type":"goodluck"},"_roomid":12722}
                 */
                return 'WELCOME_ACTIVITY: [房间]' . $resp['_roomid'] . '|' . $resp['data']['uname'];
                break;
            case 'GUARD_BUY':
                /**
                 * 舰队购买
                 * {"cmd":"GUARD_BUY","data":{"uid":43510479,"username":"416の老木鱼","guard_level":3,"num":1},"roomid":"24308","_roomid":24308}
                 */
                return 'GUARD_BUY: [房间]' . $resp['_roomid'] . '|' . $resp['data']['username'] . '购买舰队';
                break;
            case 'RAFFLE_START':
                /**
                 * 抽奖开始
                 * {"cmd":"RAFFLE_START","roomid":11365,"data":{"raffleId":5082,"type":"newspring","from":"LexBurner","time":60},"_roomid":11365}
                 */
                return 'RAFFLE_START: [房间]' . $resp['_roomid'] . '|' . $resp['data']['type'] . 'From' . $resp['data']['from'];
                break;
            case 'RAFFLE_END':
                /**
                 * 抽奖结束
                 * {"cmd":"RAFFLE_END","roomid":2785651,"data":{"raffleId":5081,"type":"newspring","from":"秃物祝大家新年快乐","fromFace":"http://i1.hdslb.com/bfs/face/34de240643bc2e5078e9aff222ff1601e6c9d31d.jpg","win":{"uname":"明太太太太太太太太太","face":"http://i1.hdslb.com/bfs/face/34de240643bc2e5078e9aff222ff1601e6c9d31d.jpg","giftId":"stuff-1","giftName":"经验原石","giftNum":10}},"_roomid":2785651}
                 */
                return 'TV_START: [房间]' . $resp['_roomid'] . '|' . $resp['data']['msg']['msg'];
                break;
            case 'TV_START':
                /**
                 * 小电视抽奖开始
                 * {"cmd":"TV_START","data":{"id":"36682","dtime":180,"msg":{"cmd":"SYS_MSG","msg":"【红色海蜗牛】:?在直播间:?【510】:?赠送 小电视一个，请前往抽奖","msg_text":"【红色海蜗牛】:?在直播间:?【510】:?赠送 小电视一个，请前往抽奖","rep":1,"styleType":2,"url":"http://live.bilibili.com/510","roomid":510,"real_roomid":80397,"rnd":1517306497,"tv_id":"36682"},"raffleId":36682,"type":"small_tv","from":"红色海蜗牛","time":180},"_roomid":80397}
                 */
                return 'TV_START: [房间]' . $resp['_roomid'] . '|' . $resp['data']['msg']['msg'];
                break;
            case 'TV_END':
                /**
                 * 小电视抽奖结束
                 * {"cmd":"TV_END","data":{"id":"36682","uname":"梦醒二生梦","sname":"红色海蜗牛","giftName":"10W银瓜子","mobileTips":"恭喜 梦醒二生梦 获得10W银瓜子","raffleId":"36682","type":"small_tv","from":"红色海蜗牛","fromFace":"http://i2.hdslb.com/bfs/face/0125426600e2d414f925ed0ed0ac011e42b7c35a.gif","win":{"uname":"梦醒二生梦","face":"http://i0.hdslb.com/bfs/face/ff8d14a116dcc59b3de30f3ac821f683b85e7150.jpg","giftName":"银瓜子","giftId":"silver","giftNum":100000}},"_roomid":80397}
                 */
                return 'TV_END: [房间]' . $resp['_roomid'] . '|' . $resp['data']['mobileTips'];
                break;
            case 'EVENT_CMD':
                /**
                 * 活动相关
                 * {"roomid":11365,"cmd":"EVENT_CMD","data":{"event_type":"newspring-5082","event_img":"http://s1.hdslb.com/bfs/static/blive/live-assets/mobile/activity/newspring_2018/raffle.png"},"_roomid":11365}
                 */
                return 'EVENT_CMD: [房间]' . $resp['_roomid'] . '|' . $resp['data']['event_type'];
                break;
            case 'ROOM_SILENT_ON':
                /**
                 * 房间开启禁言
                 * {"cmd":"ROOM_SILENT_ON","data":{"type":"level","level":1,"second":1517318804},"roomid":544893,"_roomid":544893}
                 */
                return 'ROOM_SILENT_ON: [房间]' . $resp['_roomid'] . '|开启禁言';
                break;
            case 'ROOM_SILENT_OFF':
                /**
                 * 房间禁言结束
                 * {"cmd":"ROOM_SILENT_OFF","data":[],"roomid":"101526","_roomid":101526}
                 */
                return 'ROOM_SILENT_OFF: [房间]' . $resp['_roomid'] . '|禁言结束';
                break;
            case 'ROOM_SHIELD':
                /**
                 * 房间屏蔽
                 * {"cmd":"ROOM_SHIELD","type":0,"user":"","keyword":"","roomid":939654,"_roomid":939654}
                 */
                return 'ROOM_SHIELD: [房间]' . $resp['_roomid'] . '|屏蔽';
                break;
            case 'ROOM_BLOCK_MSG':
                /**
                 * 房间封禁消息
                 * {"cmd":"ROOM_BLOCK_MSG","uid":"12482716","uname":"筱小公主","roomid":5501645,"_roomid":5501645}
                 */
                return 'ROOM_BLOCK_MSG: [房间]' . $resp['_roomid'] . '|封禁:' . $resp['uname'];
                break;
            case 'ROOM_ADMINS':
                /**
                 * 管理员变更
                 * {"cmd":"ROOM_ADMINS","uids":[37690892,22741742,21861760,35306422,40186466,27138800],"roomid":5667325,"_roomid":5667325}
                 */
                return 'ROOM_ADMINS: [房间]' . $resp['_roomid'] . '|管理员变更';
                break;
            case 'CHANGE_ROOM_INFO':
                /**
                 * 房间设置变更
                 * {"cmd":"CHANGE_ROOM_INFO","background":"http://i0.hdslb.com/bfs/live/6411059a373a594e648b26d9714d7eab4ee556ed.jpg","_roomid":24308}
                 */
                return 'CHANGE_ROOM_INFO: [房间]' . $resp['_roomid'] . '|设置变更';
                break;
            case 'WISH_BOTTLE':
                /**
                 * 许愿瓶
                 * {"cmd":"WISH_BOTTLE","data":{"action":"update","id":6301,"wish":{"id":6301,"uid":610390,"type":1,"type_id":109,"wish_limit":99999,"wish_progress":39370,"status":1,"content":"灯笼挂着好看","ctime":"2018-01-21 13:20:12","count_map":[1,20,225]}},"_roomid":14893}
                 */
                return 'WISH_BOTTLE: [房间]' . $resp['_roomid'] . '|' . $resp['data']['id'];
                break;
            case 'CUT_OFF':
                /**
                 * 直播强制切断
                 * {"cmd":"CUT_OFF","msg":"违反直播规范","roomid":945626,"_roomid":945626}
                 */
                return 'CUT_OFF: [房间]' . $resp['roomid'] . '|' . $resp['msg'];
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
}
