# BilibiliHelper
B 站直播实用脚本

BUG群 : 55308141


## 运行环境
 php版本推荐7.*+

|extension  |
| --------- |
|php-gd     |
|php-curl   |
|php_sockets|
|php-xml    |
|php-openssl|
|add...     |


## 功能
 - 每日签到
 - 发送PC端心跳
 - 自动领限时礼物
 - 自动领宝箱（瓜子）
 - 自动清理（投喂）过期礼物
 - 抽小电视
 - 银瓜子换硬币(一次)
 - 每日领取扭蛋币(已结束)
 - 发送APP端心跳
 - 完成每日任务
 - 完成每日背包奖励(暂定)
 - PC端活动抽奖
 - 节奏风暴
 - 定时发送自定义弹幕
 - APP端活动抽奖
 - 定时刷新Cookie
 - 应援团签到
 -


## TODO
 - 优化节奏风暴
 - 实物抽奖
 - 实时通知(中奖抽奖)
 -

## 更新日志
 - 0.6.0: 更新大部分接口，新增参数自动检测
 - 0.7.0: 添加账号密码登录, 验证码识别(识别率低)
 - 0.8.0: 添加抽小电视功能
 - 0.8.1: 添加瓜子换硬币，扭蛋币
 - 0.8.2: 添加双端心跳发送
 - 0.8.3: 添加输出信息
 - 0.8.4: 添加完成每日任务
 - 0.8.5: 添加每日背包奖励(暂定,抄来的api，还需要测试,刷个版本号)
 - 0.8.6: 领取宝箱验证码API变动
 - 0.8.7: 添加节奏风暴代码(效率低)
 - 0.8.9: 添加活动抽奖(现活动：桃源盛会)
 - 0.9.0: 添加可选任务,包括(赠送过期礼物，银瓜子兑换硬币，定时弹幕)
 - 0.9.1: 添加自定义直播间房间(修复一点BUG)
 - 0.9.2: 添加app端活动抽奖(pc辣条x2,app辣条x1)
 - 0.9.3: 添加定时刷新cookie信息(暂定20小时一次)
 - 0.9.4: 添加应援团签到(每天一次,增加10点亲密度)
 - 0.9.5: 添加一个外挂功能
 -

## BUG日志
 - 2018年3月28日
  > 删除扭蛋币代码，增加一个curl模块，添加APP端的弹幕发送，添加一个规避措施(防止被ban)
 -


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
 2. 需要额外安装 php-gd、php-curl、php_sockets 组件
 3. 本程序是脚本终端执行，不要用网页执行
 4. 请在当前目录(index.php所在目录)执行脚本，不然可能会出现蜜汁报错

 > 重要: `php_sockets` 必须开启，不然socket不能连接

## FAQ
Q: 关于应援团签到?

A: 目前应援团签到属于可选任务,可以自行修改`customConfig.php`配置项,默认开启的

Q: SOCKET弹幕刷屏的时候领不了礼物?

A: 因为程序默认随机的人气直播间，辣条刷屏、节奏风暴消息刷屏等，

A: 现在支持自定义房间，推荐一些经常有弹幕，但是又不会快速刷屏的直播间


Q: 程序一直刷屏连接socket?

A: 查看你的`php_sockets`模块是否开启


Q: 关于节奏风暴抽奖问题?

A: 本程序现在的节奏风暴抽奖几率非常小，原因: 有时候会呼出验证码(暂时没写识别),

A: 只是监控了当前直播间和全站公告,全站公告需要20倍才出一次,当前直播间可以会被刷屏，弹幕等

A: 给延迟掉,(风暴10s不到就结束了),所以几率很小，后期可能会单独抽出来写......


Q: 需要定时发送自定义弹幕?

A: 修改`includes/Traits/customConfig.php`里面的配置项，一定要看完配置说明


Q: 怎么跳过一些任务，不执行?

A：同上面的配置，暂时只添加了3个可以有必要跳过的任务，待添加


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
[2018-03-28 12:15:22] [BiliLogin] 加载账号密码
[2018-03-28 12:15:22] [BiliLogin] 加载成功,获取加密信息
[2018-03-28 12:15:23] [BiliLogin] 获取Cookie成功
[2018-03-28 12:15:24] [签到] 今天已签到过
[2018-03-28 12:15:25] [心跳] PCHeart: OK!
[2018-03-28 12:15:25] [心跳] level:16 exp:424000/1000000 (42.4%)
[2018-03-28 12:15:25] [宝箱] 今天所有的宝箱已经领完!
[2018-03-28 12:15:25] [收礼] 没有礼物可以领了呢
[2018-03-28 12:15:26] [DAILY] 每日任務: [double_watch_task]奖励已领取
[2018-03-28 12:15:26] [DAILY] 每日任務: 完成!
[2018-03-28 12:15:26] [DAILY] 每日背包: 完成!
[2018-03-28 12:15:26] [投喂] 开始翻动礼物
[2018-03-28 12:15:27] [COIN] 硬币兑换: 每天最多能兑换 1 个
[2018-03-28 12:15:27] [GROUP] Group: 你没有需要签到的应援团!
[2018-03-28 12:15:27] [SOCKET] 查找弹幕服务器中
[2018-03-28 12:15:27] [SOCKET] 连接弹幕服务器中
[2018-03-28 12:15:28] [SOCKET] 连接123456弹幕服务器成功
[2018-03-28 12:15:28] [心跳] SocketHeart: OK!
[2018-03-28 12:15:28] [LIVE] WIN: 201803|No Winning ~
[2018-03-28 12:15:28] [心跳] AppHeart: OK!
[2018-03-28 12:15:28] [心跳] 昵称: 用户名|等级: 16|银瓜子: 33096
[2018-03-28 12:15:28] [心跳] 金瓜子: 0|硬币: 90|经验值: 424000/1000000
[2018-03-28 12:15:28] [SOCKET] ONLINE: 当前直播间有847人在线
...
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
