<?php

trait userHelper
{
    public $_accessToken = '';
    public $_refreshToken = '';
    //APP_KEY
    public $_appKey = '1d8b6e7d45233436';
    //APP_SECRET
    public $_appSecret = '560c52ccd288fed045859ed18bffd973';
    //prefix
    public $_baseUrl = 'http://passport.bilibili.com/';
    //查看是否实名
    public $_stormFlag = true;

    //查看是否实名
    public function realnameCheck()
    {
        $url = "http://account.bilibili.com/identify/index";
        $raw = $this->curl($url);
        $de_raw = json_decode($raw,true);
        //检查有没有名字，没有则没实名
        if (!$de_raw['data']['memberPerson']['realname']){
            $this->_stormFlag = false;
        }
        return true;
    }
    //刷新cookie
    public function getCookie()
    {
        $data = [
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '414000',
            'platform' => 'android',
            'access_key' => $this->_accessToken,
            'ts' => time(),
        ];

        ksort($data);
        $data['sign'] = $this->createSign($data);
        $url = $this->_baseUrl . 'api/login/sso?' . http_build_query($data);

        $res = $this->curl($url, null, true, null, null, false, true);
        preg_match_all('/Set-Cookie: (.*);/iU', $res, $cookies);
        if (empty($cookies)) {
            $this->log('Cookie获取失败', 'red', 'BiliLogin');
            return false;
        }
        $newcookie = '';
        foreach ($cookies[1] as $cookie) {
            $newcookie .= $cookie . ';';
        }
        //写入cookie文件
        $filename = $this->_userDataInfo['name'] . '.cookies';
        //返回 用户名.cookies 路径
        $cookiefile = './user/' . $filename;
        if (is_file($cookiefile)) {
            unlink($cookiefile);
        }
        $this->writeFileTo('./user/', $filename, $newcookie);
        return $newcookie;
    }

    //刷新信息
    public function refreshInfo()
    {
        //每20小时刷新一次
        if (time() < $this->lock['refreshCookie']) {
            return true;
        }
        //每100小时刷新一次
        if (time() > $this->lock['refreshToken']) {
            $temp = $this->refreshToken();
            //失败就跳出
            if (!$temp) return true;
            $this->lock['refreshToken'] = time() + 480 * 60 * 60;
            $this->log('Token: 刷新成功', 'green', 'BiliLogin');
        }
        $temp = $this->getCookie();
        //失败就跳出
        if (!$temp) return true;
        $this->cookie = $this->trimAll($temp);
        $this->lock['refreshCookie'] = time() + 240 * 60 * 60;
        $this->log('Cookie: 刷新成功', 'green', 'BiliLogin');

        //推送Cookie刷新成功信息
        $this->infoSendManager('cookieRefresh', '刷新成功');
        return true;
    }

    //获取登录认证信息
    public function authInfo()
    {
        $data = [
            'access_token' => $this->_accessToken,
            'appkey' => $this->_appKey,
        ];
        $data['sign'] = $this->createSign($data);

        $suffix = http_build_query($data);
        $url = $this->_baseUrl . 'api/oauth2/info?' . $suffix;

        $raw = $this->curl($url);
        $raw = json_decode($raw, true);
        if ($raw['code'] == 0 && isset($raw['data']['mid']) && !empty($raw['data']['mid'])) {
            return $raw['data'];
        }
        return false;
    }

    //刷新Token
    public function refreshToken()
    {
        $data = [
            'access_token' => $this->_accessToken,
            'appkey' => $this->_appKey,
            'refresh_token' => $this->_refreshToken,
        ];
        $data['sign'] = $this->createSign($data);

        $url = $this->_baseUrl . 'api/oauth2/refreshToken';

        $raw = $this->curl($url, $data);
        $raw = json_decode($raw, true);

        if ($raw['code'] == 0 && isset($raw['data']['mid']) && !empty($raw['data']['mid'])) {
            $this->_accessToken = $raw['data']['access_token'];
            $this->_refreshToken = $raw['data']['refresh_token'];
            return true;
        }
        return false;
    }


    public function createSign($data)
    {
        ksort($data);
        $url_build = http_build_query($data);
        return md5(http_build_query($data) . $this->_appSecret);
    }
}
