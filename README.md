# feather2-blade

lothar环境运行时必要文件
此包对blade进行了一些扩展，可独立于laravel运行，[独立的blade包传送](https://github.com/jenssegers/blade)，支持blade及laravel版本为5.0+，同时更好的支持了插件机制。

### 安装

```sh
composer require feather2-blade
composer require feather2-resource
```

#### laravel

项目config/view.php配置
```php
<?php
return [
    'paths' => [],
    'compiled' => '缓存存放路径'
];
```

项目providers配置 config/app.php
```php
<?php
return [
    'providers' => [
        //some some some provider
        'Feather2\Blade\ResourceProvider'
    ]
];
```

#### blade独立包
