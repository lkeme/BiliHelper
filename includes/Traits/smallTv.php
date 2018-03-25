<?php

trait smallTv
{
    //检查小电视
    public $_smallTvCheckApi = 'http://api.live.bilibili.com/gift/v2/smalltv/check?roomid=';
    //加入小电视
    public $_smallTvJoinApi = 'http://api.live.bilibili.com/gift/v2/smalltv/join?roomid=';
    //小电视抽奖反馈
    public $_smallTvFbApi = 'http://api.live.bilibili.com/gift/v2/smalltv/notice?';
    //roomid=545498&raffleId=39342

    //数组 保存小电视抽奖信息
    public $_smallTvLdList = [];
    //数组 保存小电视信息
    public $_smallTvList = [];

    public function smallTvStart($data)
    {
        $this->log("SmallTv:" . $data['msg'], 'blue', 'SOCKET');
        $checkdata = $this->tcraCheck($data['real_roomid']);
        $this->log("SmallTv: 检查状态", 'blue', 'SOCKET');
        switch ($checkdata['code']) {
            case '-1':
                $this->log("SmallTv:" . $checkdata['msg'], 'red', 'SOCKET');
                break;
            case '2':
                $this->log("SmallTv:" . $checkdata['msg'], 'red', 'SOCKET');
                break;
            case '0':
                if (is_array($checkdata['msg'])) {
                    foreach ($checkdata['msg'] as $value) {
                        $this->log("SmallTv: 编号-" . $value, 'cyan', 'SOCKET');
                        $filename = $this->_userDataInfo['name'] . '-smallTvRecord.txt';
                        $temp_data = date("Y-m-d H:i:s") . '|' . 'RoomId:' . $data["real_roomid"] . '|RaffleId:' . $value;
                        $this->writeFileTo('./record/', $filename, $temp_data);
                        //加入查询数组
                        $raffleid = explode("|", $value);
                        $this->_smallTvLdList[] = [
                            'roomid' => $data["real_roomid"],
                            'raffleId' => $raffleid[0],
                        ];
                        //TODO 详细写入信息没做
                    }
                } else {
                    $this->log("SmallTv: " . $checkdata['msg'], 'red', 'SOCKET');
                }
                break;
            default:
                var_dump($checkdata['raw']);
                $this->log('SmallTv: 关于小电视的未知状态', 'red', 'SOCKET');
                break;
        }
    }

    //小电视抽奖查询
    public function smallTvWin()
    {
        if (time() < $this->lock['smallTvWin']) {
            return true;
        }
        if (!empty($this->_smallTvLdList)) {
            $this->lock['smallTvWin'] = time() + 50;
            $url = $this->_smallTvFbApi . 'roomid=' . $this->_smallTvLdList[0]['roomid'] . '&raffleId=' . $this->_smallTvLdList[0]['raffleId'];
            $raw = $this->curl($url);
            $raw = json_decode($raw, true);
            if ($raw['data']['status'] == '3') {
                $this->log("SmallTv: " . $this->_smallTvLdList[0]['raffleId'] . $raw['msg'], 'green', 'SOCKET');
                return true;
            } elseif ($raw['data']['status'] == '2') {
                $this->log("SmallTv: " . $this->_smallTvLdList[0]['raffleId'] . '获得' . $raw['data']['gift_num'] . $raw['data']['gift_name'], 'yellow', 'SOCKET');

                $filename = $this->_userDataInfo['name'] . '-smallTvFb.txt';
                $temp_data = "SmallTv: " . $this->_smallTvLdList[0]['roomid'] . '|' . $this->_smallTvLdList[0]['raffleId'] . '获得' . $raw['data']['gift_num'] . $raw['data']['gift_name'];
                $this->writeFileTo('./record/', $filename, $temp_data);

                unset($this->_smallTvLdList[0]);
                $this->_smallTvLdList = array_values($this->_smallTvLdList);
                return true;
            } else {
                return true;
            }
        }
        return true;

    }

    //检查
    public function tcraCheck($roomid)
    {
        //TODO
        $roomRealid = $roomid;
        //$roomRealid = $this->getRealRoomID($roomid);

        $url = $this->_smallTvCheckApi . $roomRealid;
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);

        if (array_key_exists('status', $raw['data'])) {
            switch ($raw['data']['status']) {
                case '-1':
                    $data = [
                        'code' => $raw['data']['status'],
                        'msg' => $raw['msg'],
                    ];
                    return $data;
                    break;
                case '2':
                    $data = [
                        'code' => '2',
                        'msg' => '该抽奖已经抽取过',
                    ];
                    return $data;
                    break;
                default:
                    $data = [
                        'code' => $raw['data']['status'],
                        'raw' => $raw,
                    ];
                    return $data;
                    break;
            }
        } else {
            $data = [
                //'code' => $raw['data']['status'],
                'code' => 0,
                'msg' => [],
            ];
            for ($i = 0; $i < count($raw['data']); $i++) {
                //随机延时抽奖
                if ($i < 2) {
                    $this->randFloat();
                }
                //抽奖前访问一次直播间
                if ($i < 1) {
                    $this->goToRoom($roomid);
                }
                $raffleId = $raw['data'][$i]['raffleId'];
                $data['msg'][$i] = $this->joinActivity($roomRealid, $raffleId);
            }
            return $data;
        }
    }

    //加入
    public function joinActivity($roomRealid, $raffleId)
    {
        if (in_array($raffleId, $this->_smallTvList)) {
            if (count($this->_smallTvList) > 100) {
                $this->_smallTvList = null;
            }
            return $raffleId . '|重复抽奖!';
        } else {
            $this->_smallTvList[] = $raffleId;
        }
        $url = $this->_smallTvJoinApi . $roomRealid . '&raffleId=' . $raffleId;
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        //打印加入信息
        var_dump($raw);
        if ($raw['code'] == 0) {
            return $raffleId . '|成功，注意查看中奖信息';
        } elseif ($raw['message'] == '抽奖已失效！') {
            return $raffleId . '|失效';
        } else {
            return $raffleId . '|失败';
        }
    }

}
