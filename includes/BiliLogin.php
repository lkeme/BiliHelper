<?php
/**
 *  Website: https://mudew.com/
 *  Author: Mudew
 *  Version: 0.0.1
 */
require 'Ocr.php';

class BiliLogin
{
    // UA
    private $_userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
    //prefix
    private $_baseUrl = 'http://passport.bilibili.com/';
    //APP_KEY
    private $_appKey = '1d8b6e7d45233436';
    //APP_SECRET
    private $_appSecret = '560c52ccd288fed045859ed18bffd973';
    //DEBUG
    private $_deBug = false;
    private $_keyHash = '';
    // 调试信息上色
    public $color = true;
    public $_flag = 0;

    public function __construct($account)
    {
        date_default_timezone_set('Asia/Shanghai');
        $this->_user = $account['username'];
        $this->_pass = $account['password'];

    }

    public function start()
    {
        $this->log('加载账号密码', 'lightgray', 'BiliLogin');

        if (empty($this->_user) || empty($this->_pass)) {
            $this->log('配置为空,请检查配置', 'red', 'BiliLogin');
            die;
        }
        $this->log('加载成功,获取加密信息', 'green', 'BiliLogin');
        $this->_keyHash = $this->getKey();

        $pass = $this->_keyHash['hash'] . $this->_pass;
        $newpass = $this->rsaEncrypt($pass);

        /**
         *  OldApi : 'api/oauth2/login'
         *  更换接口
         */
        $url = $this->_baseUrl . 'api/v2/oauth2/login';
        $data = [
            'appkey' => $this->_appKey,
            'username' => $this->_user,
            'password' => $newpass,
        ];
        $data['sign'] = $this->createSign($data);

        $res = $this->curl($url, $data);
        $loginInfo = json_decode($res, true);

        if (array_key_exists('message', $loginInfo)) {
            $this->log($loginInfo['message'], 'red', 'BiliLogin');

            if ($loginInfo['code'] == -105) {
                /**
                 *  TODO 验证码登陆问题
                 *  $loginInfo['message'] = 'CAPTCHA is not match'
                 */
                unset($loginInfo);
                $loginInfo = $this->captchaLogin($url, $data);
            }
        }

        if ($loginInfo['code'] != 0) {
            $this->log($loginInfo['message'], 'red', 'BiliLogin');
        }

        $this->log('获取Cookie成功', 'green', 'BiliLogin');
        $cookie_file = $this->saveCookie($loginInfo);
        /**
         * return
         * path $cookie_file
         * string $access_token
         * string $refresh_token
         */
        return [
            'cookie' => $cookie_file,
            'access_token' => $loginInfo['data']['token_info']['access_token'],
            'refresh_token' => $loginInfo['data']['token_info']['refresh_token'],
        ];
    }

