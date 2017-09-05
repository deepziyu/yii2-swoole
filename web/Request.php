<?php
namespace deepziyu\yii\swoole\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\HeaderCollection;
use yii\web\RequestParserInterface;
use yii\web\NotFoundHttpException;
use yii\web\Cookie;

class Request extends \yii\web\Request
{
    /**
     * @var \Swoole\Http\Request swoole
     * swoole request
     *
     */
    public $swooleRequest;

    /**
     * set the swoole request
     * @param \Swoole\Http\Request $request
     */
    public function setSwooleRequest(\Swoole\Http\Request $request)
    {
        $this->swooleRequest = $request;
    }

    /**
     * get the current swoole request
     * @return \Swoole\Http\Request
     */
    public function getSwooleRequest()
    {
        return $this->swooleRequest;
    }

    /**
     * Resolves the current request into a route and the associated parameters.
     * @return array the first element is the route, and the second is the associated parameters.
     * @throws NotFoundHttpException if the request cannot be resolved.
     */
    public function resolve()
    {
        $result = Yii::$app->getUrlManager()->parseRequest($this);
        if ($result !== false) {
            list ($route, $params) = $result;
            if ($this->getQueryParams() === null) {
                $this->_queryParams = $params;
            } else {
                $this->_queryParams = $params + $this->_queryParams;
            }
            return [$route, $this->getQueryParams()];
        }

        throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
    }

