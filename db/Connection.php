<?php
namespace deepziyu\yii\swoole\db;

use deepziyu\yii\swoole\web\Application;
use Yii;
use deepziyu\yii\swoole\bootstrap\YiiWeb;
use deepziyu\yii\swoole\db\mysql\Schema;
use yii\base\NotSupportedException;

/**
 * Class Connection
 * @package deepziyu\yii\swoole\db
 */
class Connection extends \yii\db\Connection
{
    //public $commandClass = 'deepziyu\yii\swoole\db\Command';
    public $pdoClass = 'deepziyu\yii\swoole\db\mysql\PoolPdo';
    public $enableSchemaCache = true;

    public $schemaMap = [
        'pgsql' => 'yii\db\pgsql\Schema', // PostgreSQL
        'mysqli' => 'yii\db\mysql\Schema', // MySQL
        'mysql' => 'deepziyu\yii\swoole\db\mysql\Schema', // MySQL
        'sqlite' => 'yii\db\sqlite\Schema', // sqlite 3
        'sqlite2' => 'yii\db\sqlite\Schema', // sqlite 2
        'sqlsrv' => 'yii\db\mssql\Schema', // newer MSSQL driver on MS Windows hosts
        'oci' => 'yii\db\oci\Schema', // Oracle driver
        'mssql' => 'yii\db\mssql\Schema', // older MSSQL driver on MS Windows hosts
        'dblib' => 'yii\db\mssql\Schema', // dblib drivers on GNU/Linux (and maybe other OSes) hosts
        'cubrid' => 'yii\db\cubrid\Schema', // CUBRID
    ];


    public $enableReloadSchema = true;

    private static $isSchemaLoaded = false;

    public function init()
    {
        if(!empty($this->charset)){
            $this->charset = null;
        }
    }

    /**
     * It work when you init mysql schema before handle request
     * ```php
     *   'on beforeRequest' => function ($event){
     *      //当Application初始化的时候，生成 MysqlSchemas,
     *      //防止Mysql子协程挂起
     *       $app = $event->sender;
     *       $app->getDb()->initSchema();
     *    }
     * ```
     * @see YiiWeb::onYiiEvent()
     * @see https://wiki.swoole.com/wiki/page/p-coroutine.html
     * @var bool
     */
    public function initSchema()
    {
        if(!static::$isSchemaLoaded){
            $this->getSchema()->getTableSchemas();
            static::$isSchemaLoaded =true;
        }
    }

    /**
     * @var Schema the database schema
     */
    private $_schema;

    /**
     * @inheritdoc
     */
    public function getSchema()
    {
        if ($this->_schema !== null) {
            return $this->_schema;
        } else {
            $driver = $this->getDriverName();
            if (isset($this->schemaMap[$driver])) {
                $config = !is_array($this->schemaMap[$driver]) ? ['class' => $this->schemaMap[$driver]] : $this->schemaMap[$driver];
                $config['db'] = $this->getComponentId($this);

                return $this->_schema = Yii::createObject($config);
            } else {
                throw new NotSupportedException("Connection does not support reading schema information for '$driver' DBMS.");
            }
        }
    }

    /**
     * find the Component Id
     * @param \yii\base\Object $object
     * @return bool|int|string
     */
    protected function getComponentId(\yii\base\Object $object)
    {
        /** @var Application $app */
        $app = Yii::$app->getApplication();
        $components = $app->getComponents(false);
        foreach ($components as $id => $component) {
            if($object === $component){
                return $id;
            }
        }
        return false;
    }
}