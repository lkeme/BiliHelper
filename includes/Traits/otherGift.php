<?php

trait otherGift
{
    //硬币兑换瓜子
    public function silver2coin()
    {
        if (time() < $this->lock['silver2coin']) {
            return true;
        }
        //TODO 客户端 网页端都可以领一次 暂时只做了客户端的
        //判断accessToken 可用
        if (!$this->authInfo()) {
            if (!$this->refreshToken())
                return false;
        }
        $url = 'https://api.live.bilibili.com/AppExchange/silver2coin?';
        $data = [
            'access_key' => $this->_accessToken,
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '5210000',
            'device' => 'android',
            'mobi_app' => 'android',
            'platform' => 'android',
            'ts' => time()
        ];
        $data['sign'] = $this->createSign($data);
        $url .= http_build_query($data);
        $raw = $this->curl($url, null, true, null, null, false);
        $raw = json_decode($raw, true);

        if ($raw['code'] == '0' && $raw['msg'] == '兑换成功') {
            $this->lock['silver2coin'] += 24 * 60 * 60;
            $this->log('硬币兑换: ' . $raw['msg'], 'blue', 'COIN');
            return true;
        } elseif ($raw['code'] == '403') {
            $this->lock['silver2coin'] += 24 * 60 * 60;
            $this->log('硬币兑换: ' . $raw['msg'], 'blue', 'COIN');
            return true;
        } else {
            //6小时重试
            $this->lock['silver2coin'] += 6 * 60 * 60;
            $this->log('硬币兑换: 兑换失败', 'red', 'COIN');
            return false;
        }
    }

    //領取每日任務獎勵
    public function dailyTask()
    {
        if (time() < $this->lock['dailyTask']) {
            return true;
        }
        //https://api.live.bilibili.com/i/api/taskInfo
        $url = $this->prefix . 'activity/v1/task/user_tasks';
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);
        if (empty($de_raw['data'])) {
            return true;
        }
        $url = $this->prefix . 'activity/v1/task/receive_award?';
        $flag = 0;
        foreach ($de_raw['data'] as $tasks) {
            $data = null;
            $data = [
                'task_id' => $tasks['task_id'],
            ];
            $newurl = $url . http_build_query($data);
            $raw = $this->curl($newurl);
            $de_raw = json_decode($raw, true);
            if ($de_raw['msg'] != '参数错误') {
                $flag += 1;
                $this->log('每日任務: [' . $tasks['task_id'] . ']' . $de_raw['msg'], 'blue', 'DAILY');
            }
        }
        //TODO 暂时沒有判斷
        $this->lock['dailyTask'] += 24 * 60 * 60;
        if (!$flag) {
            $this->log('每日任務: 没有需要完成任务!', 'red', 'DAILY');
            return true;
        }
        $this->log('每日任務: 完成!', 'blue', 'DAILY');
        return true;
    }

    //每日背包奖励
    public function dailyBag()
    {
        if (time() < $this->lock['dailyBag']) {
            return true;
        }
        $url = $this->prefix . 'gift/v2/live/receive_daily_bag';
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        //TODO 沒有判斷
        if (empty($raw['data']['bag_list'])) {
            $this->lock['dailyBag'] += 24 * 60 * 60;
            $this->log('每日背包: 完成!', 'blue', 'DAILY');
            return true;
        }
        $this->log('每日背包: ' . $raw['data']['bag_list'][0]['bag_name'] . '完成!', 'blue', 'DAILY');
        $this->lock['dailyBag'] += 24 * 60 * 60;
        return true;
    }
}
