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

    /**
     * @var Application
     */
    protected $client;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public $cacheRedisKey = 'key:rpc_nodes_%s';
    public $namespaceId;
    public $isCache = false;

    public function __construct()
    {
        $this->ipReader = $this->container->get(IPReaderInterface::class);
        $this->client = $this->container->get(Application::class);
        $this->config = $this->container->get(ConfigInterface::class);

        $this->namespaceId = $this->config->get("app.fengdangxing.nacos.namespaceId") ? $this->config->get("app.fengdangxing.nacos.namespaceId") : env('NAMESPACE_PREFIX', '');
        if (empty($this->namespaceId)) {
            throw new \Exception("namespaceId empty");
        }
        $this->isCache = $this->config->get("app.fengdangxing.nacos.cache") ? $this->config->get("app.fengdangxing.nacos.cache") : false;
        $this->cacheRedisKey = $this->config->get("app.fengdangxing.nacos.cacheKey") ? $this->config->get("app.fengdangxing.nacos.cacheKey") : $this->cacheRedisKey;
    }

    public function getOneNodeService($serviceName)
    {
        $ip = $this->ipReader->read();
        if ($this->isCache) {
            $key = sprintf($this->cacheRedisKey, md5((string)$serviceName . $this->namespaceId));
            $rpcNodes = RedisHelper::init()->get($key);
            echo '11';
            print_r($rpcNodes);
        }
        $list = $this->getNodeServiceHostPort();
        print_r($list);
        return 'http://' . $ip . ':/' . $serviceName . '/';
    }

    public function delServiceNacos()
    {
        //设置临时心跳为3600 为1小时
        $this->config->set('services.drivers.nacos.heartbeat', 3600);
        $this->setSigterm();
        sleep(5);
        $hostListPort = $this->getNodeServiceHostPort();
        $this->delServiceName($hostListPort);
    }

    public function disposeSigterm($wokerName): bool
    {
        if (RedisHelper::init()->get($this->getSigtermKey())) {
            system('sh ' . BASE_PATH . "/del_worker_process.sh " . env('APP_NAME') . '.Manager');
            system('sh ' . BASE_PATH . "/del_worker_process.sh $wokerName");
            return true;
        }
        return false;
    }

    public function setCache(): int
    {

    }

    private function getNodeServiceHostPort(): array
    {
        $hostListPort = [];
        //获取所有服务
        $listAll = $this->client->service->list(1, 1000, '', env('NAMESPACE_PREFIX', ''));
        $list = Json::decode($listAll->getBody()->getContents());
        foreach ($list['doms'] as $serviceName) {
            //获取单服务详情
            $infoJson = $this->client->instance->list($serviceName, ['namespaceId' => env('NAMESPACE_PREFIX', '')]);
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

    private function delServiceName($hostListPort)
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
            $response = $this->client->instance->delete((string)$service[0], $groupName, $ip, (int)$service[1], [
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

    private function setSigterm()
    {
        if ($this->isCache) {
            $key = $this->getSigtermKey();
            RedisHelper::init()->set($key, 1);
            RedisHelper::init()->expire($key, 360);
        }
    }

    private function getSigtermKey(): string
    {
        $ip = $this->ipReader->read();
        $key = sprintf($this->cacheRedisKey, md5($ip));
        return $key;
    }
}