<?php
namespace deepziyu\yii\swoole\coroutine;


/**
 * Class CoroutineHelp
 * @package deepziyu\yii\swoole\coroutine
 */
class CoroutineHelp
{
    /**
     * Create child coroutine in case to use parent`s context
     * @param \Closure $callback
     * @return mixed
     */
    public static function createChild(\Closure $callback)
    {
        $puid = Context::getcoroutine();
        return \Swoole\Coroutine::create(function () use ($puid,$callback){
            Context::markParent($puid);
            \Swoole\Coroutine::call_user_func($callback);
        });
    }
}