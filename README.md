# BilibiliHelper
B 站直播实用脚本

## 功能
 - 每日签到
 - 发送心跳包领经验
 - 自动领限时礼物
 - 自动领宝箱（瓜子）
 - 自动清理（投喂）过期礼物
 - 抽小电视
 - 银瓜子换硬币(一次)
 - 每日领取扭蛋币


## TODO
 - 节奏风暴

## 更新日志
 - 0.6.0: 更新大部分接口，新增参数自动检测
 - 0.7.0: 添加账号密码登录, 验证码识别(识别率低)
 - 0.8.0: 添加抽小电视功能
 - 0.8.1: 添加瓜子换硬币，扭蛋币

## 简易使用
 1. 克隆或者下载项目，`https://github.com/lkeme/BiliHelper.git`
 2. 修改 `index.php`, 添加你B站账号密码到`account`里保存
 3. 键入命令 `php index.php`, 试运行（可选）
 4. 使用 `screen` 后台运行，或 `nohup`
 
 > 因为瓜子兑换硬币需要一个必要的参数，所以暂时取消cookie的登陆，后期可能会还原
 > 添加账号密码前请提前确认账号密码正确，多次尝试可能会呼出验证码，造成不必要的事情
 > 主要的配置，修改`index.php`就好，么有特别需求，可以修改其他文件的配置
 > 因为是拼接的另一个项目，开始想被耦合度太高，造成的结果就是结构全乱了，勉强把功能实现了
 > 暂定为`beta`版本,有时间就重构，没时间就将就着用(233),代码很渣，别吐槽，有问题`issue`

## 高级
用 systemd 食用最佳  
在 `$api->callback=function(){}` 中可以添加自定义函数，实现 cookie 失效后的通知  

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

## License
BilibiliHelper is under the MIT license.
