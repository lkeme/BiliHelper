<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Updated: 2018
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
        self::$room_id = $room_id;
        self::$title = $title;
        // 钓鱼行为检测
        if (!Live::fishingDetection(self::$room_id)) {
            Log::warning('当前直播间[' . self::$room_id . ']存在敏感行为!');
            return;
        }

        // APP 检查房间是否有抽奖列表,没有则跳出
        if (!($datas = self::checkApp())) {
            // TODO
            Log::info('移动端抽奖检测异常!');
            // return;
        } else {
            self::joinApp($datas);
        }

        // 抽奖前访问直播间
        Live::goToRoom(self::$room_id);

        // WEB检查房间是否有抽奖列表,没有则跳出
        if (!($datas = self::checkWeb())) {
            return;
        }
        self::joinWeb($datas);
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
            $url = 'https://api.live.bilibili.com/gift/v3/smalltv/notice';
            // 请求 && 解码
            $raw = Curl::get($url, Sign::api($payload));
            $de_raw = json_decode($raw, true);
            // 判断
            switch ($de_raw['data']['status']) {
                case 3:
                    break;
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
        $url = 'https://api.live.bilibili.com/gift/v3/smalltv/check';
        // 请求 && 解码
        $raw = Curl::get($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 计数 && 跳出
        $total = count($de_raw['data']['list']);
        if (!$total) {
            Log::info("网页端直播间 [" . self::$room_id . "] 抽奖列表为空，丢弃本次抽奖!");
            return false;
        }
        // 临时数组返回
        $temp_list = [];
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
                'room_id' => self::$room_id,
            ];
            // 重复抽奖检测
            if (in_array($data['raffle_id'], self::$lottery_list_web)) {
                continue;
            }
            // 添加到待抽奖 && 临时
            array_push(self::$lottery_list_web, $data);
            array_push($temp_list, $data);
        }
        // 判断空值 && 返回数组
        if (!count($temp_list)) {
            return false;
        }
        return $temp_list;
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
            if (in_array($data['raffle_id'], self::$lottery_list_app)) {
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
    private static function joinWeb(array $datas)
    {
        // 统计抽奖个数 && 判断空
        $total = count($datas);
        if (!$total) {
            return false;
        }
        // Web端随机延迟 TODO 暂停使用
        // Live::randFloat();

        foreach ($datas as $data) {
            self::lotteryWeb($data);
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
        // 重复判断
        foreach (self::$winning_list_web as $winning_web) {
            if ($data['raffle_id'] == $winning_web['raffle_id']) {
                return;
            }
        }
        // 参数
        $payload = [
            'raffleId' => $data['raffle_id'],
            'roomid' => self::$room_id,
        ];
        // Web V3
        $url = 'https://api.live.bilibili.com/gift/v3/smalltv/join';
        // 请求 && 解码
        $raw = Curl::get($url, Sign::api($payload));
        $de_raw = json_decode($raw, true);
        // 抽奖判断
        if (isset($de_raw['code']) && $de_raw['code']) {
            Log::warning("网页端参与{$data['title']}[{$data['raffle_id']}]抽奖加入失败，状态: {$de_raw['message']}!");
            print_r($de_raw);
        } else {
            Log::notice("网页端参与了房间[{$data['room_id']}]的{$data['title']}[{$data['raffle_id']}]抽奖, 状态: {$de_raw['msg']}!");
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
            Log::info("移动端参与{$data['title']}[{$data['raffle_id']}]抽奖加入失败，状态: {$de_raw['message']}!");
        } elseif (array_key_exists('code', $de_raw) && $de_raw['code'] == 0) {
            Log::notice("移动端参与了房间[{$data['room_id']}]的{$data['title']}[{$data['raffle_id']}]抽奖, 状态: {$de_raw['data']['gift_desc']}!");
        } else {
            Log::error("移动端参与{$data['title']}[{$data['raffle_id']}]抽奖加入失败，状态: {$de_raw['message']}!");
            print_r($de_raw);
        }
        return;
    }
}
