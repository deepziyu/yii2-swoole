<?php
namespace deepziyu\yii\swoole\db;
use deepziyu\yii\swoole\bootstrap\YiiWeb;

/**
 * Class Connection
 * @package deepziyu\yii\swoole\db
 */
class Connection extends \yii\db\Connection
{
    public $commandClass = 'deepziyu\yii\swoole\db\Command';
    public $pdoClass = 'deepziyu\yii\swoole\db\MysqlPoolPdo';
    /**
     * It work when you reload mysql schema
     * @see YiiWeb::onYiiEvent()
     * @see https://wiki.swoole.com/wiki/page/p-coroutine.html
     * @var bool
     */
    public $enableReloadSchema = true;

    private static $isSchemaLoaded = false;

    public function init()
    {
        if(!empty($this->charset)){
            $this->charset = null;
        }
        if($this->enableReloadSchema && !static::$isSchemaLoaded){
            $this->getSchema()->getTableSchemas();
            static::$isSchemaLoaded =true;
        }
        parent::init();
    }
}