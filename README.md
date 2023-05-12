# hyperf-nacos
用于容器平滑下线操作

#下载包后配置

```php
#config/app.php
return [
    //增加该配置
    'fengdangxing' => [
            'nacos' => [
                'namespaceId' => 'ffffkk',
                'cache' => false,
                'cacheKey' => 'key:rpc_nodes_%s',
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
sh WEB_PATH+/vendor/fengdangxing/hyperf-nacos/del_nacos_service.sh 9501
```


