<?php


namespace framework\dispatcher;

use framework\core\IView;
use framework\core\IRequestDispatcher;
use framework\core\IController;
use framework\core\Context;
use framework\util\Daemon;

/**
 * IRequestDispatcher的抽象实现，它实现了dispatch方法，
 * 并且定义了getCtrlClassName和getCtrlMethodName两个抽象方法，其子类只需实现这两个方法即可。
 */
abstract class RequestDispatcherBase implements IRequestDispatcher {

    /**
     * 默认动作
     *
     * @var string
     */
    protected $defaultAction;

    protected $daemon = false; //是否开启守护进程

    public function dispatch() {
        if($this->daemon) {
            $this->_daemon();
        } else {
            $this->_dispatch();
        }
    }

    private function _daemon() {
        $deamon = new Daemon($GLOBALS['DAEMON_CONFIG']);
        $deamon->start();
        $this->_dispatch();
    }

    public function _dispatch() {
        $ctrlClass = Context::getCtrlNamespace() . "\\" . $this->getCtrlClassName();
        $ctrlMethod = $this->getCtrlMethodName();

        $ctrl = new $ctrlClass();
        $filtered = false;

        if ($ctrl instanceof IController) {
            $ctrl->setDispatcher($this);
            $filtered = !$ctrl->beforeFilter();
        }

        $exception = null;

        if (!$filtered) {
            try {
                $view = $ctrl->$ctrlMethod();

                if ($view instanceof IView) {
                    $view->display();
                }
            } catch (\Exception $e) {
                $exception = $e;
            }
        }

        if ($ctrl instanceof IController) {
            $ctrl->afterFilter();
        }

        if ($exception != null) {
            throw $exception;
        }
    }

    /**
     * 获取控制器类名
     *
     * @return String
     */
    abstract public function getCtrlClassName();

    /**
     * 获取控制器方法名
     *
     * @return String
     */
    abstract public function getCtrlMethodName();
}