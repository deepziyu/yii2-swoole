<?php
namespace deepziyu\yii\swoole\server;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Server as SwooleServer;
use yii\helpers\FileHelper;

/**
 * Class HttpServer
 * @package deepziyu\yii\swoole\server
 */
class HttpServer extends Server
{
    public $index = '/index.php';

    public function init()
    {
        if(defined('WEB_ROOT')){
            $this->root = WEB_ROOT;
        }
        parent::init();
    }

    /**
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     */
    public function onRequest($request,$response)
    {
        $this->bootstrap->onRequest($request,$response);
    }

    public function onTask(SwooleServer $serv, int $task_id, int $src_worker_id, $data)
    {
        $result = $this->bootstrap->onTask($serv,$task_id,$src_worker_id,$data);
        return $result;
    }

    public function onFinish(SwooleServer $serv, int $task_id, string $data)
    {
        $this->bootstrap->onFinish($serv,$task_id,$data);
    }
}