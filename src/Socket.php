<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.2
 *  License: The MIT License
 *  Updated: 20180425 18:47:50
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;
use lkeme\BiliHelper\Live;
use lkeme\BiliHelper\DataTreating;

class Socket
{
    protected static $socket_connection = null;
    protected static $ips = [];
    protected static $socket_ip = null;
    protected static $socket_port = null;
    public static $lock = 0;

    // RUN
    public static function run()
    {
        self::start();
        $message = self::decodeMessage();
        if (!$message) {
            unset($message);
            self::resetConnection();
            return;
        }
        $data = DataTreating::socketJsonToArray($message);
        if (!$data) {
            return;
        }
        DataTreating::socketArrayToDispose($data);
        return;
    }

    // KILL
    protected static function killConnection()
    {
        socket_clear_error(self::$socket_connection);
        socket_shutdown(self::$socket_connection);
        socket_close(self::$socket_connection);
        self::$socket_connection = null;
    }

    // RECONNECT
    protected static function resetConnection()
    {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        unset($errormsg);
        unset($errorcode);
        self::killConnection();
        Log::warning('SOCKET连接断开,5秒后重新连接...');
        sleep(5);
        self::start();
        return;
    }

    // SOCKET READER
    protected static function readerSocket(int $length)
    {
        return socket_read(self::$socket_connection, $length);
    }

    // DECODE MESSAGE
    protected static function decodeMessage()
    {
        $res = '';
        $tmp = '';
        while (1) {
            while ($out = self::readerSocket(16)) {
                $res = unpack('N', $out);
                unset($out);
                if ($res[1] != 16) {
                    break;
                }
            }
            if (isset($res[1])) {
                $length = $res[1] - 16;
                if ($length > 65535) {
                    continue;
                }
                if ($length <= 0) {
                    return false;
                }
                return self::readerSocket($length);
            }
            return false;
        }
    }

    // START
    protected static function start()
    {
        if (is_null(self::$socket_connection)) {
            $room_id = empty(getenv('ROOM_ID_SOCKET')) ? Live::getUserRecommend() : Live::getRealRoomID(getenv('ROOM_ID_SOCKET'));
            $room_id = intval($room_id);
            if ($room_id) {
                self::getSocketServer($room_id);
                self::connectServer($room_id, self::$socket_ip, self::$socket_port);
            }
        }
        self::sendHeartBeatPkg();
        return;
    }

    // SEND HEART
    protected static function sendHeartBeatPkg()
    {
        if (self::$lock > time()) {
            return;
        }
        $action_heart_beat = getenv('ACTIONHEARTBEAT');
        $str = pack('NnnNN', 16, 16, 1, $action_heart_beat, 1);
        socket_write(self::$socket_connection, $str, strlen($str));
        Log::info('发送一次SOCKET心跳!');
        self::$lock = time() + 30;
        return;
    }

    // SOCKET CONNECT
    protected static function connectServer($room_id, $ip, $port)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $ip, $port);
        $str = self::packMsg($room_id);
        socket_write($socket, $str, strlen($str));
        self::$socket_connection = $socket;
        // TODO
        Log::info('连接直播间[' . $room_id . ']弹幕服务器成功!');
        return;
    }

    // PACK DATA
    protected static function packMsg($room_id)
    {
        $uid = intval(getenv('UID'));
        $action_entry = getenv('ACTIONENTRY');
        $data = json_encode(['roomid' => $room_id, 'uid' => $uid]);
        return pack('NnnNN', 16 + strlen($data), 16, 1, $action_entry, 1) . $data;
    }

    // GET SERVER
    protected static function getSocketServer(int $room_id): bool
    {
        while (1) {
            try {
                $raw = Curl::get('https://api.live.bilibili.com/api/player?id=cid:' . $room_id);
                $xml_dom = '<xml>' . $raw . '</xml>';
                $parser = xml_parser_create();
                xml_parse_into_struct($parser, $xml_dom, $resp, $index);
                $domain = $resp[$index['DM_SERVER'][0]]['value'];

                self::$socket_ip = gethostbyname($domain);
                self::$socket_port = $resp[$index['DM_PORT'][0]]['value'];

                break;
            } catch (\Exception $e) {
                Log::warning('获取SOCKET服务器出错!', $e);
                continue;
            }
        }
        return true;
    }
}