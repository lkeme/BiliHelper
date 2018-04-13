<?php

trait noticeManager
{
    public function infoSendManager($type, $result = '')
    {
        $nowtime = date('Y-m-d H:i:s');

        switch ($type) {
            case 'smallTv':
                $info = [
                    'title' => '小电视中奖结果',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 在小电视抽奖中获得: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'storm':
                $info = [
                    'title' => '节奏风暴中奖结果',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 在节奏风暴抽奖中: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'active':
                $info = [
                    'title' => '活动中奖结果',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 在活动抽奖中获得: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'cookieRefresh':
                $info = [
                    'title' => 'Cookie刷新',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 刷新Cookie: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'loginInit':
                break;

            case 'todaySign':
                $info = [
                    'title' => '每日签到',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 签到: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'winIng':
                $info = [
                    'title' => '实物中奖',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 实物中奖: ' . $result,
                ];
                $this->scSend($info);
                break;
            case 'banned':
                $info = [
                    'title' => '账号封禁',
                    'content' => '[' . $nowtime . ']' . ' 用户: ' . $this->_userDataInfo['name'] . ' 账号被封禁: 程序开始睡眠,凌晨自动唤醒,距离唤醒还有' . $result . '小时',
                ];
                $this->scSend($info);
                break;

            default:
                break;

        }
        return true;
    }

    //发送信息
    public function scSend($info)
    {
        if (!$this->_scKey) {
            return true;
        }
        $postdata = http_build_query(
            [
                'text' => $info['title'],
                'desp' => $info['content']
            ]
        );

        $opts = ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            ]
        ];
        $context = stream_context_create($opts);
        file_get_contents('https://sc.ftqq.com/' . $this->_scKey . '.send', false, $context);
        return true;
        //return $result = file_get_contents('https://sc.ftqq.com/' . $this->_sckey . '.send', false, $context);
    }
}