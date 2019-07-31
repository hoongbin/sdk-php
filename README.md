# betadata
## 版本
|版本 |时间|
| ----- | ----- |
|0.1.0| 2019年7月30日

## 安装
通过composer安装

```php
composer require hoongbin/sdk-php "dev-master"
```
或添加配置到项目目录下的composer.json

```
"require": {
...
"hoongbin/sdk-php": "dev-master",
...
}
```

## 配置
在组件配置中配置

```php
'betadata' => [
            'class' => 'hoongbin\sdkphp\BetaData',
            'appId' => '182348345923',
            'token' => 'ca6e7e122466f2d3062633cd0de8c751',
            'project' => 'moego',
            'options' => [
                'url' => 'http://api.betadata.io/tracks',
            ],
            'on beforeProperties' => function ($event) {
                $beforeProperties = $event->sender;
                // 获取请求头信息
                $header = isset(Yii::$app->request->headers) ? Yii::$app->request->headers : null;

                // 数据组装
                $beforeProperties->properties['event_properties'] = [
                    // 设备号
                    '_device_id' => isset($header['dd']) ? $header['dd'] : null,
                    // 操作系统
                    '_os' => isset($header['o']) ? $header['o'] : null,
                    // 操作系统版本
                    '_os_version' => isset($header['sv']) ? $header['sv'] : null,
                    // 应用的版本
                    '_app_version' => isset($header['n']) ? $header['n'] : null,
                    // 屏幕宽度
                    '_screen_width' => isset($header['w']) ? (int)$header['w'] : null,
                    // 屏幕高度
                    '_screen_height' => isset($header['h']) ? (int)$header['h'] : null,
                    // 设备型号
                    '_model' => isset($header['m']) ? $header['m'] : null,
                    // 设备制造商
                    '_manufacturer' => isset($header['ma']) ? $header['ma'] : null,
                    // 网络类型
                    '_network_type' => isset($header['l']) ? $header['l'] : null,
                    // 页面地址
                    '_url' => isset(Yii::$app->request->url) ? Yii::$app->request->getHostInfo() . Yii::$app->request->url : null,
                    // 客户端IP
                    '_ip' => isset(Yii::$app->request->userIP) ? Yii::$app->request->userIP : null
                ];
            }
        ],
```

## 使用
```php
Yii::$app->betadata->track("_app_install", ["label" => "安装"], ['uid' => 123456]);
```
