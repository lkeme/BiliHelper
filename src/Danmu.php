<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2018
 */

namespace lkeme\BiliHelper;

class Danmu
{
    public static $lock = 0;

    public static function run()
    {
        if (self::$lock > time() || getenv('USE_DANMU') == 'false') {
            return;
        }
        $room_id = empty(getenv('DANMU_ROOMID')) ? Live::getUserRecommend() : Live::getRealRoomID(getenv('DANMU_ROOMID'));
        $msg = empty(getenv('DANMU_CONTENT')) ? self::getMsgInfo() : getenv('DANMU_CONTENT');

        $info = [
            'roomid' => $room_id,
            'content' => $msg,
        ];

        if (self::privateSendMsg($info)) {
            self::$lock = time() + 3600;
            return;
        }

        self::$lock = time() + 30;
    }

    // 获取随机弹幕
    private static function getMsgInfo()
    {
        try {
            $data = Curl::get('https://api.lwl12.com/hitokoto/v1?encode=realjso');

            if (strpos($data, '，')) {
                $newdata = explode('，', $data);
            } elseif (strpos($data, ',')) {
                $newdata = explode(',', $data);
            } elseif (strpos($data, '。')) {
                $newdata = explode('。', $data);
            } elseif (strpos($data, '!')) {
                $newdata = explode('!', $data);
            } elseif (strpos($data, '.')) {
                $newdata = explode('.', $data);
            } elseif (strpos($data, ';')) {
                $newdata = explode(';', $data);
            } else {
                $newdata = explode('——', $data);
            }
            return $newdata[0];

        } catch (\Exception $e) {
            return $e;
        }
    }

    //转换信息
    private static function convertInfo()
    {
        preg_match('/bili_jct=(.{32})/', getenv('COOKIE'), $token);
        $token = isset($token[1]) ? $token[1] : '';
        return $token;
    }

    //发送弹幕通用模块
    private static function sendMsg($info)
    {
        $raw = Curl::get('https://api.live.bilibili.com/room/v1/Room/room_init?id=' . $info['roomid']);
        $de_raw = json_decode($raw, true);

        $payload = [
            'color' => '16777215',
            'fontsize' => 25,
            'mode' => 1,
            'msg' => $info['content'],
            'rnd' => 0,
            'roomid' => $de_raw['data']['room_id'],
            'csrf' => self::convertInfo(),
            'csrf_token' => self::convertInfo(),
        ];

        return Curl::post('https://api.live.bilibili.com/msg/send', Sign::api($payload));
    }

    //使用发送弹幕模块
    private static function privateSendMsg($info)
    {
        //TODO 暂时性功能 有需求就修改
        $raw = self::sendMsg($info);
        $de_raw = json_decode($raw, true);

        if ($de_raw['code'] == 1001) {
            Log::warning($de_raw['msg']);
            return false;
        }

        if (!$de_raw['code']) {
            Log::info('弹幕发送成功!');
            return true;
        }

        Log::error("弹幕发送失败, {$de_raw['msg']}");
        return false;
    }
}