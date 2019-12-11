<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2019
 */

namespace lkeme\BiliHelper;

class RaffleHandler
{
    // 房间ID
    private static $room_id = 0;
    // 时间锁
    private static $lock = 0;
    // 待抽奖 WEB端
    private static $lottery_list_app = [];
    // 待抽奖 WEB端
    private static $lottery_list_web = [];
    // 待开奖
    private static $winning_list_web = [];
    // 标题
    private static $title = '';

    /**
     * @use 入口
     * @param $room_id 直播间ID
     * @param $type 抽奖类型
     * @param $title 标题
     */
    public static function run($room_id, $type, $title)
    {
        // 如果禁用功能,则跳出
        if (getenv($type) == 'false') {
            return;
        }
        // 赋值
        self::$title = $title;
        if ($room_id != self::$room_id) {
            // 钓鱼行为检测
            if (!Live::fishingDetection($room_id)) {
                Log::warning("当前直播间[{$room_id}]存在敏感行为!");
                return;
            }
            self::$room_id = $room_id;
            // 抽奖前访问直播间
            Live::goToRoom($room_id);
        }

        // APP 检查房间是否有抽奖列表,没有则跳出
        // if ($datas = self::checkApp()) {
        //     self::joinApp($datas);
        // }

        // WEB检查房间是否有抽奖列表,没有则跳出
        if ($datas = self::checkWeb()) {
            self::joinWeb($datas);
        }
        return;
    }


    /**
     * @use WEB中奖查询
     */
    public static function resultWeb()
    {
        // 时间锁
        if (self::$lock > time()) {
            return;
        }
        // 如果待查询为空 && 去重
        if (!count(self::$winning_list_web)) {
            self::$lock = time() + 40;
            return;
        } else {
            self::$winning_list_web = array_unique(self::$winning_list_web, SORT_REGULAR);
        }
        // 查询，每次查询10个
        $flag = 0;
        foreach (self::$winning_list_web as $winning_web) {
            $flag++;
            if ($flag > 40) {
                break;
            }
            // 参数
            $payload = [
                'type' => $winning_web['type'],
                'raffleId' => $winning_web['raffle_id']
            ];
            // Web V3 Notice
            $url = 'https://api.live.bilibili.com/xlive/lottery-interface/v3/smalltv/Notice';
            // 请求 && 解码
            $raw = Curl::get($url, Sign::api($payload));
            $de_raw = json_decode($raw, true);
            // 判断
            switch ($de_raw['data']['status']) {
                // case 3:
                //     break;
                case 2:
                    // 提示信息
                    $info = "网页端在直播间[{$winning_web['room_id']}]{$winning_web['title']}[{$winning_web['raffle_id']}]获得";
                    $info .= "[{$de_raw['data']['gift_name']}X{$de_raw['data']['gift_num']}]";
                    Log::notice($info);
                    // 推送活动抽奖信息
                    if ($de_raw['data']['gift_name'] != '辣条' && $de_raw['data']['gift_name'] != '') {
                        Notice::run('raffle', $info);
                    }
                    // 删除查询完成ID
                    unset(self::$winning_list_web[$flag - 1]);
                    self::$winning_list_web = array_values(self::$winning_list_web);
                    break;
                default:
                    break;
            }
        }
        self::$lock = time() + 40;
        return;
    }

