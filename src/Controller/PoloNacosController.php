<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Fengdangxing\HyperfNacos\Controller;

use Fengdangxing\HyperfNacos\OperateNacos;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * @AutoController()
 */
class PoloNacosController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    public $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Notes: 操作直接删除nacos节点
     * @return void
     */
    public function del()
    {
        $nacos = $this->container->get(OperateNacos::class);
        $nacos->delServiceNacos();
    }

    /**
     * @Notes: 直接设置信号
     * @return void
     */
    public function setSigterm()
    {
        $hashKey = $this->request->input('key', '');
        $nacos = $this->container->get(OperateNacos::class);
        $nacos->setSigterm($hashKey);
    }
}
