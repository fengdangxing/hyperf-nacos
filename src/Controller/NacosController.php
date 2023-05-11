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

namespace Hyperf\HyperfNacos\Controller;

use Hyperf\HyperfNacos\OperateNacos;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * @AutoController()
 */
class NacosController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    public $container;

    public function del()
    {
        echo '执行到了';
        $nacos = $this->container->get(OperateNacos::class);
        $nacos->delServiceNacos();
    }
}
