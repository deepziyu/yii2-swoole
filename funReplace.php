<?php
/**
 * \call_user_func() is not allow in swoole-cotroutine of v2.0.10-stable .
 * so we must replace them by \Swoole\Coroutine::call_user_func().
 * @see https://wiki.swoole.com/wiki/page/p-coroutine.html
 */
namespace deepziyu\yii {
    function func_array(...$params)
    {
        if(is_string($params[0])){
            //var_dump($params);
            return \call_user_func_array(...$params);
        }
        return \Swoole\Coroutine::call_user_func_array(...$params);
    }

    function func(...$params)
    {
        if(is_string($params[0])){
            //var_dump($params);
            return \call_user_func(...$params);
        }
        return \Swoole\Coroutine::call_user_func(...$params);
    }
}
namespace yii {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\base {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\behaviors {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\caching {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\captcha {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\console {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\console\controllers {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\data {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\db {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\di {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\filters {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\filters\auth {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\grid {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\helper {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\i18n {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\log {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\mail {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\mutex {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\rbac {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\validators {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\views {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\web {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\widgets {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}

/**
 *
 */
namespace yii\gii {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\components {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\console {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\controllers {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\controller {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\crud {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\extension {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\form {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\model {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace yii\gii\generators\module {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}

/**
 *
 */
namespace deepziyu\yii {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\bootstrap {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\cache {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\coroutine {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\db {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\di {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\helper {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\log {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\pool {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\redis {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}
namespace deepziyu\yii\swoole\web {
    function call_user_func_array(...$params)
    {
        return \deepziyu\yii\func_array(...$params);
    }

    function call_user_func(...$params)
    {
        return \deepziyu\yii\func(...$params);
    }
}