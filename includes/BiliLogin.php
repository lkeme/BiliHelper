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

        $url = $this->_baseUrl . 'api/oauth2/login';
        $data = [
            'appkey' => $this->_appKey,
            'username' => $this->_user,
            'password' => $newpass,
        ];
        $data['sign'] = $this->createSign($data);

        $res = $this->curl($url, $data);
        $loginInfo = json_decode($res, true);

        if (array_key_exists('message', $loginInfo)) {
            if ($loginInfo['message'] == 'CAPTCHA is not match') {
                //code = -105
                $loginInfo = null;
                $loginInfo = $this->captchaLogin($url, $data);

                $cookies = $this->getCookie($loginInfo);
                $this->log('获取Cookie成功', 'green', 'BiliLogin');

                $tempfile = './tmp/' . $this->getRandCode();

                foreach ($cookies[1] as $cookie) {
                    file_put_contents($tempfile, $cookie . ';', FILE_APPEND);
                }
                return $tempfile;
            }
            $this->log($loginInfo['message'], 'red', 'BiliLogin');
            die;
        }

        $cookies = $this->getCookie($loginInfo);
        $this->log('获取Cookie成功', 'green', 'BiliLogin');

        $tempfile = $this->getRandCode();

        foreach ($cookies[1] as $cookie) {
            file_put_contents($tempfile, $cookie . ';', FILE_APPEND);
        }
        //返回cookie access_token refresh_token
        return [
            'cookie' => $tempfile,
            'access_token' => $loginInfo['data']['access_token'],
            'refresh_token' => $loginInfo['data']['refresh_token'],
        ];
    }

    public function captchaLogin($url, $data)
    {
        $this->_flag += 1;
        if (array_key_exists('sign', $data)) {
            unset($data['sign']);
        }
        $ocr = new Ocr();
        $tmp = $this->saveCaptcha();
        if ($tmp['code'] == '200') {
            $tmpocr = $ocr->foreUpload($tmp['captcha']);
            $tmpocr = str_replace('\n', '', $tmpocr);
            $tmpocr = json_decode($tmpocr, true);

            if ($tmpocr['success'] == 1) {
                $data['captcha'] = $tmpocr['result'][0]['content'];
                ksort($data);
                $data['sign'] = $this->createSign($data);
                $res = $this->curl($url, $data);
                $loginInfo = json_decode($res, true);
                if ($loginInfo['code'] == '-105' || $loginInfo['code'] == '-3') {
                    $this->log('验证码识别错误，第' . $this->_flag . '次', 'red', 'BiliLogin');
                    unlink('./tmp/' . $tmp['captcha']);
                    $this->captchaLogin($url, $data);
                } else {
                    $this->log('验证码识别成功，第' . $this->_flag . '次', 'green', 'BiliLogin');
                    return $loginInfo;
                }

            }
        }

    }

    public function saveCaptcha()
    {
        $max = 6;
        $url = $this->_baseUrl . 'captcha';
        $cookie = 'sid=' . $this->getRandCode($max);
        $res = $this->curl($url, null, false, $cookie);
        $captcha = './tmp/' . $this->getRandCode($max) . '.jpg';
        $flag = file_put_contents($captcha, $res);

        //if (!$flag)
        //    $this->log('生成验证码失败', 'red', 'BiliLogin');
        //TODO

        $this->log('生成验证码成功', 'green', 'BiliLogin');

        return [
            'code' => '200',
            'captcha' => $captcha,
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

        $data['access_key'] = $loginInfo['data']['access_token'];
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
