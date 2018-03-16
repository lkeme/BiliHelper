<?php

trait socketHelper
{
    //获取房间真实id
    public $_roomTureIdApi = 'https://api.live.bilibili.com/room/v1/Room/room_init?id=';
    //获取弹幕服务器
    public $_roomServerApi = 'https://api.live.bilibili.com/api/player?id=cid:';
    //获取第一个直播间
    public $_getUserRecommend = 'http://api.live.bilibili.com/room/v1/room/get_user_recommend?page=1';
    //socket数据包
    public $_actionEntry = 7;
    public $_actionHeartBeat = 2;
    public $_socket = '';
    public $_uid = 18466419;
    public $_roomRealId = '';

    public function socketHelperStart()
    {
        socketRestart:

        //$data1 = '进入socket时内存:' . memory_get_peak_usage() . PHP_EOL;
        //file_put_contents('./tmp/memory.log', $data1, FILE_APPEND);

        //保存socket到全局
        if (!$this->_socket) {
            $this->log("查找弹幕服务器中", 'green', 'SOCKET');

            //检查状态，返回真实roomid
            $this->_roomRealId = $this->getUserRecommend();
            $this->_roomRealId = $this->_roomRealId ?: $this->liveRoomStatus($this->_defaultRoomId);
            //$roomRealId = $this->getRealRoomID($roomId);
            $serverInfo = $this->getServer($this->_roomRealId);

            $this->log("连接弹幕服务器中", 'green', 'SOCKET');

            $this->_socket = $this->connectServer($serverInfo['ip'], $serverInfo['port'], $this->_roomRealId);
            $this->log("连接" . $this->_roomRealId . "弹幕服务器成功", 'green', 'SOCKET');
        }

        //发送socket心跳包 30s一次 误差5s
        $this->sendHeartBeatPkg($this->_socket);

        //接收socket返回的数据
        $resp = $this->decodeMessage($this->_socket);

        //$data1 = '读取socket返回时内存:' . memory_get_peak_usage() . PHP_EOL;
        //file_put_contents('./tmp/memory.log', $data1, FILE_APPEND);

        //判断是否需要重连
        if (!$resp) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            if ($errormsg) {
                socket_clear_error($this->_socket);
                socket_close($this->_socket);

                $this->_socket = null;
                unset($this->_socket);
                $resp = null;
                unset($resp);
                $this->_socket = null;

                $this->log("读取推送流错误,5秒后尝试重连...", 'red', 'SOCKET');
                sleep(5);

                //$data1 = '重连socket时内存:' . memory_get_peak_usage() . PHP_EOL;
                //file_put_contents('./tmp/memory.log', $data1, FILE_APPEND);

                //return $this->socketHelperStart();
                //TODO 尝试用一下goto语句
                goto socketRestart;
            }
            //尝试打印错误
            print_r($errormsg);
        }
        return $resp;
    }

    //连接弹幕服务器
    public function connectServer($ip, $port, $roomID)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $ip, $port);
        $str = $this->packMsg($roomID, $this->_uid);
        socket_write($socket, $str, strlen($str));
        return $socket;
    }

    // 发送心跳包
    public function sendHeartBeatPkg($socket)
    {
        if (time() < $this->lock['sheart']) {
            return true;
        }
        $str = pack('NnnNN', 16, 16, 1, $this->_actionHeartBeat, 1);
        socket_write($socket, $str, strlen($str));
        $this->log('SocketHeart: OK!', 'magenta', '心跳');

        //周期是30s 但是socket读数据可能会超时
        //TODO
        $this->lock['sheart'] = time() + 20;
        return true;
        //TODO
    }

    //打包请求
    public function packMsg($roomID, $uid)
    {
        $data = json_encode(['roomid' => $roomID, 'uid' => $uid]);
        return pack('NnnNN', 16 + strlen($data), 16, 1, $this->_actionEntry, 1) . $data;
    }

    // 获取弹幕服务器的 ip 和端口号
    public function getServer($roomID)
    {
        $xmlResp = '<xml>' . $this->curl($this->_roomServerApi . $roomID) . '</xml>';
        $parser = xml_parser_create();

        xml_parse_into_struct($parser, $xmlResp, $resp, $index);
        $domain = $resp[$index['DM_SERVER'][0]]['value'];

        $ip = gethostbyname($domain);
        $port = $resp[$index['DM_PORT'][0]]['value'];
        return ['ip' => $ip, 'port' => $port];
    }

    // 获取直播间真实房间号
    public function getRealRoomID($shortID)
    {
        $resp = json_decode($this->curl($this->_roomTureIdApi . $shortID), true);
        if ($resp['code']) {
            exit($shortID . ' : ' . $resp['msg']);
        }

        return $resp['data']['room_id'];
    }

    //解码服务器返回的数据消息
    public function decodeMessage($socket)
    {
        $res = '';
        $tmp = '';
        while ($out = socket_read($socket, 16)) {
            $res = unpack('N', $out);
            if ($res[1] != 16) {
                break;
            }
        }
        //TODO
        //没做详细的错误判断，一律判断为断开失效
        if (isset($res[1]) && $res[1] - 16 != 0) {

            //$data1 = '读取socket时内存:' . memory_get_peak_usage() . PHP_EOL;
            //file_put_contents('./tmp/memory.log', $data1, FILE_APPEND);

            $tmp = socket_read($socket, $res[1] - 16);
            return $tmp;
        }
        return false;
    }

    //获取第二个直播间
    public function getUserRecommend()
    {
        $raw = $this->curl($this->_getUserRecommend);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code'] != '0') {
            return false;
        }
        $rand_num = rand(1, 29);
        return $de_raw['data'][$rand_num]['roomid'];
    }
}
