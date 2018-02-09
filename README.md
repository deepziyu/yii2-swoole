# yii2-swoole

_为赋予 Yii2 框架协程异步能力而生。_

_后期开发会依赖 [Swoft 框架](https://gitee.com/lizhenju/yii2-swoole) 去实现功能，相信 Swoft 会是下一代优秀的框架。_

此插件基于 swoole (v2.0) 底层实现的协程，改造 Yii2 的核心代码，使开发者无感知，以及在不改动业务代码的情况下，用上 swoole 的异步IO能力。


## 特性

- 协程 MySQL 客户端、连接池，支持主从、事务。

- 协程 Redis 客户端、连接池、缓存 （目前未打算支持事务）

- 协程 HttpClient , 依赖于 Swoft 实现

- swoole_table 缓存组件

- 异步文件日志组件

- 业务代码和 swoole 主进程分离


## 安装

#### 环境要求

1. hiredis
2. composer
3. PHP7.X
4. Swoole2.1 且开启协程和异步 Redis

#### swoole install

- 参考 https://wiki.swoole.com/wiki/page/p-coroutine.html

- 参考 https://wiki.swoole.com/wiki/page/589.html

#### composer install

- 在项目中的 `composer.json` 文件中添加依赖：

```json
{
  "require": {
      "deepziyu/yii2-swoole": "*"
  }
}
```

- 执行 `$ php composer.phar update` 或 `$ composer update` 进行安装。



## 配置

你可以参考 [这个示例项目](https://gitee.com/lizhenju/yii2-swoole-demo)

新建一个启动文件即可。

启动文件清晰的展现出本插件的工作、流程原理。动手写这个文件有助于你更加理解本插件。

swoole.php 示例如下：

```php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('WEB_ROOT') or define('WEB_ROOT', dirname(__DIR__) . '/web'); //web目录的路径，用户访问的静态文件都放这里

require(__DIR__ . '/../../vendor/autoload.php');

$config = [
    'id' => 'api-test-hello',
    'setting' => [
        // swoole_server 的配置。
        // @see 其他配置项见 https://wiki.swoole.com/wiki/page/274.html
        'daemonize'=>0,
        'worker_num'=>2,
        'task_worker_num' => 1,
        'log_file' => __DIR__.'/../runtime/logs/swoole.log',
        'log_level'=> 0,
        'chroot' => '/',
    ],
    'cacheTable' => function (){
        // swoole_table 需要提前启动，大小为 2 的次方
        return deepziyu\yii\swoole\cache\SwooleCache::initCacheTable(1024);
    },
    'bootstrap' => [
        'class' => 'deepziyu\yii\swoole\bootstrap\YiiWeb',
        'config' => function(){
            // 用闭包是为了延迟加载
            // 返回 Yii 的各个组件的配置
            require_once(__DIR__ . '/../../vendor/autoload.php');
            require_once(__DIR__ . '/../../yii-swoole/Yii.php');
            require(__DIR__ . '/../config/bootstrap.php');

            $config = yii\helpers\ArrayHelper::merge(
                require(__DIR__ . '/../config/main.php'),
                require(__DIR__ . '/../config/main-local.php'),
                [
                    'components' => [
                      'errorHandler' => [
                           'class'=>'deepziyu\yii\swoole\web\ErrorHandler'
                        ],
                        'cache' => [
                            'class' => 'deepziyu\yii\swoole\cache\SwooleCache',
                        ],
                    ],
                ]
            );

            return $config;
        },
    ],

];

// 配置了这么多，最终还是逃不过一 run()
deepziyu\yii\swoole\server\Server::run($config);

```

## 启动

```
php swoole.php start|stop|reload|reload-task
```

## Usage HttpClient 

HTTP 客户端的使用请参考 [Swotf 文档](https://doc.swoft.org/http.html)。

## TODO

- MysqlPool 目前不支持事务 (transaction)。 ( 已实现 )
- MysqlPool、RedisPool 连接池用满了，目前是用 sleep() 进行排队等待，超过等待次数后，报异常。
- MysqlPool 目前不支持主从。 ( 已实现 )

## 已知Bug

- 迭代器将导致协程挂起。

  BUG代码：
  ```php
      $models = User::find()->each(10);
      foreach ($models as $model) { // Hang up in this row
           $data[] = $model->toArray();
      }
  ```

## 已解决Bug

- new ActiveRecord([]); 中会触发 __set() 魔术方法中调用协程Client，导致两个问题：

  1、 首次实例化会导致协程挂起。

  2、 如果 SQL 报错直接导致 work 进程终止。

  BUG代码：
  ```php
      class OneModel extend ActiveRecord{
          public static function tableName()
          {
             return 'some-table do not exist';
          }
      }
      //导致进程终止
      $model = new OneModel([
          'some-att'=>'some-value',
      ]);
  ```

## 链接

[gitee 仓库](https://gitee.com/lizhenju/yii2-swoole)

[github 仓库](https://github.com/deepziyu/yii2-swoole)

