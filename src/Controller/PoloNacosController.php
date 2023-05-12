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

    public function del()
    {
        $nacos = $this->container->get(OperateNacos::class);
        $nacos->delServiceNacos();
    }
}
