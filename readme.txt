=== WPFTP PRO ===

Contributors: laobuluo
Donate link: https://www.laojiang.me/donate/
Tags:WordPress对象存储,WordPress加速,WordPress FTP空间, FTP空间存储,自建云存储
Requires at least: 4.5.0
Tested up to: 6.8.1
Stable tag: 5.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WordPress FTP升级版（简称:WPFTP PRO），在原来我们WPFTP插件基础上做的优化，升级功能。包括在线编辑、禁止缩略图、图片重命名等，且重构代码提高上传效率。

## 插件特点

1. 新增支持已传图片编辑功能
2. 新增同步媒体库删除FTP空间也删除
3. 支持自定义二级目录，任意目录
4. 新增支持一键替换FTP空间网址
5. 支持一键禁止缩略图
6. 支持自动文件重命名
7. 优化重构代码 上传速度效率提高
8. 支持虚拟主机FTP空间
9. 支持云服务器自建FTP空间（新支持）

插件更多详细介绍和安装：[https://www.laojiang.me/5925.html](https://www.laojiang.me/5925.html)

## 网站支持

* [乐在云](https://www.lezaiyun.com/ "乐在云")

* [主机评价网](https://www.zhujipingjia.com/ "主机评价网")

* 欢迎加入插件和站长微信公众号：老蒋朋友圈（公众号）

== Installation ==

* 把插件文件夹上传到/wp-content/plugins/目录下<br />
* 在后台插件列表中激活插件<br />
* 在"设置"找到 WPFTP PRO设置 菜单中输入FTP空间账户相关参数信息

== Frequently Asked Questions ==

* 1.当发现插件出错时，开启调试获取错误信息。
* 2.我们可以选择备份对象存储或者本地同时备份。
* 3.如果已有网站使用，插件调试没有问题之后，需要将原有本地静态资源上传到FTP空间中，然后修改数据库原有固定静态文件链接路径。

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 5.0 =

* 配置管理：使用单独的 WPFTPConfig 类来管理所有配置，使配置更集中和易于维护。
* 日志系统：新增 WPFTPLogger 类来处理日志记录，支持数据库存储和错误日志。
* 连接池和重试机制、更强的安全性（文件类型和大小限制）

= 4.2 =

* 修复存储子目录无法保存问题
* 检测新版本WP支持

= 4.1 =

* 支持自定义FTP端口
* 支持云服务器自建FTP空间（可选被动模式）

= 3.1 =

* 完善文档，兼容WP测试
* 重构代码，提高效率以及增加不少功能

= 2.1 =

* 重构WPFTP插件，升级至PRO版本
* 考虑到之前用户兼容问题，WPFTP依旧可以使用
* 重构代码，提高效率以及增加不少功能

= 1.1 =

* 打算重构WPFTP插件
* 构思新的功能和升级功能

== Upgrade Notice ==
* 