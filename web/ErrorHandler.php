<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/3/4
 * Time: 下午10:30
 */

namespace deepziyu\yii\swoole\web;

use Yii;
use yii\base\ErrorException;
use yii\web\ErrorHandler as WebErrorHandler;
use yii\base\ExitException;

/**
 * swoole不支持set_exception_handler,在ErrorHandle中退出的方法都需要重写
 * @package deepziyu\yii\swoole\web
 */
class ErrorHandler extends WebErrorHandler
{
    public function register()
    {
        //parent::register();
    }


    public function handleException($exception)
    {
        if ($exception instanceof ExitException) {
            return;
        }

        $this->exception = $exception;

        try {
            $this->logException($exception);
            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);
        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
            $this->handleFallbackExceptionMessage($e, $exception);
        } catch (\Throwable $e) {
            // additional check for \Throwable introduced in PHP 7
            $this->handleFallbackExceptionMessage($e, $exception);
        }

        $this->exception = null;
    }

    /**
     * @param \Exception|\Throwable $exception
     * @param \Exception $previousException
     */
    protected function handleFallbackExceptionMessage($exception, $previousException) {
        $msg = "An Error occurred while handling another error:\n";
        $msg .= (string) $exception;
        $msg .= "\nPrevious exception:\n";
        $msg .= (string) $previousException;
        $endResponse = Yii::$app->getResponse();
        if (YII_DEBUG) {
            if (PHP_SAPI === 'cli') {
                if($endResponse instanceof Response && !$endResponse->isSent){
                    $endResponse->getSwooleResponse()->end($msg);
                }else{
                    echo $msg . "\n";
                }
            } else {
                echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, Yii::$app->charset) . '</pre>';
            }
        } else {
            $msg = 'An internal server error occurred.';
            if($endResponse instanceof Response){
                $endResponse->getSwooleResponse()->end($msg);
            }else{
                echo $msg;
            }
        }
        $msg .= "\n\$_SERVER = " . print_r($_SERVER, true);
        error_log($msg);
        if (defined('HHVM_VERSION')) {
            flush();
        }
    }

    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            // load ErrorException manually here because autoloading them will not work
            // when error occurs while autoloading a class
            if (!class_exists('yii\\base\\ErrorException', false)) {
                require_once(\Yii::getAlias('@yii/base/ErrorException.php'));
            }
            $exception = new ErrorException($message, $code, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    return true;
                }
            }

            throw $exception;
        }
        return false;
    }

    public function handleFatalError()
    {
        // load ErrorException manually here because autoloading them will not work
        // when error occurs while autoloading a class
        if (!class_exists('yii\\base\\ErrorException', false)) {
            require_once(\Yii::getAlias('@yii/base/ErrorException.php'));
        }

        $error = error_get_last();

        if (ErrorException::isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;

            $this->logException($exception);

            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);
        }
    }

    protected function renderException($exception)
    {
        parent::renderException($exception);
    }
}