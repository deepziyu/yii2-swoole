### 简介

YiiBoot使用[Yii2](http://www.yiiframework.com/) + [AdminLTE](https://github.com/almasaeed2010/AdminLTE) + mysql，开发高效的通用管理后台；采用代码生成器，以AminLTE为样式模板，生成数据库表的数据模型model、增删改查的视图view和控制器controller，菜单配置后直接使用；高效、快速开发自己的管理后台。
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/131856_3b94983a_2349.png "在这里输入图片标题")

### 功能特点
1、基于Yii2 + AdminLTE + mysql开发，拥有Yii2和AdminLTE的优点，支持移动端访问的后台。
2、便捷的菜单、权限、用户管理，自动识别可用路由控制器，轻松钩选配置路由权限，无需手写配置路由地址，
3、快捷高效，易于二次开发扩展，使用代码生成器，根据数据库表生成对应的增删改查页面，完全不需要修改或轻微调整就能开发出满足自己需求的功能，配置好菜单直接在后台使用。

### 下载安装

1. 运行环境 php5.5+
2. 下载代码
git clone https://git.oschina.net/penngo/chadmin.git
下载http://git.oschina.net/penngo/chadmin/attach_files 附件，或下载master zip最新代码。
3. 新建数据库yiiboot, 修改数据库配置common\config\main.php
4. 导入doc/db.sql。
5. 浏览器访问yiiboot/backend/web/index.php ,如果配置了域名xx.com请指向路径yiiboot/backend/web，访问 xx.com/index，默认帐号密码admin 123456

使用教程：https://git.oschina.net/penngo/chadmin/wikis/tutorial
我在这坐等各位BUG反馈：http://git.oschina.net/penngo/chadmin/issues
 **当前发布版本：v1.1.0** 
 **开发版本：v1.2.0** 


系统截图
 **路由管理** 
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/125143_82438fd0_2349.png "在这里输入图片标题")
 **分配权限** 
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/130345_610f38f2_2349.png "在这里输入图片标题")
 **操作日志记录** 
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/130551_d7f7b3ab_2349.png "在这里输入图片标题")
gii Model Generator生成model和service类文件
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/131001_8ce731b1_2349.png "在这里输入图片标题")
gii CRUD Generator生成视图view和controll类文件
![输入图片说明](http://git.oschina.net/uploads/images/2016/0816/131219_46baf279_2349.png "在这里输入图片标题")

