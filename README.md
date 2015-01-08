## 工程框架

### 浏览器端

* JS基础库：[elf+js](http://elfjs.com)
* MVC框架+UI组件库：[ER+esui](https://github.com/mytharcher/ER2)

### 服务端

* 运行环境：`Apache web server 2.2+`，`PHP 5.4+`，`MySQL 5+`
* PHP框架：[CodeIgniter（CI）](http://codeigniter.org.cn/)
* 模板引擎：[Smarty](http://www.smarty.net/)（用于CI框架的[整合包](https://github.com/Vheissu/Ci-Smarty)）

### 文件夹结构

	.
	|-- application/              # 后端程序文件
	|  |-- cache/                 # 缓存文件夹
	|  |  `-- smarty/             # Smarty模板缓存（以下两个文件夹要在服务端设置777的权限）
	|  |     |-- cached/          # 模板缓存
	|  |     `-- compiled/        # 已编译模板缓存
	|  |-- config/                # 配置文件
	|  |  |-- alipay.php          # 支付宝配置
	|  |  |-- autoload.php        # 自动加载库的列表配置
	|  |  |-- email.php           # 系统邮件配置
	|  |  |-- form_validation.php # 所有验证规则配置
	|  |  |-- oauth2.php          # OAuth配置
	|  |  `-- smarty.php          # Smarty配置
	|  |-- controllers/           # 控制器，所有访问入口对应处理
	|  |-- core/                  # 对CI框架基础类的扩展
	|  |-- helpers/               # 辅助功能函数
	|  |-- language/              # 语言扩展
	|  |-- libraries/             # 自定义库扩展
	|  |-- models/                # 数据模型
	|  |-- third_party/           # 第三方扩展库
	|  `-- views/                 # 模板文件
	|-- assets/                   # 前端资源文件
	|  |-- css/                   # CSS样式表
	|  |-- img/                   # 内容图片
	|  |-- js/                    # JS脚本
	|  |  |-- site/               # 管理端脚本
	|  |  |  |-- ~shortcut/       # site().xxx快捷方式定义
	|  |  |  |-- components/      # 实例UI组件配置
	|  |  |  |-- controllers/     # 管理端ER框架控制器
	|  |  |  |-- lib/             # 自定义库
	|  |  |  |-- models/          # 管理端ER框架数据模型
	|  |  |  |-- pages/           # 用户端页面对应JS
	|  |  |  |-- ui/              # 自定义UI组件
	|  |  |  `-- ui.js            # esui框架修复和基础扩展
	|  |  |-- site.js             # 站点基础命名空间
	|  |  |-- site.pack.js        # 用户端全部JS自动打包文件
	|  |  |-- site.admin.config.js # 管理端ER框架配置文件
	|  |  |-- site.admin.pack.js  # 管理端登陆页JS自动打包文件
	|  |  `-- site.admin.template.js # 管理端前端模板配置文件
	|  `-- tpl/                   # 管理端前端模板文件
	|-- build/                    # 上线打包脚本
	|-- env/                      # 系统环境变量配置（数据库，邮件系统等，该部分涉及安全问题，真实信息不保存在版本库）
	|-- plugins/                  # 第三方插件（CKEditor）
	|-- sql/                      # 数据库SQL文件
	`-- system/                   # CI框架文件

## 运维

### 上线步骤

#### 首次上线部署

##### 数据库

在数据库控制台运行这个SQL脚本：`sql/install.sql`。该脚本用于创建所有数据表和部分初始测试数据。

##### 网站程序

0.  在服务器使用git命令获取站点代码：
    
        $ git clone https://bitbucket.org/aquarter/ci-bootstrap.git <project-folder>
    
0.  进入网站目录，并修改缓存目录权限：
    
        $ chmod 777 application/cache
    
0.  修改`/env/sample`为`/env/yourdomain.com`（线上域名），并将其中的数据库用户密码和邮箱配置改为线上使用的（参照注释）。

0.  打包前端资源（要求服务器有Java运行环境）：
    
        $ sh build/pack.sh
    
    注：打包后代码不可调试，且版本控制中的文件会被覆盖，所以打包之后不要提交任何代码，仅作为线上使用。

如果服务器不支持命令行，可以在本地或某台开发机完成上述操作后再通过FTP上传到服务器。

#### 升级上线步骤

基本同首次，但注意数据库要使用线上数据库，一般不需特别操作。

### 日常备份 ###
