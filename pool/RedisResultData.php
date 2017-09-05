<?php
namespace deepziyu\yii\swoole\pool;


use yii\base\Object;

class RedisResultData extends Object
{
    public $result = false;
    public $errCode = 0;
    public $errMsg = '';
}