<?php
namespace deepziyu\yii\swoole\web;

use deepziyu\yii\swoole\coroutine\Context;
use Yii;

/**
 * Class NullApp
 * It will work with Context
 * @package deepziyu\yii\swoole\web
 */
class NullApp
{
    public function &__get($name)
    {
        $application = $this->getApplication();
        if (property_exists($application, $name)) {
            return $application->{$name};
        } else {
            $value = $application->{$name};
            return $value;
        }

    }

    public function __set($name, $value)
    {
        $application = $this->getApplication();
        $application->{$name} = $value;
    }

    public function __call($name, $arguments)
    {
        $application = $this->getApplication();
        return call_user_func_array([$application, $name], $arguments);
    }

    /**
     * @return \deepziyu\yii\swoole\web\Application
     */
    public function getApplication()
    {
        return Yii::$context->getContextDataByKey(Context::COROUTINE_APP);
    }
}