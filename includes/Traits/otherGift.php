<?php

trait otherGift
{
    //扭蛋币
    public function eggMoney()
    {
        if (time() < $this->lock['eggMoney']) {
            return true;
        }
        $url = 'http://live.bilibili.com/redLeaf/kingMoneyGift';
        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        if ($raw['code'] == '-400') {
            $this->lock['eggMoney'] += 24 * 60 * 60;
            $this->log('EggMoney:' . $raw['msg'], 'blue', '扭蛋币');
        } elseif ($raw['code'] == '0') {
            $this->lock['eggMoney'] += 24 * 60 * 60;
            $this->log('EggMoney:' . $raw['msg'], 'blue', '扭蛋币');
        } else {
            $this->log('EggMoney:' . $raw['msg'], 'red', '扭蛋币');
        }
        return true;
    }

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
        $url = 'https://api.live.bilibili.com/AppExchange/silver2coin?access_key=' . $this->_accessToken;
        $data = [
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '5210000',
            'device' => 'android',
            'mobi_app' => 'android',
            'platform' => 'android',
            'ts' => time()
        ];
        $data['sign'] = $this->createSign($data);

        $raw = $this->curl($url, $data);
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
        $url = $this->prefix . 'activity/v1/task/receive_award';
        $payload1 = ['task_id' => 'single_watch_task'];
        $this->curl($url, $payload1);
        $payload2 = ['task_id' => 'double_watch_task'];
        $this->curl($url, $payload2);
        $payload3 = ['task_id' => 'share_task'];
        $this->curl($url, $payload3);
        //TODO 沒有判斷
        $this->lock['dailyTask'] += 24 * 60 * 60;
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
        if (empty($raw['data']['bag_list'])){
            $this->lock['dailyBag'] += 24 * 60 * 60;
            $this->log('每日背包: 完成!' , 'blue', 'DAILY');
            return true;
        }
        $this->log('每日背包: '.$raw['data']['bag_list'][0]['bag_name'].'完成!' , 'blue', 'DAILY');
        $this->lock['dailyBag'] += 24 * 60 * 60;
        return true;
    }
}
