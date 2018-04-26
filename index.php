<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.25 (0.7.3)
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

//autoload
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use lkeme\BiliHelper\Curl;
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
use lkeme\BiliHelper\Socket;
use lkeme\BiliHelper\Live;


// timeout
set_time_limit(0);
// header UTF-8
header("Content-Type:text/html; charset=utf-8");
// timezone
date_default_timezone_set('Asia/Shanghai');

// load config
$conf_file = isset($argv[1]) ? $argv[1] : 'user.conf';
$dotenv = loadConfigFile($conf_file);

// run
while (true) {
    if (!Login::check()) {
        $dotenv->overload();
    }
    Daily::run();
    GiftSend::run();
    Heart::run();
    Silver::run();
    Task::run();
    Silver2Coin::run();
    GiftHeart::run();
    MaterialObject::run();
    GroupSignIn::run();
    Socket::run();

    sleep(0.5);
}

function loadConfigFile($conf_file)
{
    $file_path = __DIR__ . '/conf/' . $conf_file;

    if (is_file($file_path) && $conf_file != 'user.conf') {
        $load_files = [
            $conf_file,
            'bili.conf',
        ];
    } else {
        $load_files = [
            'bili.conf',
            'user.conf',
        ];
    }
    foreach ($load_files as $load_file) {
        $dotenv = new Dotenv(__DIR__ . '/conf', $load_file);
        $dotenv->load();
    }

    // load ACCESS_KEY
    Login::run($conf_file);
    $dotenv->overload();

    return $dotenv;
}