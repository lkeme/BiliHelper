
<p align="center"><img width="300px" src="https://i.loli.net/2018/04/20/5ad97bd395912.jpeg"></p>

<p align="center">
<img src="https://img.shields.io/badge/version-0.0.4-green.svg?longCache=true&style=for-the-badge">
<img src="https://img.shields.io/badge/license-mit-blue.svg?longCache=true&style=for-the-badge">
</p>


# BiliHelper

B 站直播实用脚本

> 群`55308141`:只作用于反馈BUG,别来问怎么使用之类问题

> 另外，打个广告，需要代挂节奏风暴的可以滴滴我，不喜勿喷

## 功能组件

|plugin              |version             |description         |
|--------------------|--------------------|--------------------|
|Daily               |18.04.21            |每日背包奖励         |
|GiftSend            |18.04.21            |自动清空过期礼物     |
|Heart               |18.07.21            |双端直播间心跳       |
|Login               |18.04.25            |帐号登录组件         |
|Silver              |18.07.21            |自动领宝箱           |
|Task                |18.07.21            |每日任务             |
|GiftHeart           |18.04.25            |心跳礼物             |
|Silver2Coin         |18.07.25            |双端银瓜子换硬币     |
|MaterialObject      |18.07.25            |实物抽奖             |
|GroupSignIn         |18.04.25            |应援团签到           |
|Storm               |18.04.26            |节奏风暴             |
|Notice              |18.07.27            |Server酱             |
|RaffleHandler       |18.07.25            |C位光环             |
|RaffleHandler       |18.07.25            |小电视飞船            |
|RaffleHandler       |18.07.25            |C位光环             |
|RaffleHandler       |18.07.25            |摩天大樓             |
|RaffleHandler       |18.07.25            |盛夏么么茶           |


## 未完成功能

|待续        |
|-----------|
|优化节奏风暴|
|添加防封机制|
|自动代理访问|
|添加多用户 |
|待添加      |

## 环境依赖

|Requirement         |
|--------------------|
|PHP >=7.0           |
|php_curl            |
|php_sockets         |
|php_openssl         |

通常使用 `composer` 工具会自动检测上述依赖问题。  

* 项目 `composer.lock` 基于镜像生成 https://pkg.phpcomposer.com/

## 打赏

![](https://i.loli.net/2018/04/07/5ac79ff8c2900.png)

> 有意的打赏个阔落，无意的可以无视.

## 使用指南

 1. 下载（克隆）项目代码，初始化项目
```
$ git clone https://github.com/lkeme/BiliHelper.git
$ cd BiliHelper/conf
$ cp user.conf.example user.conf
```
 2. 使用 composer 工具进行安装。**如果不了解 composer 工具的使用，可以直接到 https://github.com/lkeme/BiliHelper/releases 下载完整代码包，解压后跳到第三步。**
```
$ composer install
```
 3. 按照说明修改配置文件 `user.conf`，只需填写帐号密码即可
 4. 运行测试
```
$ php index.php
```

<p align="center"><img width="680px" src="https://i.loli.net/2018/04/21/5adb497dc3ece.png"></p>

## 多开方案

复制一份example配置文件，修改账号密码即可
```
$ php index.php example.conf
```
要保证配置文件存在，否则默认加载`user.conf`

## 升级指南

 1. 进入项目目录
```
$ cd BiliHelper
```
 2. 拉取最新代码
```
$ git pull
```
 3. 更新依赖库
```
$ composer install
```
 4. 如果使用 systemd 等，需要重启服务
```
$ systemctl restart bilibili
```

## 部署指南

如果你将 BiliHelper 部署到线上服务器时，则需要配置一个进程监控器来监测 `php index.php` 命令，在它意外退出时自动重启。

通常可以使用以下的方式
 - systemd (推荐)
 - Supervisor
 - screen
 - nohup

## systemd 脚本

```
# /usr/lib/systemd/system/bilibili.service

[Unit]
Description=Bili Helper Manager
Documentation=https://github.com/lkeme/BiliHelper
After=network.target

[Service]
ExecStart=/usr/bin/php /path/to/your/BiliHelper/index.php
Restart=always

[Install]
WantedBy=multi-user.target
```

## Supervisor 配置

```
[program:bilibili]
process_name=%(program_name)s
command=php /path/to/your/BiliHelper/index.php
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/tmp/bilibili.log
```

## 报错通知问题

脚本出现 error 级别的报错，会调用通知地址进行提醒，这里推荐两个服务

|服务|官网|
|---|---|
|Server酱|https://sc.ftqq.com/|
|TelegramBot|https://core.telegram.org/bots/api|

示范如下
```
# Server酱
# 自行替换 <SCKEY>
APP_CALLBACK="https://sc.ftqq.com/<SCKEY>.send?text={message}"

# TelegramBot
# 自行替换 <TOKEN> <CHAR_ID>
APP_CALLBACK="https://api.telegram.org/bot<TOKEN>/sendMessage?chat_id=<CHAR_ID>&text={message}"
```

`{message}` 部分会自动替换成错误信息，接口采用 get 方式发送


## 直播间 ID 问题

`user.conf` 文件中有个 `ROOM_ID` 配置，填写此项可以清空临过期礼物给指定直播间。

通常可以在直播间页面的 url 获取到它
```
http://live.bilibili.com/9522051
```

所有直播间号码小于 1000 的直播间为短号，该脚本在每次启动会自动修正，无需关心，

## 相关

 > 本项目基于[BilibiliHelper](https://github.com/metowolf/BilibiliHelper)项目
 
 > 基于父项目的架构开发，在此感谢父项目的开发
 
 > 保留父项目没必要修改的信息，另外欢迎重构(Haha)

[BilibiliHelper](https://github.com/metowolf/BilibiliHelper)


## License 许可证

BiliHelper is under the MIT license.

本项目基于 MIT 协议发布，并增加了 SATA 协议。

当你使用了使用 SATA 的开源软件或文档的时候，在遵守基础许可证的前提下，你必须马不停蹄地给你所使用的开源项目 “点赞” ，比如在 GitHub 上 star，然后你必须感谢这个帮助了你的开源项目的作者，作者信息可以在许可证头部的版权声明部分找到。

本项目的所有代码文件、配置项，除另有说明外，均基于上述介绍的协议发布，具体请看分支下的 LICENSE。

此处的文字仅用于说明，条款以 LICENSE 文件中的内容为准。
