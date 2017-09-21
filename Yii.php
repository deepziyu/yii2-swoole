<?php

require (__DIR__ .'/../../yiisoft/yii2/BaseYii.php');
require (__DIR__ . '/BaseYii.php');
require (__DIR__. "/funReplace.php");

/**
 * Yii is a helper class serving common framework functionalities.
 * It work in swoole-coroutine.
 */
class Yii extends deepziyu\yii\BaseYii
{

}
spl_autoload_register(['Yii', 'autoload'], true, true);
Yii::$classMap = require(__DIR__ . '/classes.php');