<?php


/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.2
 *  License: The MIT License
 *  Updated: 20180425 18:47:50
 */

namespace lkeme\BiliHelper;

//autoload
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use lkeme\BiliHelper\Daily;
use lkeme\BiliHelper\GiftSend;
use lkeme\BiliHelper\Heart;
use lkeme\BiliHelper\Login;
use lkeme\BiliHelper\Silver;
use lkeme\BiliHelper\Task;
use lkeme\BiliHelper\Silver2Coin;
use lkeme\BiliHelper\GiftHeart;
use lkeme\BiliHelper\MaterialObject;
use lkeme\BiliHelper\GroupSignIn;
use lkeme\BiliHelper\Live;
use lkeme\BiliHelper\Winning;
use lkeme\BiliHelper\Socket;


set_time_limit(0);
header("Content-Type:text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');

class Index
{
    public static $conf_file = null;
    public static $dotenv = null;

    // RUN
    public static function run($conf_file)
    {
        self::$conf_file = $conf_file;
        self::loadConfigFile();
        while (true) {
            if (!Login::check()) {
                self::$dotenv->overload();
            }
            Daily::run();
            GiftSend::run();
            Heart::run();
            Silver::run();
            Task::run();
            Silver2Coin::run();
            GroupSignIn::run();
            Live::run();
            GiftHeart::run();
            Winning::run();
            MaterialObject::run();
            Socket::run();
            Danmu::run();
            // sleep(1);
            usleep(0.5 * 1000000);
        }
    }

    protected static function loadConfigFile()
    {
        $file_path = __DIR__ . '/conf/' . self::$conf_file;

        if (is_file($file_path) && self::$conf_file != 'user.conf') {
            $load_files = [
                self::$conf_file,
            ];
        } else {
            $default_file_path = __DIR__ . '/conf/user.conf';
            if (!is_file($default_file_path)) {
                exit('默认加载配置文件不存在,请按照文档添加配置文件!');
            }

            $load_files = [
                'user.conf',
            ];
        }
        foreach ($load_files as $load_file) {
            self::$dotenv = new Dotenv(__DIR__ . '/conf', $load_file);
            self::$dotenv->load();
        }

        // load ACCESS_KEY
        Login::run();
        self::$dotenv->overload();
    }

}

// LOAD
$conf_file = isset($argv[1]) ? $argv[1] : 'user.conf';
// RUN
Index::run($conf_file);
