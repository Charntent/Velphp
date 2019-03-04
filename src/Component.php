<?php
namespace vel;

// +----------------------------------------------------------------------
// | VelPHP [ WE CAN DO IT JUST Vel ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2018 http://velphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: VelGe <VelGe@gmail.com>
// +----------------------------------------------------------------------



class Component extends BaseObject
{

    // 状态值
    const STATUS_ORIGINAL = 0;
    const STATUS_READY = 1;
    const STATUS_RUNNING = 2;

    // 组件状态
    private $_status = self::STATUS_ORIGINAL;

    // 获取状态
    public function getStatus()
    {
        return $this->_status;
    }

    // 设置状态
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    // 初始化事件
    public function onInitialize()
    {
        parent::onInitialize();
        $this->setStatus(self::STATUS_READY);
    }

    // 请求开始事件
    public function onRequestStart()
    {
        $this->setStatus(self::STATUS_RUNNING);
    }

    // 请求结束事件
    public function onRequestEnd()
    {
        $this->setStatus(self::STATUS_READY);
    }

}
