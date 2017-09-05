<?php
namespace deepziyu\yii\swoole\pool;


use yii\base\Object;

class ResultData extends Object
{
    public $result = false;
    public $affected_rows = 0;
    public $insert_id = 0;
    public $errno = 0;
    public $error = '';
}