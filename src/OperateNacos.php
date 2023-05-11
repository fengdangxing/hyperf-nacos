<?php

namespace Fengdangxing\HyperfNacos;

use App\Log\Log;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nacos\Application;
use Hyperf\ServiceGovernance\IPReaderInterface;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;

class OperateNacos
{
    /**
     * @inject
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var IPReaderInterface
     */
    protected $ipReader;

    const CACHE_RPC_NODES = 'cms3:rpc_nodes_%s';

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

        $hostListPort = $this->getNodeServiceHostPort($client);
        $this->delServiceName($client, $hostListPort);
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
                $hostListPort[$ip . ':' . $port][] = $serviceName;
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
        $namespaceId = env('NAMESPACE_PREFIX', '');

        $port = (int)env('JSON_PORT');
        $ipPort = $ip . ':' . $port;
        $serviceNames = $hostListPort[$ipPort];
        foreach ($serviceNames as $serviceName) {
            $response = $client->instance->delete($serviceName, $groupName, $ip, $port, [
                'clusterName' => $cluster,
                'namespaceId' => $namespaceId,
                'ephemeral' => $ephemeral,
            ]);
            $this->delCache($serviceName);
            if ($response->getStatusCode() === 200) {
                Log::debug(sprintf('删除服务-Instance %s:%d deleted successfully!', $ip, $port));
            } else {
                Log::error(sprintf('删除服务-Instance %s:%d deleted failed!', $ip, $port));
            }
        }
    }


    private function delCache($serviceName)
    {
        $key = sprintf(self::CACHE_RPC_NODES, md5($serviceName . env('NAMESPACE_PREFIX')));
        RedisHelper::init()->del($key);
    }
}