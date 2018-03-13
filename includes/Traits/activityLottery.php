<?php

trait activityLottery
{
    //roomid
    public $_checkActiveApi = 'https://api.live.bilibili.com/activity/v1/Raffle/check?roomid=';
    //roomid raffleId
    public $_joinActiveApi = 'https://api.live.bilibili.com/activity/v1/Raffle/join?';
    //roomid raffleId
    public $_noticeActiveApi = 'http://api.live.bilibili.com/activity/v1/Raffle/notice?';
    //保存活动抽奖信息
    public $_activeLotteryList = [];

    //start
    public function activeStart($data)
    {
        $this->log("ActiveLottery:" . $data['msg'], 'blue', 'SOCKET');
        $checkdata = $this->activeCheck($data['real_roomid']);
        $this->log("ActiveLottery: 检查状态", 'blue', 'SOCKET');
        switch ($checkdata['code']) {
            case '-1':
                $this->log("ActiveLottery:" . $checkdata['msg'], 'red', 'SOCKET');
                break;
            case '2':
                $this->log("ActiveLottery:" . $checkdata['msg'], 'red', 'SOCKET');
                break;
            case '0':
                if (is_array($checkdata['msg'])) {
                    foreach ($checkdata['msg'] as $value) {
                        $this->log("ActiveLottery: 编号-" . $value, 'cyan', 'SOCKET');
                        $path = './record/' . $this->_userName . '-activeLotteryRecord.txt';
                        file_put_contents($path, date("Y-m-d H:i:s") . '|' . 'RoomId:' . $data["real_roomid"] . '|RaffleId:' . $value . "\r\n", FILE_APPEND);
                        //加入查询数组
                        $raffleid = explode("|", $value);
                        $this->_activeLotteryList[] = [
                            'roomid' => $data["real_roomid"],
                            'raffleId' => $raffleid[0],
                        ];
                        //TODO 详细写入信息没做
                    }
                } else {
                    $this->log("ActiveLottery: " . $checkdata['msg'], 'red', 'SOCKET');
                }
                break;
            default:
                var_dump($checkdata['raw']);
                $this->log('ActiveLottery: 关于活动的未知状态', 'red', 'SOCKET');
                break;
        }
    }

    //中奖查询
    public function activeWin()
    {
        if (time() < $this->lock['activeWin']) {
            return true;
        }
        if (!empty($this->_activeLotteryList)) {
            $this->lock['activeWin'] += 20;
            $url = $this->_noticeActiveApi . 'roomid=' . $this->_activeLotteryList[0]['roomid'] . '&raffleId=' . $this->_activeLotteryList[0]['raffleId'];

            $raw = $this->curl($url);
            $raw = json_decode($raw, true);

            if ($raw['code'] == '-400') {
                $this->log("ActiveLottery: " . $this->_activeLotteryList[0]['raffleId'] . $raw['msg'], 'green', 'SOCKET');
                return true;

            } elseif ($raw['code'] == '0') {
                $this->log("ActiveLottery: " . $this->_activeLotteryList[0]['raffleId'] . '获得' . $raw['data']['gift_num'] . $raw['data']['gift_name'], 'yellow', 'SOCKET');
                $path = './record/' . $this->_userName . '-activeLotteryFb.txt';
                $data = "RoomId: " . $this->_activeLotteryList[0]['roomid'] . '|' . $this->_activeLotteryList[0]['raffleId'] . '获得' . $raw['data']['gift_num'] . $raw['data']['gift_name'];

                file_put_contents($path, date("Y-m-d H:i:s") . '|' . $data . "\r\n", FILE_APPEND);
                unset($this->_activeLotteryList[0]);

                $this->_activeLotteryList = array_values($this->_activeLotteryList);
                return true;

            } else {
                return true;
            }
        }
        return true;
    }

    //检查
    public function activeCheck($roomid)
    {
        $url = $this->_checkActiveApi . $roomid;
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);

        if (array_key_exists('status', $de_raw['data'])) {
            switch ($de_raw['data']['status']) {
                case '-1':
                    $data = [
                        'code' => $de_raw['data']['status'],
                        'msg' => $de_raw['msg'],
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
                        'code' => $de_raw['data']['status'],
                        'raw' => $de_raw,
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
            for ($i = 0; $i < count($de_raw['data']); $i++) {
                $raffleId = $de_raw['data'][$i]['raffleId'];
                $data['msg'][$i] = $this->activeJoin($roomid, $raffleId);
            }
            return $data;
        }
    }

    //加入
    public function activeJoin($roomid, $raffleId)
    {
        $url = $this->_joinActiveApi . 'roomid=' . $roomid . '&raffleId=' . $raffleId;
        $raw = $this->curl($url,null,true,null,$roomid);

        $de_raw = json_decode($raw, true);

        //打印加入信息
        var_dump($de_raw);
        if ($de_raw['code'] == 0) {
            return $raffleId . '|成功，注意查看中奖信息';
        } elseif ($de_raw['message'] == '抽奖已失效！') {
            return $raffleId . '|失效';
        } else {
            return $raffleId . '|失败';
        }
    }


}