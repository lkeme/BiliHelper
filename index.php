<?php
set_time_limit(0);
header("Content-Type:text/html; charset=utf-8");
require "includes/Bilibili.php";
require "includes/BiliLogin.php";

//输入账号密码必填
$account = [
    'username' => '',
    'password' => '',
];
//判断
if ($account['username'] == '' || $account['password'] == '') {
    die("账号密码为空,检查配置,必填项!");
}
//第一次获取cookie
$login = new BiliLogin($account);
$data = $login->start();
$cookie = file_get_contents($data['cookie']);
unlink($data['cookie']);

//start
function start($account, $cookie, $data)
{
//    $data1 = '开始程序内存:' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB' . PHP_EOL;
//    file_put_contents('./tmp/memory.log', $data1, FILE_APPEND);

    $api = new Bilibili($cookie, $data);
    $api->debug = false;
    $api->color = true;
    $api->_accessToken = $data['access_token'];
    $api->_refreshToken = $data['refresh_token'];
    $api->roomid = 9522051;
    $api->callback = function () {
        //递归调用
        global $account;
        $login = new BiliLogin($account);
        $data = $login->start();
        $cookie = file_get_contents($data['cookie']);
        unlink($data['cookie']);
        call_user_func('start', $account, $cookie, $data);
    };
    $api->run();
}

call_user_func('start', $account, $cookie, $data);
