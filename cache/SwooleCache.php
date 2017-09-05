<?php
/**
 * Created by PhpStorm.
 * User: lizhenju
 * Date: 2017/8/28
 * Time: 14:00
 */

namespace deepziyu\yii\swoole\cache;

use Yii;
use yii\caching\Cache;
use yii\base\InvalidValueException;
use yii\base\Exception;
use Swoole\Table;

/**
 * Class SwooleCache
 * @package deepziyu\yii\swoole\cache
 */
class SwooleCache extends Cache
{
    /**
     * the max size of cache is limited by this const
     */
    const DATA_LEN = 2024;

    /**
     * @var Table
     */
    public $tableInstance;

    /**
     * the max expire of cache limited by this value
     * @var int
     */
    public $maxLive = 30000;

    /**
     * @var int Gc process will seelp $gcSleep second each 100000 times
     */
    public $gcSleep = 1;

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public $gcProbability = 100;

    public function init()
    {
        $table =  Yii::$app->getServer()->cacheTable;
        if(!$table instanceof Table){
            throw new InvalidValueException('SwooleCache require deepziyu\yii\swoole\server::cacheTable');
        }
        $this->tableInstance = $table;
        parent::init();
    }

    public static function initCacheTable($size = 1024)
    {
        if( $size%1024 !== 0){
            throw new InvalidValueException("swoole_table::size error ：$size");
        }
        $table = new Table($size);
        $table->column('expire', Table::TYPE_STRING, 11);
        $table->column('data', Table::TYPE_STRING,static::DATA_LEN);
        $table->create();
        return $table;
    }

    protected function getValue($key){
        $column = $this->tableInstance->get($key);
        if($column == false){
            return false;
        }
        if($column['expire'] < time()){
            $this->deleteValue($key);
            return false;
        }
        return $column['data'];
    }

    protected function setValue($key, $value, $duration){
        $this->gc();
        if($duration < 1 || $duration > $this->maxLive){
            $duration = $this->maxLive;
        }
        if(strlen($value) > static::DATA_LEN){
            throw new Exception('too long for cache value');
        }
        return $this->tableInstance->set($key,[
            'expire' => $duration + time(),
            'data' => $value
        ]);
    }

    protected function addValue($key, $value, $duration){
        return $this->setValue($key, $value, $duration);
    }

    protected function deleteValue($key){
        return $this->tableInstance->del($key);
    }

    protected function flushValues(){
        $table = $this->tableInstance;
        foreach ($table as $key => $column) {
            $this->deleteValue($key);
        }
    }

    /**
     * Removes the expired data values.
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
     * Defaults to false, meaning the actual deletion happens with the probability as specified by [[gcProbability]].
     */
    public function gc($force = false)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            Yii::info(static::className()." GC begin");
            $i = 100000;
            $table = $this->tableInstance;
            foreach ($table as $key => $column) {
                if($column['expire'] < time() || true){
                    $this->deleteValue($key);
                }
                $i--;
                if($i <= 0){
                    \Swoole\Coroutine::sleep($this->gcSleep);
                    $i = 100000;
                }
            }
            Yii::info(static::className()." GC end.");
        }
    }
}