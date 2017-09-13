<?php
namespace deepziyu\yii\swoole\di;

use Yii;
use yii\base\Object;
use \ReflectionClass;
use yii\di\NotInstantiableException;
use yii\helpers\Json;

class Container extends \yii\di\Container
{
    /**
     * the original classes will be replace
     * @var array
     */
    private $classCompatible = [
        'yii\web\Request' => 'deepziyu\yii\swoole\web\Request',
        'yii\web\Response' => 'deepziyu\yii\swoole\web\Response',
        'yii\web\ErrorHandler' => 'deepziyu\yii\swoole\web\ErrorHandler',
        'yii\log\Dispatcher' => 'deepziyu\yii\swoole\log\Dispatcher',
        'yii\log\FileTarget' => 'deepziyu\yii\swoole\log\FileTarget',
        'yii\log\Logger' => 'deepziyu\yii\swoole\log\Logger',
        'yii\web\Session' => 'deepziyu\yii\swoole\web\Session',
        'yii\db\Connection' => 'deepziyu\yii\swoole\db\Connection',
    ];

    /**
     * the classes will keep in worker progress persistently
     * @var array
     */
    private $classStatic = [
        'deepziyu\yii\swoole\pool\MysqlPool',
        'deepziyu\yii\swoole\pool\RedisPool',
        'deepziyu\yii\swoole\web\ErrorHandler',
        'deepziyu\yii\swoole\db\mysql\Schema',
        'yii\web\UrlManager',
        'yii\i18n\I18N',
    ];

    private static $_singletons_static = [];

    /**
     * @inheritdoc
     */
    public function get($class, $params = [], $config = [])
    {
        return parent::get($class, $params, $config);
    }

    /**
     * @inheritdoc
     */
    public function set($class, $definition = [], array $params = [])
    {
        return parent::set($class, $definition, $params);
    }

    /**
     * @inheritdoc
     */
    protected function build($class, $params, $config)
    {
        if (isset($this->classCompatible[$class])) {
            $class = $this->classCompatible[$class];
        } elseif (isset($this->classCompatible[trim($class, "\\")])) {
            $class = $this->classCompatible[trim($class, "\\")];
        }
        //var_dump("build-$class");
        if (!$obj = $this->getSingletonsStatic($class, $params, $config)) {
            $obj = $this->buildSave($class, $params, $config);
            $this->setSingletonsStatic($class, $params, $config,$obj);
        }
        return $obj;
    }

    /**
     * build classes secure in cotoutine
     * @param $class
     * @param $params
     * @param $config
     * @return object
     * @throws NotInstantiableException
     */
    private function buildSave($class, $params, $config)
    {
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            $instance = $reflection->newInstanceWithoutConstructor();
            call_user_func_array([$instance, '__construct'], $dependencies);
            return $instance;
        } else {
            $object = $reflection->newInstanceWithoutConstructor();
            call_user_func_array([$object, '__construct'], $dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }



    public function getSingletonsStatic($class, $params, $config)
    {
        if (!$this->isStatic($class)) {
            return null;
        }
        $key = $this->buildKey($class, $params, $config);
        return self::$_singletons_static[$key] ?? null;
    }

    /**
     * @param $class
     * @param $params
     * @param $config
     * @param $object
     * @return null
     */
    public function setSingletonsStatic($class, $params, $config, $object)
    {
        if (!$this->isStatic($class)) {
            return null;
        }
        $key = $this->buildKey($class, $params, $config);
        self::$_singletons_static[$key] = $object;
        return null;
    }

    /**
     * @param $class
     * @return bool
     */
    protected function isStatic($class)
    {
        $success = false;
        if (in_array($class, $this->classStatic)) {
            $success = true;
        }
        return $success;
    }

    protected function buildKey($class, $params, $config)
    {
        return $class . md5(Json::encode($params) . Json::encode($config));
    }


}