<?php

trait socketHelper
{
    //获取房间真实id
    public $_roomTureIdApi = 'https://api.live.bilibili.com/room/v1/Room/room_init?id=';
    //获取弹幕服务器
    public $_roomServerApi = 'https://api.live.bilibili.com/api/player?id=cid:';
    //socket数据包
    public $_actionEntry = 7;
    public $_actionHeartBeat = 2;
    public $_socket = '';
    public $_uid = 18466419;

    public function socketHelperStart()
    {
        //保存socket到全局
        if (!$this->_socket) {
            $this->log("查找弹幕服务器中", 'green', 'SOCKET');
            //检查状态，返回真实roomid
            $roomRealId = $this->liveRoomStatus($this->_defaultRoomId) ?: $this->liveCheck();
            //$roomRealId = $this->getRealRoomID($roomId);
            $serverInfo = $this->getServer($roomRealId);

            $this->log("连接弹幕服务器中", 'green', 'SOCKET');
            $socketRes = $this->connectServer($serverInfo['ip'], $serverInfo['port'], $roomRealId);

            $this->log("连接弹幕服务器成功", 'green', 'SOCKET');
            $this->_socket = $socketRes;
        } else {
            $socketRes = $this->_socket;
        }

        //发送socket心跳包 30s一次 误差5s
        $this->sendHeartBeatPkg($socketRes);

        //接收socket返回的数据
        $resp = $this->decodeMessage($socketRes);

        //判断是否需要重连
        if (!$resp) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $this->log("读取推送流错误,3秒后尝试第一次重连...", 'red', 'SOCKET');
            sleep(3);
            if ($errormsg) {
                socket_close($socketRes);
                $this->_socket = '';
                return $this->socketHelperStart();
            }
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
        $this->log("SOCKET: 发送心跳包中", 'magenta', 'SOCKET');
        //周期是30s 但是socket读数据可能会超时
        //TODO
        $this->lock['sheart'] += 20;
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
        $xmlResp = '<xml>' . file_get_contents($this->_roomServerApi . $roomID) . '</xml>';
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
        $resp = json_decode(file_get_contents($this->_roomTureIdApi . $shortID), true);
        if ($resp['code']) {
            exit($shortID . ' : ' . $resp['msg']);
        }

        return $resp['data']['room_id'];
    }

    //解码服务器返回的数据消息
    public function decodeMessage($socket)
    {
        $res = '';
        while ($out = socket_read($socket, 16)) {
            $res = unpack('N', $out);
            if ($res[1] != 16) {
                break;
            }
        }
        //TODO
        //没做详细的错误判断，一律判断为断开失效
        if (isset($res[1])) {
            return socket_read($socket, $res[1] - 16);
        } else {
            return false;
        }
    }
}
