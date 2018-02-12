<?php
namespace deepziyu\yii\swoole\pool;

use yii\base\Component;
use Swoole\Coroutine\MySQL;
use yii\db\Exception;


class MysqlPool extends Component
{
    private $defaultMysqlConfig = [
        'host' => '127.0.0.1',
        'user' => '',
        'password' => '',
        'database' => '',
        'port'    => 3306,
        'timeout' => 1000,
        'charset' => 'utf8'
    ];

    public $mysqlConfig =[];

    public $maxSize = 10;

    public $minSize = 2;

    public $sleep = 0.01;

    public $maxSleepTimes = 10;

    /**
     * @var \SplQueue
     */
    protected $poolQueue;

    protected $resources = [];

    public function init()
    {
        $this->mysqlConfig = array_merge($this->defaultMysqlConfig,$this->mysqlConfig);
        $this->poolQueue = new \SplQueue();
        for($i =  0;$i<$this->minSize;$i++){
            $conenct = $this->openOneConnect();
            $this->releaseConnect($conenct);
        }
        parent::init();
    }

    protected function openOneConnect()
    {
        $swoole_mysql = new MySQL();
        $swoole_mysql->connect($this->mysqlConfig);
        if($swoole_mysql->connected === false){
           throw new Exception($swoole_mysql->connect_error,[],$swoole_mysql->connect_errno);
        }
        $this->resources[(string)$swoole_mysql->sock] = $swoole_mysql;
        return $swoole_mysql;
    }

    /**
     * @param string $sql
     * @param int $bindID the socket id of connect
     * @return ResultData
     * @throws \Exception
     */
    public function doQuery($sql, $bindID = null)
    {
        if($bindID !== null){
            $connect = $this->getBindConnect($bindID);
        }else{
            $connect  = $this->getConnect();
        }
        $res = false;
        try{
            $res = $connect->query($sql);
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            if($bindID === null ) $this->releaseConnect($connect);
        }
        return new ResultData([
            'result' => $res,
            "affected_rows"=> $connect->affected_rows,
            "insert_id"=> $connect->insert_id,
            "error"=> $connect->error,
            "errno"=> $connect->errno,
        ]);
    }

    public function prepareAndExecute(string $sql, array $inputParams = [], $bindID = null)
    {
        if($bindID !== null){
            $connect = $this->getBindConnect($bindID);
        }else{
            $connect  = $this->getConnect();
        }
        $res = false;
        $stmt = null;
        try{
            $stmt = $connect->prepare($sql);
            $stmt && $res = $stmt->execute($inputParams);

            /**
             * it is better to use statement object once,
             * I do not want to provide any Socket-Resources to the developer,
             * by a thought as all Link-Resources is the devils to some lovely developer.
             */
            unset($stmt);
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            if($bindID === null) $this->releaseConnect($connect);
        }
        return new ResultData([
            'result' => $res,
            "affected_rows"=> $connect->affected_rows,
            "insert_id"=> $connect->insert_id,
            "error"=> $connect->error,
            "errno"=> $connect->errno,
        ]);
    }

    public function escape(string $string) : string
    {
        $connect  = $this->getConnect();
        $res = null;
        try{
            if(method_exists($connect,'escape')){
                $res = $connect->escape($string);
            }else{
                \Yii::warning("MysqlPool::escape() is not effected! please check swoole compile option [--enable-mysqlnd]");
                $res = "'" . str_replace("'", "''", $string) . "'";
            }
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            $this->releaseConnect($connect);
        }
        return $res ?? $string;
    }

    public function begin()
    {
        $connect  = $this->getConnect();
        $res = false;
        try{
            $res = $connect->begin();
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            $this->releaseConnect($connect);
        }
        if($res === false){
            return false;
        }
        return (string) $connect->sock;
    }

    public function commit($bindID)
    {
        $connect = $this->getBindConnect($bindID);
        $res = false;
        try{
            $res = $connect->commit();
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            $this->releaseConnect($connect);
        }
        return $res;
    }

    public function rollBack($bindID)
    {
        $connect = $this->getBindConnect($bindID);
        $res = false;
        try{
            $res = $connect->rollback();
        }catch (\Exception $exception){
            throw $exception;
        }finally{
            $this->releaseConnect($connect);
        }
        return $res;
    }

    /**
     * @param $bindID
     * @return MySQL
     * @throws Exception
     */
    protected function getBindConnect($bindID)
    {
        if(!isset($this->resources[$bindID])){
            throw new Exception("connect has close or not bind",[],1098);
        }
        return $this->resources[$bindID];
    }

    protected function getConnect($sleepTime = 0){
        $connect = null;
        if($this->poolQueue->count()){
            $connect = $this->poolQueue->dequeue();
        }elseif(count($this->resources) < $this->maxSize){
            $connect = $this->openOneConnect();
        }elseif ( $sleepTime < $this->maxSleepTimes){
            \Swoole\Coroutine::sleep($this->sleep);
            $sleepTime++;
            $connect = $this->getConnect($sleepTime);
        }
        if($connect === null){
            throw new Exception("mysqlPool is fulled",[],1099);
        }elseif(!$connect->connected){
            $this->releaseConnect($connect);
            $sleepTime--;
            $connect = $this->getConnect($sleepTime);
        }
        return $connect;
    }

    protected function releaseConnect(MySQL $connect){
        if(!$connect){
            return;
        }
        if(!$connect->connected){
            $this->close($connect);
            return ;
        }
        $this->poolQueue->enqueue($connect);
    }

    protected function close(MySQL $connect)
    {
        unset($this->resources[(string)$connect->sock]);
        return $connect->close();
    }

}