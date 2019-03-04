<?php
namespace vel;
use vel\Component;
// +----------------------------------------------------------------------
// | VelPHP [ WE CAN DO IT JUST Vel ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2018 http://velphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: VelGe <VelGe@gmail.com>
// +----------------------------------------------------------------------

use vel\View;

class Error extends Component
{

    // 格式值
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';

    // 输出格式
    public $format = self::FORMAT_HTML;

    // 注册异常处理
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler(['vel\Error', 'appError']);
        set_exception_handler(['vel\Error', 'appException']);
        register_shutdown_function(['vel\Error', 'appShutdown']);
    }

    // Error Handler
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        throw new \vel\exceptions\ErrorException($errno, $errstr, $errfile, $errline);
    }

    // Error Handler
    public static function appShutdown()
    {
        if ($error = error_get_last()) {
            self::appException(new \vel\exceptions\ErrorException($error['type'], $error['message'], $error['file'], $error['line']));
        }
    }

    // Exception Handler
    public static function appException($e)
    {
        // debug处理 & exit处理
        if ($e instanceof \vel\exceptions\DebugException || $e instanceof \vel\exceptions\EndException) {
            \Vel::app()->response->content = $e->getMessage();
            \Vel::app()->response->send();
            \Vel::app()->cleanComponents();
            return;
        }
        // 错误参数定义
        $statusCode = $e instanceof \vel\exceptions\NotFoundException ? 404 : 500;
        $errors     = [
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'type'    => get_class($e),
            'trace'   => $e->getTraceAsString(),
        ];
        // 日志处理
        if (isset(\Vel::app()->components['log']) && !($e instanceof \vel\exceptions\NotFoundException)) {
            $time    = date('Y-m-d H:i:s');
            $message = "[time] {$time}" . PHP_EOL;
            $message .= "[code] {$errors['code']}" . PHP_EOL;
            $message .= "[message] {$errors['message']}" . PHP_EOL;
            $message .= "[type] {$errors['type']}" . PHP_EOL;
            $message .= "[file] {$errors['file']} line {$errors['line']}" . PHP_EOL;
            $message .= "[trace] {$errors['trace']}" . PHP_EOL;
            $message .= '$_SERVER' . substr(print_r($_SERVER, true), 5);
            $message .= '$_GET' . substr(print_r(\Vel::app()->request->get(), true), 5);
            $message .= '$_POST' . substr(print_r(\Vel::app()->request->post(), true), 5);
            \Vel::app()->log->error($message);
        }
        // 清空系统错误
        ob_get_contents() and ob_clean();
        // 错误响应
        if (!VEL_DEBUG) {
            if ($statusCode == 404) {
                $errors = [
                    'code'    => 404,
                    'message' => $e->getMessage(),
                ];
            }
            if ($statusCode == 500) {
                $errors = [
                    'code'    => 500,
                    'message' => '服务器内部错误',
                ];
            }
        }

        echo '<pre>';
        exit();
        $format                           = \Vel::app()->error->format;
        $tpl                              = [
            404 => "errors.{$format}.not_found",
            500 => "errors.{$format}.internal_server_error",
        ];
        $content                          = (new View())->render($tpl[$statusCode], $errors);
        \Vel::app()->response->statusCode = $statusCode;
        \Vel::app()->response->content    = $content;
        switch ($format) {
            case self::FORMAT_HTML:
                \Vel::app()->response->setHeader('Content-Type', 'text/html;charset=utf-8');
                break;
            case self::FORMAT_JSON:
                \Vel::app()->response->setHeader('Content-Type', 'application/json;charset=utf-8');
                break;
            case self::FORMAT_XML:
                \Vel::app()->response->setHeader('Content-Type', 'text/xml;charset=utf-8');
                break;
        }
        \Vel::app()->response->send();
        \Vel::app()->cleanComponents();
    }

    // 手动处理异常
    public function exception($e)
    {
        self::appException($e);
    }

}
