<?php

namespace deepziyu\yii\swoole\web;

use deepziyu\yii\swoole\coroutine\Context;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\ExitException;
use deepziyu\yii\swoole\server\Server;

/**
 * Class Application
 * @package deepziyu\yii\swoole\web
 */
class Application extends \yii\web\Application
{
    /**
     * @var Server
     */
    protected $server;

    private $bootstrapComponents = [];

    public function __construct(array $config = [])
    {
        Yii::$app = new NullApp();
        Yii::$context->setContextDataByKey(Context::COROUTINE_APP,$this);
        static::setInstance($this);
        //$this->loadedModules[get_called_class()] = $this;

        $this->state = self::STATE_BEGIN;

        $this->preInit($config);

        $this->registerErrorHandler($config);

        //parent::__construct($config);
        Component::__construct($config);
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Server $server
     */
    public function setServer(Server $server)
    {
        $this->server = $server;
    }

    public function run()
    {
        try {
            $this->beforeRun();
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);
            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;

            if(! ($response instanceof \deepziyu\yii\swoole\web\Response)){
                throw new Exception('Response must extend \deepziyu\yii\swoole\web\Response');
            }
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        }catch (\Exception $exception){
            Yii::$app->getErrorHandler()->handleException($exception);
            return 0;
        }catch (\Throwable $errorException){
            Yii::$app->getErrorHandler()->handleException($errorException);
            return 0;
        }
    }

    /**
     * 在run开始前执行配置文件启动组件
     */
    private function beforeRun()
    {
        $this->runComponentBootstrap();
    }

    /**
     * 重写引导组件方法
     * @throws InvalidConfigException
     */
    protected function bootstrap()
    {
        $request = $this->getRequest();
        Yii::setAlias('@webroot', dirname($request->getScriptFile()));
        Yii::setAlias('@web', $request->getBaseUrl());
        if ($this->extensions === null) {
            $file = Yii::getAlias('@vendor/yiisoft/extensions.php');
            $this->extensions = is_file($file) ? include($file) : [];
        }
        foreach ($this->extensions as $extension) {
            if (!empty($extension['alias'])) {
                foreach ($extension['alias'] as $name => $path) {
                    Yii::setAlias($name, $path);
                }
            }
            if (isset($extension['bootstrap'])) {
                $component = Yii::createObject($extension['bootstrap']);
                if ($component instanceof BootstrapInterface) {
                    $this->bootstrapComponents[] = $component;
                } else {
                    Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
                }
            }
        }
        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                if ($this->has($class)) {
                    $component = $this->get($class);
                } elseif ($this->hasModule($class)) {
                    $component = $this->getModule($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            if (!isset($component)) {
                $component = Yii::createObject($class);
            }

            if ($component instanceof BootstrapInterface) {
                //记录组件,不重启
                $this->bootstrapComponents[] = $component;
            } else {
                Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
            }
        }
    }

    /**
     * Eet the Component boot each request .
     * But it may cause some error or memory leak , es. Debug and Gii Component
     *
     */
    protected function runComponentBootstrap(){
        foreach ($this->bootstrapComponents as $component) {
            if ($component instanceof BootstrapInterface) {
                Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                $component->bootstrap($this);
            }
        }
    }

    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ? : $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        } else {
            return $status;
        }
    }
}