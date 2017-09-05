<?php
namespace deepziyu\yii\swoole\log;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\log\FileTarget as YiiFileTarget;

/**
 * Class FileTarget
 * @package deepziyu\yii\swoole\log
 */
class FileTarget extends YiiFileTarget
{
    protected $lastCount = 0;

    /**
     * @inheritdoc
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        \Swoole\Async::writeFile($this->logFile, $text, null, FILE_APPEND);
    }

    protected function getContextMessage()
    {
        //$context = ArrayHelper::filter($GLOBALS, $this->logVars);
        $context = ArrayHelper::filter([
            '_GET' => Yii::$app->getRequest()->getQueryParams(),
            '_POST' => Yii::$app->getRequest()->getBodyParams(),
            '_SERVER' => Yii::$app->getRequest()->swooleRequest->server,
            '_FILES' => Yii::$app->getRequest()->swooleRequest->files,
            '_COOKIE' => Yii::$app->getRequest()->getCookies()->toArray(),
        ], $this->logVars);
        $result = [];
        foreach ($context as $key => $value) {
            // some function is not allow in swoole
            //$result[] = "\${$key} = " . VarDumper::dumpAsString($value);
            $result[] = "\${$key} = " . Json::encode($value,JSON_UNESCAPED_UNICODE);
        }
        return implode("\n", $result);
    }

}
