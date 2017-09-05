<?php
namespace deepziyu\yii;

use yii\BaseYii as LBaseYii;
use deepziyu\yii\swoole\coroutine\Context;

class BaseYii extends LBaseYii
{
    /**
     * 上下文持久对象，这里存着每个请求协程 new 的 application
     * @var Context
     */
    public static $context;
}