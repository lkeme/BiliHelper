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

    //TODO
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
        $res = $this->curl($url);
        preg_match_all('/Set-Cookie: (.*);/iU', $res, $cookie);
        if (empty($cookie)) {
            $this->log('Cookie获取失败', 'red', 'BiliLogin');
        }
        return $cookie;
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
