# hyperf-nacos
用于容器平滑下线操作

#下载包后配置

```php
#config/app.php
return [
    //增加该配置
    'fengdangxing' => [
            'nacos' => [
                'namespaceId' => 'ffffkk',//nacos 命名空间
                'cache' => true,//是否启用redis 缓存节点
                'cacheKey' => 'key:rpc_nodes_%s',//用redis 缓存节点 key值 
                'hashKey' => 'TqGvAmpbJX6XttMsFJrDw7F',//增加密钥值
                'periodSeconds' => 60,//容器缓冲时间(k8s默认30s)
                'preStopSleep' => 60,//容器摧毁前增加时间默认30s+preStopSleep  容器将会等待30+preStopSleep 的时候后真实摧毁pod
            ]
        ]
];
#配置路径 config/annotations.php 没有改文件新建
return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
            BASE_PATH . '/vendor/fengdangxing',//增加该配置
        ],
        'ignore_annotations' => [
            'mixin',
            'Notes',
            'Author',
            'Data',
            'Date'
        ],
    ],
];
```
#WEB_PATH根目录 容器配置摧毁前运行脚本  http转端口 请一定开启http—server服务
```bash
sh WEB_PATH/vendor/fengdangxing/hyperf-nacos/del_nacos_service.sh 9501
或者把脚本复制到根目录
sh WEB_PATH/del_nacos_service.sh 9501
```

#如果不是nacos注册的微服务-需要直接使用信号
```bash
sh WEB_PATH/vendor/fengdangxing/hyperf-nacos/set_sigterm.sh 9501 hashKey
或者把脚本复制到根目录
sh WEB_PATH/set_sigterm.sh 9501 hashKey
```

#增加信号处理-防止后台进程任务继续执行操作（必须开启redis）
```php
#该脚本必须移动到 项目根目录 del_worker_process.sh
//rabbitMq
getContainer()->get(OperateNacos::class)->disposeSigterm($this->getQueue());//所有消费Consumer-或者队列名称 消费者 demo-service.Consumer-demo.build.queue.0
//task
getContainer()->get(OperateNacos::class)->disposeSigterm('crontab-dispatcher');//所有定时任务或者定时任务名称 模糊匹配
```