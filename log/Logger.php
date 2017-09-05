<?php
namespace deepziyu\yii\swoole\log;

/**
 * Class Log
 * @package deepziyu\yii\swoole\log
 */
class Logger extends \yii\log\Logger
{
    public function init()
    {
        register_shutdown_function(function () {
            $this->log('onFatalErrorShutdown',self::LEVEL_INFO);
            $this->onFatalErrorShutdown();
        });
        parent::init();
    }

    public function log($message, $level, $category = 'application')
    {
        $time = microtime(true);
        $traces = [];
        if ($this->traceLevel > 0 && false) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0 && strpos($trace['file'], YII2_SWOOLE_PATH) !== 0) {
                    //cli remove start script
                    if(isset($trace['class']) && $trace['class']=='deepziyu\yii\swoole\server\Server'){
                        break;
                    }
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->traceLevel) {
                        break;
                    }
                }
            }
        }
        // exceptions may not be serializable if in the call stack somewhere is a Closure
        if($message instanceof \Throwable){
            $message = (string) $message;
        }
        $this->messages[] = [$message, $level, $category, $time, $traces, memory_get_usage()];
        if ($this->flushInterval > 0 && count($this->messages) >= $this->flushInterval) {
            $this->flush();
        }
    }

    /**
     *
     * callback regsiter_shutdown_function
     * set exception not work on swoole
     * if you use php7, use can and try catch for throwable
     */
    public function onFatalErrorShutdown()
    {
        $error = error_get_last();
        if (isset($error['type'])) {
            switch ($error['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                    $message = $error['message'];
                    $this->log($message,self::LEVEL_ERROR);
                    break;
                default:
                    break;
            }
        }
    }
}