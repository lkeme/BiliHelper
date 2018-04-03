<?php

trait customConfig
{
    //自定义弹幕
    public $_privateSendMsgInfo = [
        //要发的内容
        'content' => '测试弹幕内容',
        //要发到的房间号
        'roomid' => '9522051',
        //多久发一次 ,单位 秒
        'time' => '3600',
    ];

    //有必要的自定义任务，如果为true，就是要执行，对应false的话就不执行
    public $_biliTaskskip = [
        //赠送快过期礼物任务 ，默认执行
        'giftsend' => true,
        //瓜子兑换硬币任务，默认执行
        'silver2coin' => true,
        //发送弹幕任务，如果此项为true，对应$_privateSendMsgInfo配置项必，默认不执行
        'privateSendMsg' => false,
        //应援团签到任务
        'groupSignStart' => true,
        //延迟任务 避免一定程度被ban
        'delayTasks' => true,
        //实物抽奖 实验性
        'drawLottery' => true,
    ];
}