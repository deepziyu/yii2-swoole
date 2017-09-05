<?php
namespace deepziyu\yii\swoole\di;

use Yii;
use deepziyu\yii\swoole\coroutine\Context;

/**
 * Class NullContainer
 * It will work with Context
 * @package deepziyu\yii\swoole\di
 */
class NullContainer
{
    function __get($name)
    {
        $container = $this->getContainer();
        return $container->{$name};
    }

    function __call($name, $arguments)
    {
        $container = $this->getContainer();
        return \call_user_func_array([$container,$name],$arguments);
    }

    /**
     * @return Container
     */
    protected function getContainer(){
        return Yii::$context->getContextDataByKey(Context::COROUTINE_CONTAINER);
    }
}