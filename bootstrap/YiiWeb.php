<?php
namespace deepziyu\yii\swoole\bootstrap;


use Yii;
use yii\base\Event;
use yii\db\ActiveRecord;
use deepziyu\yii\swoole\di\Container;
use deepziyu\yii\swoole\server\Server;
use deepziyu\yii\swoole\web\Application;
use Swoole\Server as SwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use deepziyu\yii\swoole\di\NullContainer;
use deepziyu\yii\swoole\coroutine\Context;


/**
 * Yii starter for swoole server
 * @package deepziyu\yii\swoole\bootstrap
 */
class YiiWeb implements BootstrapInterface
{
    public $index = '/index.php';

    public $webRoot;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var array|\Closure callable init
     */
    public $init;

    /**
     * @var array|\Closure yii config;
     */
    public $config;

    /**
     * @var \yii\web\Application
     */
    public $app;

    /**
     * @var \yii\di\Container
     */
    public $container;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @param SwooleServer $server
     * @param $worker_id
     */
    public function onWorkerStart(SwooleServer $server, $worker_id)
    {
        $this->webRoot = $this->server->root;
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'] = $this->index;
        $_SERVER['SCRIPT_FILENAME'] = $this->webRoot . $this->index;

        if($this->config instanceof \Closure){
            $this->config = call_user_func($this->config);
        }
        if ($this->init instanceof \Closure) {
            call_user_func($this->init,$this);
        }
        Yii::$container = new NullContainer();
        Yii::$context = new Context();
        $this->onYiiEvent();
    }

    /**
     * to fix ActiveRecord::__set() call call some Coroutine error.
     * @see https://wiki.swoole.com/wiki/page/p-coroutine.html
     * @deprecated
     */
    public function onYiiEvent()
    {
        Event::on(ActiveRecord::className(), ActiveRecord::EVENT_INIT, function ($event) {
            /** @var ActiveRecord $model */
            $model = $event->sender;
            $model->getTableSchema();
        });
    }

    /**
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     * @throws \Exception
     * @throws \Throwable
     */
    public function onRequest($request, $response)
    {
        try {
            $this->initRequest($request);
            Yii::$context->setContextDataByKey(Context::COROUTINE_CONTAINER, new Container());
            Yii::$context->setContextDataByKey(Context::COROUTINE_APP, new Application($this->config));
            Yii::$app->setServer($this->server);
            Yii::$app->getRequest()->setSwooleRequest($request);
            Yii::$app->getResponse()->setSwooleResponse($response);
            Yii::$app->run();
            $this->onEndRequest();
        } catch (\Throwable $throwable) {
            echo $throwable->getMessage();
        }

    }

    /**
     * @param SwooleRequest $request
     */
    public function initRequest(SwooleRequest $request)
    {
        $request->get = isset($request->get) ? $request->get : [];
        $request->post = isset($request->post) ? $request->post : [];
        $request->cookie = isset($request->cookie) ? $request->cookie : [];
        $request->files = isset($request->files) ? $request->files : [];
        $request->server = isset($request->server) ? $request->server : [];
        $request->server['REQUEST_URI'] = isset($request->server['request_uri']) ? $request->server['request_uri'] : '';

        $request->server = array_change_key_case($request->server, CASE_UPPER);

        /** not need too
         * $_GET    = isset($request->get) ? $request->get : [];
         * $_POST   = isset($request->post) ? $request->post : [];
         * $_SERVER = isset($request->server) ? $request->server : [];
         * $_FILES  = isset($request->files) ? $request->files : [];
         * $_COOKIE = isset($request->cookie) ? $request->cookie : [];
         */
    }

    public function onTask(SwooleServer $serv, int $task_id, int $src_worker_id, $data)
    {
        $func = array_shift($data);
        if (is_callable($func)) {
            $params[] = array_shift($data);
            call_user_func_array($func, $params);
        }
        return 1;
    }

    public function onFinish(SwooleServer $serv, int $task_id, string $data)
    {
        //echo $data;
    }

    /**
     * To flush Log into LogTargets
     * @param SwooleServer $server
     * @param $worker_id
     */
    public function onWorkerStop(SwooleServer $server, $worker_id)
    {
        //不能在这里处理 coroutine ，所以不能使用 Yii::$app
        $contexts = Yii::$context->getContextData();
        foreach ($contexts as $context){
            /** @var Application $application */
            $application = $context[Context::COROUTINE_APP] ?? null;
            if(empty($application)){
                continue;
            }
            $targets = $application->getLog()->targets;
            foreach ($targets as $target) {
                $target->export();
            }
        }

    }

    /**
     * To flush log
     * To destroy context
     */
    protected function onEndRequest()
    {
        Yii::getLogger()->flush();
        Yii::getLogger()->flush(true);
        Yii::$context->destory();
    }
}