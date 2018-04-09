<?php
set_time_limit(0);
header("Content-Type:text/html; charset=utf-8");

class activeSendMsg
{
    //cookie路径
    private $_cookieFilePath = '../user/';
    //用户信息组
    private $_userInfoList = [];
    //一言的api 取原数据
    private $_hitokotoApi = 'https://api.lwl12.com/hitokoto/main/get';
    //发送弹幕
    public $_liveSendMsg = 'https://api.live.bilibili.com/msg/send';
    //直播状态查询
    public $_liveStatusApi = 'http://api.live.bilibili.com/room/v1/Room/room_init?id=';
    //认证信息
    private $_cookie = '';
    private $_token = null;
    //时间锁
    private $_lock = [];
    //发弹幕的房间号
    private $_roomid = 9522051;
    //代理
    private $_deBug = false;

    public function __construct()
    {
        $this->_start = time();
    }

    //主函数
    public function start()
    {
        $this->init();
        while (1) {
            if ($this->_lock['cookie'] < time()) {
                $this->init();
            }
            foreach ($this->_userInfoList as $userInfo) {
                $this->convertInfo($userInfo);

                $msg = $this->getMsgInfo();
                $info = [
                    'roomid' => $this->_roomid,
                    'content' => $msg,
                ];
                $this->privateSendMsg($info);
                sleep(10);
            }
            sleep(10);
        }
    }

    //init
    public function init()
    {
        $this->_lock['cookie'] = $this->_start + 60 * 60;
        $this->_userInfoList = [];
        $filelist = $this->scanPathFile();
        foreach ($filelist as $file) {
            $this->_userInfoList[] = file_get_contents($file);
        }
    }

    //转换信息
    public function convertInfo($userInfo)
    {
        $this->_cookie = $userInfo;
        preg_match('/bili_jct=(.{32})/', $this->_cookie, $token);
        $this->_token = isset($token[1]) ? $token[1] : '';
        return;
    }

    //扫描文件信息
    private function scanPathFile()
    {
        $fileList = scandir($this->_cookieFilePath);

        $newlist = [];
        foreach ($fileList as $filename) {
            $file = $this->_cookieFilePath . $filename;
            if (is_file($file)) {
                $newlist[] = $file;
            }

        }
        if (empty($newlist)) {
            die('没有需要操作的用户信息!');
        }
        return $newlist;
    }

    //获取随机弹幕信息
    private function getMsgInfo()
    {
        $data = $this->curl($this->_hitokotoApi);
        if (strpos($data, '，')) {
            $newdata = explode('，', $data);
            return $newdata[0];
        } elseif (strpos($data, ',')) {
            $newdata = explode(',', $data);
            return $newdata[0];
        } elseif (strpos($data, '。')) {
            $newdata = explode('。', $data);
            return $newdata[0];
        } elseif (strpos($data, '!')) {
            $newdata = explode('!', $data);
            return $newdata[0];
        } elseif (strpos($data, '.')) {
            $newdata = explode('.', $data);
            return $newdata[0];
        } elseif (strpos($data, ';')) {
            $newdata = explode(';', $data);
            return $newdata[0];
        } else {
            $newdata = explode('——', $data);
            return $newdata[0];
        }

    }

    //发送弹幕通用模块
    private function sendMsg($info)
    {
        $url = $this->_liveStatusApi . $info['roomid'];
        $raw = $this->curl($url);
        $de_raw = json_decode($raw, true);

        $data = [
            'color' => '16777215',
            'fontsize' => 25,
            'mode' => 1,
            'msg' => $info['content'],
            'rnd' => 0,
            'roomid' => $de_raw['data']['room_id'],
            'csrf_token' => $this->_token,
        ];

        $data = http_build_query($data);
        $length = mb_strlen($data) + 2;

        $headers = array(
            'Host: api.live.bilibili.com',
            'Connection: keep-alive',
            'Content-Length: ' . $length,
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Origin: http://live.bilibili.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Referer: http://live.bilibili.com/' . $de_raw['data']['room_id'],
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.8',
            'Cookie: ' . $this->_cookie,
        );

        return $this->curl($this->_liveSendMsg, $data, $headers);
    }

    //使用发送弹幕模块
    public function privateSendMsg($info)
    {
        //TODO 暂时性功能 有需求就修改
        $raw = $this->sendMsg($info);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] == '0') {
            echo '[' . date("Y/m/d H:i:s") . '] ' . '弹幕发送成功' . PHP_EOL;
            return true;
        }
        echo '[' . date("Y/m/d H:i:s") . '] ' . '弹幕发送失败' . PHP_EOL;
        return true;
    }

    //通用curl
    public function curl($url, $data = null, $headers = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($this->_deBug) {
            curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1');
            curl_setopt($ch, CURLOPT_PROXYPORT, '8888');
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}

$api = new  activeSendMsg();
$api->start();



