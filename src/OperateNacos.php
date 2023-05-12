<?php

namespace Fengdangxing\HyperfNacos;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Application;
use Hyperf\ServiceGovernance\IPReaderInterface;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Fengdangxing\HyperfRedis\RedisHelper;

class OperateNacos
{
    /**
     * @inject
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var IPReaderInterface
     */
    protected $ipReader;

    public $cacheRedisKey = 'key:rpc_nodes_%s';
    public $namespaceId;
    public $isCache = false;

    public function __construct()
    {
        $config = $this->container->get(ConfigInterface::class);
        $this->namespaceId = $config->get("app.fengdangxing.nacos.namespaceId") ? $config->get("app.fengdangxing.nacos.namespaceId") : env('NAMESPACE_PREFIX', '');
        if (empty($this->namespaceId)) {
            throw new \Exception("namespaceId empty");
        }
        $this->isCache = $config->get("app.fengdangxing.nacos.cache") ? $config->get("app.fengdangxing.nacos.cache") : false;
        $this->cacheRedisKey = $config->get("app.fengdangxing.nacos.cacheKey") ? $config->get("app.fengdangxing.nacos.cacheKey") : $this->cacheRedisKey;
    }

    public function getOneService()
    {

    }

    public function delServiceNacos()
    {
        $config = $this->container->get(ConfigInterface::class);
        $client = $this->container->get(Application::class);
        $this->ipReader = $this->container->get(IPReaderInterface::class);

        //设置临时心跳为3600 为1小时
        $config->set('services.drivers.nacos.heartbeat', 3600);
        sleep(5);
        $hostListPort = $this->getNodeServiceHostPort($client);
        $this->delServiceName($client, $hostListPort);
    }

    public function setCache()
    {

    }

    private function getNodeServiceHostPort(Application $client): array
    {
        $hostListPort = [];
        //获取所有服务
        $listAll = $client->service->list(1, 1000, '', env('NAMESPACE_PREFIX', ''));
        $list = Json::decode($listAll->getBody()->getContents());
        foreach ($list['doms'] as $serviceName) {
            //获取单服务详情
            $infoJson = $client->instance->list($serviceName, ['namespaceId' => env('NAMESPACE_PREFIX', '')]);
            $info = Json::decode($infoJson->getBody()->getContents());
            //获取服务节点列表
            $hosts = $info['hosts'];
            foreach ($hosts as $hostInfo) {
                $ip = $hostInfo['ip'];
                $port = $hostInfo['port'];
                $hostListPort[$ip][] = array($serviceName, $port);
            }
        }
        return $hostListPort;
    }

    private function delServiceName(Application $client, $hostListPort)
    {
        if (empty($hostListPort)) {
            return;
        }
        $ip = $this->ipReader->read();
        $groupName = '';
        $cluster = '';
        $ephemeral = 'true';
        $ipPort = $ip;
        $services = $hostListPort[$ip];
        foreach ($services as $key => $service) {
            $response = $client->instance->delete((string)$service[0], $groupName, $ip, (int)$service[1], [
                'clusterName' => $cluster,
                'namespaceId' => $this->namespaceId,
                'ephemeral' => $ephemeral,
            ]);
            $this->delCache($service[0]);
        }
    }


    private function delCache($serviceName)
    {
        if ($this->isCache) {
            $key = sprintf($this->cacheRedisKey, md5((string)$serviceName . $this->namespaceId));
            RedisHelper::init()->del($key);
        }
        return true;
    }
}