    //保存cookie
    public function saveCookie($login_nfo)
    {
        //临时保存cookie
        $temp_cookie = '';
        $cookies = $login_nfo['data']['cookie_info']['cookies'];
        foreach ($cookies as $cookie) {
            $temp_cookie .= $cookie['name'] . '=' . $cookie['value'] . ';';
        }
        $filename = $this->getUserInfo($temp_cookie) . '.cookies';
        //返回 用户名.cookies 路径
        $cookie_file = './user/' . $filename;
        if (is_file($cookie_file)) {
            unlink($cookie_file);
        }
        $this->writeFileTo('./user/', $filename, $temp_cookie);

        return $cookie_file;
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

    //查用户名
    public function getUserInfo($cookie)
    {
        $url = 'http://api.live.bilibili.com/User/getUserInfo?ts=' . time();
        $raw = $this->curl($url, null, false, $cookie);
        $de_raw = json_decode($raw, true);
        //返回用户名
        return $de_raw['data']['uname'];
    }

    //验证码登陆
    public function captchaLogin($url, $data)
    {
        $this->_flag += 1;
        if (array_key_exists('sign', $data)) {
            unset($data['sign']);
        }
        $captcha_raw = $this->saveCaptcha();
        $ocr_captcha_url = "http://101.236.6.31:8080/code";
        $ocr_captcha_raw = base64_encode($captcha_raw['captcha']);
        $ocr_data = [
            'image' => $ocr_captcha_raw,
        ];
        $ocr_raw = $this->curl($ocr_captcha_url, $ocr_data);

        $this->log('验证码识别: ' . $ocr_raw, 'green', 'BiliLogin');

        $data['captcha'] = $ocr_raw;
        ksort($data);
        $data['sign'] = $this->createSign($data);
        $cookie = $this->trimAll($captcha_raw['cookie']);
        $raw = $this->curl($url, $data, false, $cookie);

        $loginInfo = json_decode($raw, true);

        if ($loginInfo['code'] == -105) {
            $this->log('验证码识别: 错误，重试!', 'red', 'BiliLogin');
            exit();
        }
        $this->log('验证码识别: 成功,开始登陆!', 'green', 'BiliLogin');

        return $loginInfo;
    }

    public function saveCaptcha()
    {
        $max = 6;
        $url = $this->_baseUrl . 'captcha';
        $cookie = 'sid=' . $this->getRandCode($max);
        $res = $this->curl($url, null, false, $cookie);

        $this->log('验证码识别: 生成验证码中...', 'green', 'BiliLogin');

        return [
            'code' => '200',
            'captcha' => $res,
            'cookie' => $cookie,
        ];
    }

    private function getCookie($loginInfo)
    {
        $data = [
            'actionKey' => 'appkey',
            'appkey' => $this->_appKey,
            'build' => '414000',
            'platform' => 'android',
            'ts' => time(),
        ];

        $data['access_key'] = $loginInfo['data']['token_info']['access_token'];
        ksort($data);
        $data['sign'] = $this->createSign($data);
        $url = 'http://passport.bilibili.com/api/login/sso?' . http_build_query($data);
        $res = $this->curl($url, null, true);
        preg_match_all('/Set-Cookie: (.*);/iU', $res, $cookie);
        if (empty($cookie)) {
            $this->log('Cookie获取失败', 'red', 'BiliLogin');
        }
        return $cookie;

    }

    private function createSign($data)
    {
        ksort($data);
        $url_build = http_build_query($data);
        return md5(http_build_query($data) . $this->_appSecret);
    }

    private function getRandCode($len = 6)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string = time();
        for (; $len >= 1; $len--) {
            $position = rand() % strlen($chars);
            $position2 = rand() % strlen($string);
            $string = substr_replace($string, substr($chars, $position, 1), $position2, 0);
        }
        return $string;
    }

    private function getKey()
    {
        $data = [
            'appkey' => $this->_appKey,
        ];
        $url = $this->_baseUrl . 'api/oauth2/getKey';

        $data['sign'] = $this->createSign($data);
        $res = $this->curl($url, $data);

        $tmp = json_decode($res, true);
        $public_key = str_replace('\n', '', $tmp['data']['key']);
        //Public key & Hash
        if (!array_key_exists('data', $tmp)) {
            $this->log('加密信息获取失败', 'red', 'BiliLogin');
            die;
        }
        return [
            'key' => $public_key,
            'hash' => $tmp['data']['hash'],
        ];
    }

    //删除cookie的空格和回车
    public function trimAll($str)
    {
        $rule = array("\r\n", " ", "　", "\t", "\n", "\r");
        return str_replace($rule, '', $str);
    }

    //rsa加密
    private function rsaEncrypt($data)
    {
        $public_key = openssl_pkey_get_public($this->_keyHash['key']);
        openssl_public_encrypt($data, $newpass, $public_key);
        return base64_encode($newpass);
    }

    private function log($message, $color = 'default', $type = '')
    {
        $colors = array(
            'none' => "",
            'black' => "\033[30m%s\033[0m",
            'red' => "\033[31m%s\033[0m",
            'green' => "\033[32m%s\033[0m",
            'yellow' => "\033[33m%s\033[0m",
            'blue' => "\033[34m%s\033[0m",
            'magenta' => "\033[35m%s\033[0m",
            'cyan' => "\033[36m%s\033[0m",
            'lightgray' => "\033[37m%s\033[0m",
            'darkgray' => "\033[38m%s\033[0m",
            'default' => "\033[39m%s\033[0m",
            'bg_red' => "\033[41m%s\033[0m",
        );
        $this->msg = $message;
        $date = date('[Y-m-d H:i:s] ');
        if (!empty($type)) {
            $type = "[$type] ";
        }

        if (!$this->color) {
            $color = 'none';
        }

        echo sprintf($colors[$color], $date . $type . $message) . PHP_EOL;
    }

    private function curl($url, $data = null, $header = false, $cookie = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, $header);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_userAgent);
        if ($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        if ($this->_deBug) {
            curl_setopt($curl, CURLOPT_PROXY, "127.0.0.1"); //代理服务器地址
            curl_setopt($curl, CURLOPT_PROXYPORT, "8888"); //代理服务器端口
        }

        if (!empty($data)) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

}