    private $_headers;
    /**
     * Returns the header collection.
     * The header collection contains incoming HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection();
            foreach ($this->swooleRequest->server as $name => $value) {
                if (strncmp($name, 'HTTP_', 5) === 0) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $this->_headers->add($name, $value);
                }
            }
        }
        return $this->_headers;
    }

    /**
     * Returns the method of the current request (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
     * @return string request method, such as GET, POST, HEAD, PUT, PATCH, DELETE.
     * The value returned is turned into upper case.
     */
    public function getMethod()
    {
        if (isset($this->swooleRequest->post[$this->methodParam])) {
            return strtoupper($this->swooleRequest->post[$this->methodParam]);
        }

        if (isset($this->swooleRequest->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($this->swooleRequest->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        if (isset($this->swooleRequest->server['REQUEST_METHOD'])) {
            return strtoupper($this->swooleRequest->server['REQUEST_METHOD']);
        }

        return 'GET';
    }

    /**
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     *
     * Note that jQuery doesn't set the header in case of cross domain
     * requests: https://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
     *
     * @return bool whether this is an AJAX (XMLHttpRequest) request.
     */
    public function getIsAjax()
    {
        return isset($this->swooleRequest->server['HTTP_X_REQUESTED_WITH']) && $this->swooleRequest->server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Returns whether this is a PJAX request
     * @return bool whether this is a PJAX request
     */
    public function getIsPjax()
    {
        return $this->getIsAjax() && !empty($this->swooleRequest->server['HTTP_X_PJAX']);
    }

    /**
     * Returns whether this is an Adobe Flash or Flex request.
     * @return bool whether this is an Adobe Flash or Adobe Flex request.
     */
    public function getIsFlash()
    {
        return isset($this->swooleRequest->server['HTTP_USER_AGENT']) &&
        (stripos($this->swooleRequest->server['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($this->swooleRequest->server['HTTP_USER_AGENT'], 'Flash') !== false);
    }

    private $_rawBody;
    /**
     * Returns the raw HTTP request body.
     * @return string the request body
     */
    public function getRawBody()
    {
        if ($this->_rawBody === null) {
            $this->_rawBody = $this->swooleRequest->rawContent();
        }
        return $this->_rawBody;
    }

    public $_bodyParams;
    /**
     * @inheritdoc
     */
    public function getBodyParams()
    {
        if ($this->_bodyParams === null) {
            if (isset($this->swooleRequest->post[$this->methodParam])) {
                $this->_bodyParams = $this->swooleRequest->post;
                unset($this->_bodyParams[$this->methodParam]);
                return $this->_bodyParams;
            }
            $contentType = $this->getContentType();
            if (($pos = strpos($contentType, ';')) !== false) {
                // e.g. application/json; charset=UTF-8
                $contentType = substr($contentType, 0, $pos);
            }
            if (isset($this->parsers[$contentType])) {
                $parser = Yii::createObject($this->parsers[$contentType]);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The '$contentType' request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $contentType);
            } elseif (isset($this->parsers['*'])) {
                $parser = Yii::createObject($this->parsers['*']);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The fallback request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $contentType);
            } elseif ($this->getMethod() === 'POST') {
                // PHP has already parsed the body so we have all params in $this->swoole->post
                $this->_bodyParams = $this->swooleRequest->post;
            } else {
                $this->_bodyParams = [];
                mb_parse_str($this->getRawBody(), $this->_bodyParams);
            }
        }
        return $this->_bodyParams;
    }

    private $_queryParams;
    /**
     * Returns the request parameters given in the [[queryString]].
     *
     * This method will return the contents of `$_GET` if params where not explicitly set.
     * @return array the request GET parameter values.
     * @see setQueryParams()
     */
    public function getQueryParams()
    {
        if ($this->_queryParams === null) {
            $this->_queryParams = $this->swooleRequest->get;
        }

        return $this->_queryParams;
    }

    /**
     * Converts `$_COOKIE` into an array of [[Cookie]].
     * @return array the cookies obtained from request
     * @throws InvalidConfigException if [[cookieValidationKey]] is not set when [[enableCookieValidation]] is true
     */
    protected function loadCookies()
    {
        $cookies = [];
        if ($this->enableCookieValidation) {
            if ($this->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
            foreach ($this->getSwooleRequest()->cookie as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $data = Yii::$app->getSecurity()->validateData($value, $this->cookieValidationKey);
                if ($data === false) {
                    continue;
                }
                $data = @unserialize($data);
                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = new Cookie([
                        'name' => $name,
                        'value' => $data[1],
                        'expire' => null,
                    ]);
                }
            }
        } else {
            foreach ($this->getSwooleRequest()->cookie as $name => $value) {
                $cookies[$name] = new Cookie([
                    'name' => $name,
                    'value' => $value,
                    'expire' => null,
                ]);
            }
        }

        return $cookies;
    }

    protected function resolveRequestUri()
    {
        if (isset($this->swooleRequest->server['REQUEST_URI'])) {
            $requestUri = $this->swooleRequest->server['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } else {
            throw new InvalidConfigException('Unable to determine the request URI.');
        }

        return $requestUri;
    }

    /**
     * Returns part of the request URL that is after the question mark.
     * @return string part of the request URL that is after the question mark
     */
    public function getQueryString()
    {
        return isset($this->swooleRequest->server['QUERY_STRING']) ? $this->swooleRequest->server['QUERY_STRING'] : '';
    }

    /**
     * Return if the request is sent via secure channel (https).
     * @return bool if the request is sent via secure channel (https)
     */
    public function getIsSecureConnection()
    {
        return isset($this->swooleRequest->server['HTTPS']) && (strcasecmp($this->swooleRequest->server['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
            || isset($this->swooleRequest->server['HTTP_X_FORWARDED_PROTO']) && strcasecmp($this->swooleRequest->server['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * Returns the server name.
     * @return string server name, null if not available
     */
    public function getServerName()
    {
        return isset($this->swooleRequest->server['SERVER_NAME']) ? $this->swooleRequest->server['SERVER_NAME'] : null;
    }

    /**
     * Returns the server port number.
     * @return int|null server port number, null if not available
     */
    public function getServerPort()
    {
        return isset($this->swooleRequest->server['SERVER_PORT']) ? (int) $this->swooleRequest->server['SERVER_PORT'] : null;
    }

    /**
     * Returns the URL referrer.
     * @return string|null URL referrer, null if not available
     */
    public function getReferrer()
    {
        return isset($this->swooleRequest->server['HTTP_REFERER']) ? $this->swooleRequest->server['HTTP_REFERER'] : null;
    }

    /**
     * Returns the user agent.
     * @return string|null user agent, null if not available
     */
    public function getUserAgent()
    {
        return isset($this->swooleRequest->server['HTTP_USER_AGENT']) ? $this->swooleRequest->server['HTTP_USER_AGENT'] : null;
    }

    /**
     * Returns the user IP address.
     * @return string|null user IP address, null if not available
     */
    public function getUserIP()
    {
        return isset($this->swooleRequest->server['REMOTE_ADDR']) ? $this->swooleRequest->server['REMOTE_ADDR'] : null;
    }

    /**
     * Returns the user host name.
     * @return string|null user host name, null if not available
     */
    public function getUserHost()
    {
        return isset($this->swooleRequest->server['REMOTE_HOST']) ? $this->swooleRequest->server['REMOTE_HOST'] : null;
    }

    /**
     * @return string|null the username sent via HTTP authentication, null if the username is not given
     */
    public function getAuthUser()
    {
        return isset($this->swooleRequest->server['PHP_AUTH_USER']) ? $this->swooleRequest->server['PHP_AUTH_USER'] : null;
    }

    /**
     * @return string|null the password sent via HTTP authentication, null if the password is not given
     */
    public function getAuthPassword()
    {
        return isset($this->swooleRequest->server['PHP_AUTH_PW']) ? $this->swooleRequest->server['PHP_AUTH_PW'] : null;
    }

    private $_port;

    /**
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * @return int port number for insecure requests.
     * @see setPort()
     */
    public function getPort()
    {
        if ($this->_port === null) {
            $this->_port = !$this->getIsSecureConnection() && isset($this->swooleRequest->server['SERVER_PORT']) ? (int) $this->swooleRequest->server['SERVER_PORT'] : 80;
        }

        return $this->_port;
    }


}
