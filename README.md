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
                'cache' => false,//是否启用redis 缓存节点
                'cacheKey' => 'key:rpc_nodes_%s',//用redis 缓存节点 key值 
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

#增加信号处理-防止后台进程任务继续执行操作（必须开启redis）
```php
//rabbitMq
getContainer()->get(OperateNacos::class)->disposeSigterm('Consumer-');//消费者 demo-service.Consumer-demo.build.queue.0
//task
getContainer()->get(OperateNacos::class)->disposeSigterm('crontab-dispatcher');//定时任务名称模糊匹配
```

