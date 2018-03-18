<?php

trait groupSign
{
    //加入的应援团列表
    public $_groupListApi = 'http://api.live.bilibili.com/link_group/v1/member/my_groups?';
    //应援团签到
    public $_groupSignApi = 'http://api.vc.bilibili.com/link_setting/v1/link_setting/sign_in?';

    public function groupSignStart()
    {
        if (time() < $this->lock['groupSign']) {
            return true;
        }
        $groups = $this->getGroupList();
        if (!$groups) {
            $this->log('Group: 你没有需要签到的应援团!', 'red', 'GROUP');
            $this->lock['groupSign'] = time() + 24 * 60 * 60;
            return true;
        }
        foreach ($groups as $group) {
            $temp = $this->signGroup($group);
            $this->log('Group: ' . $temp, 'blue', 'GROUP');
        }
        $this->lock['groupSign'] = time() + 24 * 60 * 60;
        return true;
    }

    //获取列表
    public function getGroupList()
    {
        $url = $this->_groupListApi . 'access_key=' . $this->_accessToken;
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);
        if (empty($de_raw['data']['list'])) {
            return false;
        }
        return $de_raw['data']['list'];
    }

    //签到列表
    public function signGroup($groupInfo)
    {
        $data = [
            'access_key' => $this->_accessToken,
            'group_id' => $groupInfo['group_id'],
            'owner_id' => $groupInfo['owner_uid'],
        ];
        $url = $this->_groupSignApi . http_build_query($data);

        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);

        if ($de_raw['code'] != '0') {
            return '在应援团{' . $groupInfo['group_name'] . '}中签到失败,原因待查';
        }
        if ($de_raw['data']['status'] == '0') {
            return '在应援团{' . $groupInfo['group_name'] . '}中签到成功,增加{' . $de_raw['data']['add_num'] . '点}亲密度';
        } else {
            return '在应援团{' . $groupInfo['group_name'] . '}中不要重复签到';
        }
    }

}