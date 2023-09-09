<?php
namespace Home\Cnsts;

/**
 * 错误码定义
 */
class ERRNO
{
    // 系统内置错误码
    const SUCCESS           = 0;
    const USER_PWD_ERROR = -101;
    const NO_LOGIN = -102;
    const MQ_TOPIC_NOT_EXISTS = -103;

    const ERRNO_DICTS = [
        self::SUCCESS             => "成功",
        self::USER_PWD_ERROR => '用户名或密码错误',
        self::NO_LOGIN => '未登录',
        self::MQ_TOPIC_NOT_EXISTS => '消息队列不存在',
    ];

    /**
     * 获取错误信息
     * @param[in] $module_name 模块名称
     * @param[in] $errno 模块内错误码
     * @return
     * */
    public static function e($errno)
    {
        header("Content-Type:text/html;charset=utf-8");
        if (array_key_exists($errno, self::ERRNO_DICTS)) {
            return self::ERRNO_DICTS[$errno];
        }

        return '不存在的错误码，未知的运行错误！';
    }

}
