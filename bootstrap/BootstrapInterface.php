<?php
namespace deepziyu\yii\swoole\bootstrap;

use Swoole\Server;

/**
 * Interface BootstrapInterface
 * @package deepziyu\yii\swoole\bootstrap
 */
interface BootstrapInterface
{
    public function onWorkerStart(Server $server,$worker_id);
    public function onWorkerStop(Server $server,$worker_id);
    public function onRequest($request, $response);
    public function onTask(Server $serv, int $task_id, int $src_worker_id, $data);
    public function onFinish(Server $serv, int $task_id, string $data);
}