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
    public static $dataLength = 2024;

    /**
     * @var Table
     */
    public $tableInstance;

    /**
     * the max expire of cache limited by this value
     * @var int
     */
    public $maxLive = 3000000;

    /**
     * @var int Gc process will seelp $gcSleep second each 100000 times
     */
    public $gcSleep = 0.01;

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

    public static function initCacheTable($size = 1024, $dataLength = 8192)
    {
        if( $size%1024 !== 0){
            throw new InvalidValueException("swoole_table::size error ：$size");
        }
        static::$dataLength = $dataLength;
        $table = new Table($size);
        $table->column('expire', Table::TYPE_STRING, 11);
        $table->column('nextId', Table::TYPE_STRING, 35);
        $table->column('data', Table::TYPE_STRING,static::$dataLength);
        $table->create();
        return $table;
    }

    protected function getValue($key){
        return $this->getValueRec($key);
    }

    private function getValueRec($key,$nowtime = null)
    {
        if(empty($key)){
            return '';
        }
        if(empty($nowtime)){
            $nowtime = time();
        }

        $column = $this->tableInstance->get($key);
        if($column == false){
            return false;
        }
        if($column['expire'] < $nowtime){
            $this->deleteValue($key);
            return false;
        }

        $nextValue = $this->getValueRec($column['nextId'],$nowtime);
        if($nextValue === false){
            var_dump('false:'.$column['nextId']);
            $this->tableInstance->del($key);
            return false;
        }

        return $column['data'].$nextValue;
    }

    protected function setValue($key, $value, $duration){
        $this->gc();
        $expire = $duration + time();
        $valueLength = strlen($value);
        return (boolean) $this->setValueRec($key, $value, $expire, $valueLength);
    }

    private function setValueRec($key, &$value, $expire, $valueLength, $num = 0){
        $start = $num*static::$dataLength;
        if($start > $valueLength){
            return '';
        }
        $nextNum = $num+1;
        $nextId = $this->setValueRec($key, $value, $expire, $valueLength, $nextNum);
        if($nextId === false){
            return false;
        }
        if($num){
            $setKey = $key.$num;
        }else{
            $setKey = $key;
        }
        $result = $this->tableInstance->set($setKey,[
            'expire' => $expire,
            'nextId' => $nextId,
            'data' => substr($value,$start,static::$dataLength)
        ]);
        if($result === false){
            if($nextId){
                $this->deleteValue($nextId);
            }
            return false;
        }
        return $setKey;
    }

    protected function addValue($key, $value, $duration){
        return $this->setValue($key, $value, $duration);
    }

    protected function deleteValue($key){
        return $this->deleteValueRec($key);
    }

    private function deleteValueRec($key)
    {
        $column = $column = $this->tableInstance->get($key);
        if($column){
            $nextId = $column['nextId'];
            unset($column);
            $nextId && $this->deleteValueRec($nextId);
        }
        return $this->tableInstance->del($key);
    }

    protected function flushValues(){
        $table = $this->tableInstance;
        foreach ($table as $key => $column) {
            $this->tableInstance->del($key);
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