    /**
     * @use WEB端检测
     * @return array|bool
     */
    private static function checkWeb()
    {
        // 未抽奖列表阀值，否则置空
        if (count(self::$lottery_list_web) > 1000) {
            self::$lottery_list_web = [];
        }
        // 参数
        $payload = [
            'roomid' => self::$room_id,
        ];
        // Web V3接口
        $url = 'https://api.live.bilibili.com/xlive/lottery-interface/v3/smalltv/Check';
        // 请求 && 解码
        $raw = Curl::get($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 计数 && 跳出
        $total = count($de_raw['data']['list']);
        if (!$total) {
            // Log::info("网页端直播间 [" . self::$room_id . "] 待抽奖列表为空，放弃本次抽奖!");
            return false;
        }
        for ($i = 0; $i < $total; $i++) {
            /**
             * raffleId    :    88995
             * title    :    C位光环抽奖
             * type    :    GIFT_30013
             */
            $data = [
                'raffle_id' => $de_raw['data']['list'][$i]['raffleId'],
                'title' => $de_raw['data']['list'][$i]['title'],
                'type' => $de_raw['data']['list'][$i]['type'],
                'wait' => $de_raw['data']['list'][$i]['time_wait'] + strtotime(date("Y-m-d H:i:s")),
                'room_id' => self::$room_id,
            ];
            // 重复抽奖检测
            if (in_array($data['raffle_id'], array_column(self::$lottery_list_web, 'raffle_id'))) {
                continue;
            }
            // 添加到待抽奖 && 临时
            array_push(self::$lottery_list_web, $data);
        }

        return true;
    }

    /**
     * @use APP端检测
     * @return array|bool
     */
    private static function checkApp()
    {
        // 未抽奖列表阀值，否则置空
        if (count(self::$lottery_list_app) > 1000) {
            self::$lottery_list_app = [];
        }
        // 参数
        $payload = [
            'roomid' => self::$room_id,
        ];
        // App旧接口
        $url = 'https://api.live.bilibili.com/activity/v1/Common/mobileRoomInfo';
        // 请求 && 解码
        $raw = Curl::get($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 计数 && 跳出
        $total = count($de_raw['data']['lotteryInfo']);
        if (!$total) {
            // Log::info("移动端直播间 [" . self::$room_id . "] 抽奖列表为空，丢弃本次抽奖!");
            return false;
        }
        // 临时数组返回
        $temp_list = [];
        for ($i = 0; $i < $total; $i++) {
            // eventType	:	GIFT-68149
            $data = [
                'raffle_id' => $de_raw['data']['lotteryInfo'][$i]['eventType'],
                'title' => self::$title,
                'room_id' => self::$room_id
            ];
            // 重复抽奖检测
            if (in_array($data['raffle_id'], array_column(self::$lottery_list_app, 'raffle_id'))) {
                continue;
            }
            // 添加到待抽奖 && 临时
            array_push(self::$lottery_list_app, $data);
            array_push($temp_list, $data);
        }

        // 判断空值 && 返回数组
        if (!count($temp_list)) {
            return false;
        }
        return $temp_list;
    }

    /**
     * @use WEB加入抽奖
     * @param array $datas
     * @return bool
     */
    private static function joinWeb()
    {
        $max_num = mt_rand(10, 20);
        if (count(self::$lottery_list_web) == 0) {
            return false;
        }
        self::$lottery_list_web = self::arrKeySort(self::$lottery_list_web, 'wait');
        for ($i = 0; $i <= $max_num; $i++) {
            $raffle = array_shift(self::$lottery_list_web);
            if (is_null($raffle)) {
                break;
            }
            if ($raffle['wait'] > strtotime(date("Y-m-d H:i:s"))) {
                array_push(self::$lottery_list_web, $raffle);
                continue;
            }
            self::lotteryWeb($raffle);
        }
        return true;
    }


    /**
     * @use APP加入抽奖
     * @param array $datas
     * @return bool
     */
    private static function joinApp(array $datas)
    {
        // 统计抽奖个数 && 判断空
        $total = count($datas);
        if (!$total) {
            return false;
        }

        foreach ($datas as $data) {
            self::lotteryApp($data);
        }
        return true;
    }

    /**
     * @use WEB抽奖模块
     * @param array $data
     */
    private static function lotteryWeb(array $data)
    {
        // 重复抽奖检测
        if (in_array($data['raffle_id'], array_column(self::$winning_list_web, 'raffle_id'))) {
            return;
        }
        $user_info = User::parseCookies();
        // 参数
        $payload = [
            'raffleId' => $data['raffle_id'],
            'roomid' => $data['room_id'],
            'type' => $data['type'],
            'csrf_token' => $user_info['token'],
            'csrf' => $user_info['token'],
            'visit_id' => null,
        ];
        // v3 api 暂做保留处理
        // $url = 'https://api.live.bilibili.com/gift/v3/smalltv/join';
        // $url = 'https://api.live.bilibili.com/xlive/lottery-interface/v5/smalltv/join';
        $url = 'https://api.live.bilibili.com/gift/v4/smalltv/getAward';
        // 请求 && 解码
        $raw = Curl::post($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 抽奖判断
        if (isset($de_raw['code']) && $de_raw['code']) {
            if ($de_raw['code'] != -405) {
                Log::warning("网页端参与{$data['title']}[{$data['raffle_id']}]抽奖，状态: {$de_raw['message']}!");
                print_r($de_raw);
            }
        } else {
            Log::notice("网页端参与了房间[{$data['room_id']}]的{$data['title']}[{$data['raffle_id']}]抽奖, 状态: " . "{$de_raw['data']['gift_name']}x{$de_raw['data']['gift_num']}");
            array_push(self::$winning_list_web, $data);
        }
        return;
    }


    /**
     * @use APP加入抽奖
     * @param array $data
     */
    private static function lotteryApp(array $data)
    {
        // 参数
        // flower_rain-
        // lover_2018
        $payload = [
            'event_type' => $data['raffle_id'],
            'room_id' => self::$room_id,
        ];
        // App 旧接口
        $url = 'https://api.live.bilibili.com/YunYing/roomEvent';
        // 请求 && 解码
        $raw = Curl::get($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 抽奖判断
        if (array_key_exists('code', $de_raw) && $de_raw['code'] != 0) {
            Log::info("移动端参与{$data['title']}[{$data['raffle_id']}]抽奖，状态: {$de_raw['message']}!");
        } elseif (array_key_exists('code', $de_raw) && $de_raw['code'] == 0) {
            Log::notice("移动端参与了房间[{$data['room_id']}]的{$data['title']}[{$data['raffle_id']}]抽奖, 状态: {$de_raw['data']['gift_desc']}!");
        } else {
            Log::error("移动端参与{$data['title']}[{$data['raffle_id']}]抽奖，状态: {$de_raw['message']}!");
            print_r($de_raw);
        }
        return;
    }

    /**
     * @use 二维数组按key排序
     * @param $arr
     * @param $key
     * @param string $type
     * @return array
     */
    private static function arrKeySort($arr, $key, $type = 'asc')
    {
        switch ($type) {
            case 'desc':
                array_multisort(array_column($arr, $key), SORT_DESC, $arr);
                return $arr;
            case 'asc':
                array_multisort(array_column($arr, $key), SORT_ASC, $arr);
                return $arr;
            default:
                return $arr;
        }
    }
}
