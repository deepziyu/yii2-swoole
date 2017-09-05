<?php
namespace deepziyu\yii\swoole\coroutine;

use deepziyu\yii\swoole\web\Request;
use deepziyu\yii\swoole\web\Response;
use yii\base\InvalidValueException;


/**
 * 请求上下文
 *
 * @uses      RequestContext
 * @version   2017年04月29日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class Context
{
    /**
     * 请求数据共享区
     */
    const COROUTINE_DATA = "data";

    /**
     * 当前请求 container
     */
    const COROUTINE_CONTAINER = "container";

    /**
     * 当前请求 Application
     */
    const COROUTINE_APP = "app";

    /**
     * @var array 协程数据保存
     */
    private static $coroutineLocal;

    private static $stackTree = [];

    /**
     * 请求共享数据
     *
     * @return array
     */
    public static function getContextData()
    {
        return self::getCoroutineContext(self::COROUTINE_DATA);
    }

    /**
     * 初始化数据共享
     *
     * @param array $contextData
     */
    public static function setContextData(array $contextData = [])
    {
        $coroutineId = self::getcoroutine();
        self::$coroutineLocal[$coroutineId][self::COROUTINE_DATA] = $contextData;
    }

    /**
     * 设置或修改，当前请求数据共享值
     *
     * @param string $key
     * @param mixed  $val
     */
    public static function setContextDataByKey(string $key, $val)
    {
        $coroutineId = self::getcoroutine();
        self::$coroutineLocal[$coroutineId][self::COROUTINE_DATA][$key] = $val;
    }

    /**
     * 获取当前请求数据一个KEY的值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getContextDataByKey(string $key, $default = null)
    {
        $coroutineId = self::getcoroutine();
        if(isset(self::$coroutineLocal[$coroutineId][self::COROUTINE_DATA][$key])){
            return self::$coroutineLocal[$coroutineId][self::COROUTINE_DATA][$key];
        }
        return $default;
    }

    /**
     * 销毁当前协程数据
     */
    public static function destory()
    {
        $coroutineId = self::getcoroutine();
        if (isset(self::$coroutineLocal[$coroutineId])) {
            unset(self::$coroutineLocal[$coroutineId]);
        }
        foreach (static::$stackTree as $key => $value){
            if($coroutineId == $value){
                unset(static::$stackTree[$key]);
            }
        }
    }

    /**
     * 获取协程上下文
     *
     * @param string $name  协程ID
     *
     * @return mixed|null
     */
    private static function getCoroutineContext(string $name)
    {
        $coroutineId = self::getcoroutine();
        if (!isset(self::$coroutineLocal[$coroutineId])) {
            throw new InvalidValueException("协程上下文不存在，coroutineId=".$coroutineId);
        }

        $coroutineContext = self::$coroutineLocal[$coroutineId];
        if (isset($coroutineContext[$name])) {
            return $coroutineContext[$name];
        }
        return null;
    }

    /**
     * markParent
     * @param $pid
     * @return bool
     */
    public static function markParent($pid)
    {
        $coroutineId = \Swoole\Coroutine::getuid();
        if($coroutineId == $pid){
            return false;
        }
        static::$stackTree[$coroutineId] = $pid;
        return true;
    }

    /**
     * 协程ID
     *
     * @return int
     */
    public static function getcoroutine()
    {
        $coroutineId = \Swoole\Coroutine::getuid();
        return static::$stackTree[$coroutineId] ?? $coroutineId;
    }
}