<?php
/**
 * Created by PhpStorm.
 * User: lizhenju
 * Date: 2017/8/29
 * Time: 14:29
 */

namespace deepziyu\yii\swoole\pool;

use Yii;
use yii\base\Component;
use Swoole\Coroutine\Redis;
use yii\db\Exception;

class RedisPool extends Component
{
    public $hostname = '127.0.0.1';

    public $port = 6379;

    public $timeout = 1000;

    public $password;

    public $database;

    public $maxSize = 500;

    public $minSize = 10;

    public $sleep = 0.01;

    public $maxSleepTimes = 10;

    protected $count = 0;

    /**
     * @var \SplQueue
     */
    protected $poolQueue;

    public function init()
    {
        parent::init();
        $this->poolQueue = new \SplQueue();
        for ($i = 0; $i < $this->minSize; $i++) {
            $conenct = $this->getConnect();
            $this->releaseConnect($conenct);
        }
    }

    protected function openOneConnect()
    {
        $connect = new Redis();
        $isS = $connect->connect($this->hostname, $this->port,$this->timeout);
        if ($isS === false) {
            throw new Exception($connect->errMsg, [], $connect->errCode);
        }
        if($this->password && false === $connect->auth($this->password)){
            throw new Exception('error password for redis', [], 500);
        }
        if($this->database !== null && false === $connect->select($this->database)){
            throw new Exception('error when select database for redis', [], 500);
        }
        $this->count++;
        return $connect;
    }

    protected function getConnect($sleepTime = 0)
    {
        $connect = null;
        if ($this->poolQueue->count()) {
            $connect = $this->poolQueue->dequeue();
        } elseif ($this->count < $this->maxSize) {
            $connect = $this->openOneConnect();
        } elseif ($sleepTime < $this->maxSleepTimes) {
            \Swoole\Coroutine::sleep($this->sleep);
            $sleepTime++;
            $connect = $this->getConnect($sleepTime);
        }
        if ($connect === null) {
            throw new Exception("mysqlPool is fulled", [], 1099);
        }
        return $connect;
    }

    protected function releaseConnect(Redis $connect)
    {
        if(empty($connect)){
            return ;
        }
        $this->poolQueue->enqueue($connect);
    }

    /**
     * @param $redisCommand
     * @param array $params
     * @return RedisResultData
     * @throws \Exception
     */
    public function executeCommand($redisCommand, ...$params)
    {
        $connect = null;
        try {
            $connect = $this->getConnect();
            $res = $connect->{$redisCommand}(...$params);
            $resultData = new RedisResultData([
                'result' => $res,
                'errCode' => $connect->errCode,
                'errMsg' => $connect->errMsg,
            ]);
            return $resultData;
        } catch (\Exception $exception) {
            throw $exception;
        } finally {
            $this->releaseConnect($connect);
        }
    }

}