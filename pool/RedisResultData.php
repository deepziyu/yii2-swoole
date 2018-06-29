<?php
namespace deepziyu\yii\swoole\pool;


use yii\base\BaseObject;

class RedisResultData extends BaseObject
{
    public $result = false;
    public $errCode = 0;
    public $errMsg = '';
}