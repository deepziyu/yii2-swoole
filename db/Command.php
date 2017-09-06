<?php
namespace deepziyu\yii\swoole\db;

use Yii;
use yii\db\DataReader;

/**
 * Class Command
 * @package deepziyu\yii\swoole\db
 */
class Command extends \yii\db\Command
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $sql = $this->getSql();
        $rawSql = $this->getRawSql();
        Yii::info($rawSql, __METHOD__);
        if ($sql == '')
        {
            return 0;
        }
        $token = $rawSql;
        try
        {
            Yii::beginProfile($token, __METHOD__);
            $n = $this->doQuery($rawSql,true,'');
            Yii::endProfile($token, __METHOD__);
            $this->refreshTableSchema();
            return $n;
        }
        catch (\Exception $e)
        {
            Yii::endProfile($token, __METHOD__);
            throw $this->db->getSchema()->convertException($e, $rawSql);
        }
    }

    /**
     * @inheritdoc
     */
    public function queryInternal($method, $fetchMode = null)
    {
        $rawSql = $this->getRawSql();
        Yii::info($rawSql, 'yii\db\Command::query');
        if ($method !== '')
        {
            $info = $this->db->getQueryCacheInfo($this->queryCacheDuration, $this->queryCacheDependency);
            if (is_array($info))
            {
                /* @var $cache \yii\caching\Cache */
                $cache = $info[0];
                $cacheKey = [
                    __CLASS__,
                    $method,
                    $fetchMode,
                    $this->db->dsn,
                    $this->db->username,
                    $rawSql,
                ];
                $result = $cache->get($cacheKey);
                if (is_array($result) && isset($result[0]))
                {
                    Yii::trace('Query result served from cache', 'yii\db\Command::query');
                    return $result[0];
                }
            }
        }
        $token = $rawSql;
        try
        {
            Yii::beginProfile($token, 'yii\db\Command::query');
            if ($fetchMode === null) {
                $fetchMode = $this->fetchMode;
            }
            $result = $this->doQuery($rawSql,false,$method,$fetchMode);
            Yii::endProfile($token, 'yii\db\Command::query');
        }
        catch (\Throwable $e)
        {
            Yii::endProfile($token, 'yii\db\Command::query');
            throw $this->db->getSchema()->convertException($e, $rawSql);
        }
        if (isset($cache, $cacheKey, $info))
        {
            $cache->set($cacheKey, [$result], $info[1], $info[2]);
            Yii::trace('Saved query result in cache', 'yii\db\Command::query');
        }
        return $result;
    }

    /**
     * Execute sql by mysql pool
     * @TODO support slave
     * @TODO support transaction
     * @param $sql
     * @param bool $isExecute
     * @param string $method
     * @param null $fetchMode
     * @param null $forRead
     * @return mixed
     */
    public function doQuery($sql, $isExecute = false, $method = 'fetch', $fetchMode = null, $forRead = null){
        if ($forRead || $forRead === null && $this->db->getSchema()->isReadQuery($sql)) {
            $pdo = $this->db->getSlavePdo();
        } else {
            $pdo = $this->db->getMasterPdo();
        }
        return $pdo->doQuery($sql, $isExecute, $method, $fetchMode);
    }
}