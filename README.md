# BilibiliHelper
B 站直播实用脚本


## 功能
 - 每日签到
 - 发送PC端心跳
 - 自动领限时礼物
 - 自动领宝箱（瓜子）
 - 自动清理（投喂）过期礼物
 - 抽小电视
 - 银瓜子换硬币(一次)
 - 每日领取扭蛋币
 - 发送APP端心跳
 - 完成每日任务


## TODO
 - 节奏风暴
 - 定时刷新cookie
 - 

## 更新日志
 - 0.6.0: 更新大部分接口，新增参数自动检测
 - 0.7.0: 添加账号密码登录, 验证码识别(识别率低)
 - 0.8.0: 添加抽小电视功能
 - 0.8.1: 添加瓜子换硬币，扭蛋币
 - 0.8.2: 添加双端心跳发送
 - 0.8.3: 添加输出信息
 - 0.8.4: 添加完成每日任务

## 简易使用
 1. 克隆或者下载项目，`https://github.com/lkeme/BiliHelper.git`
 2. 修改 `index.php`, 添加你B站账号密码到`account`里保存
 3. 键入命令 `php index.php`, 试运行（可选）
 4. 使用 `screen` 后台运行，或 `nohup`
 
 > 因为瓜子兑换硬币需要一个必要的参数，所以暂时取消cookie的登陆，后期可能会还原

 > 添加账号密码前请提前确认账号密码正确，多次尝试可能会呼出验证码，造成不必要的事情

 > 主要的配置，修改`index.php`就好，么有特别需求，可以修改其他文件的配置

 > 因为是拼接的另一个项目，开始想被耦合度太高，造成的结果就是结构全乱了，勉强把功能实现了

 > 暂定为`beta`版本，有时间就重构，没时间就将就着用，代码很渣，随便吐槽，有问题`issue`

## 高级
用 systemd 食用最佳  

PS: 这里推荐一个即时通知服务 https://sc.ftqq.com/3.version

## systemd 脚本
```
# /usr/lib/systemd/system/bilibili.service
[Unit]
Description=Bilibili Helper Daemon
Documentation=https://i-meto.com/bilibili-silver/
After=network.target

[Service]
ExecStart=/usr/bin/php /path/to/index.php
ExecStop=/bin/kill -HUP $MAINPID
Restart=on-failure
StartLimitInterval=30min
StartLimitBurst=60
LimitNOFILE=65534
LimitNPROC=65534
LimitCORE=infinity

[Install]
WantedBy=multi-user.target
```

## 注意事项
 1. 虽然脚本为 PHP，但由于需要保持长时间运行，因此不能通过直接访问网页来使用
 2. 需要额外安装 php-gd、php-curl 组件

## FAQ

Q: 如何同时挂多个帐号？
A: 可以复制 `index.php` 为 `index1.php`, 同样修改 cookie 后在 `crontab` 添加记录

Q: 为什么会有 `PHP Parse error: syntax error, unexpected '[' ` 报错？
A: 这是因为 PHP 低版本不支持数组中括号写法，建议升级到 PHP5.6+，脚本现已兼容。

Q: 自动清理（投喂）过期礼物给谁？
A: 默认投喂给我的直播间，如果需要的话，可以在 index.php 添加一行
```
$api->roomid='3746256'; // 主播房间号
```

Q: 更可靠的获取 cookie 方法?
A: 需要点开一个直播间，按 F12 选 Network 选项卡，稍等大约 5 分钟后拿到 https://api.live.bilibili.com/ 开头的数据包，复制里面的 cookie 即可。


## Example
```php
//输入账号密码必填
$account = [
    'username' => 'Example@qq.com',
    'password' => 'Example',
];
```

```log
λ php index.php
[2018-03-03 12:21:38] [BiliLogin] 加载账号密码
[2018-03-03 12:21:38] [BiliLogin] 加载成功,获取加密信息
[2018-03-03 12:21:39] [BiliLogin] 获取Cookie成功
[2018-03-03 12:21:39] [签到] 今天已签到过
[2018-03-03 12:21:40] [心跳] level:37 exp:22087300/30000000 (73.624%)
[2018-03-03 12:21:40] [宝箱] 今天所有的宝箱已经领完!
[2018-03-03 12:21:40] [投喂] 开始翻动礼物
[2018-03-03 12:21:40] [收礼] 没有礼物可以领了呢
[2018-03-03 12:21:40] [扭蛋币] EggMoney:已经领取,请勿重复领取
[2018-03-03 12:21:40] [COIN] 硬币兑换: 每天最多能兑换 1 个
[2018-03-03 12:21:40] [SOCKET] 查找弹幕服务器中
[2018-03-03 12:21:41] [SOCKET] 连接弹幕服务器中
[2018-03-03 12:21:41] [SOCKET] 连接弹幕服务器成功
[2018-03-03 12:21:41] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:21:41] [LIVE] WIN: 201803|No Winning ~
[2018-03-03 12:21:41] [SOCKET] CMD: 暂定采集新的数据类型
[2018-03-03 12:21:47] [SOCKET] SEND_GIFT: 脑洞分拨中心 赠送5份辣条
[2018-03-03 12:22:03] [SOCKET] DANMU_MSG: 夏沫丶琉璃浅梦 : 人不能
[2018-03-03 12:22:04] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:22:04] [SOCKET] CMD: 暂定采集新的数据类型
[2018-03-03 12:22:05] [SOCKET] DANMU_MSG: 夏沫丶琉璃浅梦 : 是不一
[2018-03-03 12:22:15] [SOCKET] SEND_GIFT: 风尘ひでよし 赠送2份辣条
[2018-03-03 12:22:23] [SOCKET] DANMU_MSG: 今天也要吃榴莲ovo : (๑•̀
[2018-03-03 12:22:24] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:22:24] [SOCKET] CMD: 暂定采集新的数据类型
[2018-03-03 12:22:54] [SOCKET] SEND_GIFT: 绝望の少女つ 赠送7份辣条
[2018-03-03 12:22:55] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:22:55] [SOCKET] CMD: 暂定采集新的数据类型
[2018-03-03 12:22:57] [SOCKET] SEND_GIFT: 绝望の少女つ 赠送3份亿圆
[2018-03-03 12:23:01] [SOCKET] SEND_GIFT: 绝望の少女つ 赠送43份辣条
[2018-03-03 12:23:02] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:23:02] [SOCKET] CMD: 暂定采集新的数据类型
[2018-03-03 12:23:04] [SOCKET] SEND_GIFT: 司寇然 赠送1份B坷垃
[2018-03-03 12:23:19] [SOCKET] SEND_GIFT: 司寇然 赠送150份辣条
[2018-03-03 12:23:20] [SOCKET] SOCKET: 发送心跳包中
[2018-03-03 12:23:20] [SOCKET] CMD: 暂定采集新的数据类型
..............
```

## 相关
 >本项目基于[BilibiliHelper](https://github.com/metowolf/BilibiliHelper)
 
 >前项目一切不必要的原有信息都么有删除，保持原有状态，另外欢迎重构(Haha)

[BilibiliHelper](https://github.com/metowolf/BilibiliHelper)

[bilibili-live-crawler](https://github.com/wuYinBest/bilibili-live-crawler)

[bilibili-api](https://github.com/czp3009/bilibili-api)


## License
BilibiliHelper is under the MIT license.
