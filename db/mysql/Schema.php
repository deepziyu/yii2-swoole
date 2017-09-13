<?php
namespace deepziyu\yii\swoole\db\mysql;

use yii\di\Instance;
use deepziyu\yii\swoole\db\Connection;

/**
 * Class Schema
 * @package deepziyu\yii\swoole\db\mysql
 */
class Schema extends \yii\db\mysql\Schema
{
    public $db = 'db';

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }
}
