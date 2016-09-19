# feather2-blade

此包为[lothar](http://github.com/feather-team/lothar)环境运行时必要文件，支持blade及laravel版本为5.0+，同时更好的支持了插件机制。

### laravel

安装
```sh
composer require feather2/blade
composer require feather2/resource  
```

项目config/view.php配置

```php
<?php
return [
    'paths' => [],
    'compiled' => '缓存存放路径',
    'suffix' => 'fuck'  //哪里喜欢点哪里
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

### blade独立包，[传送](https://github.com/jenssegers/blade)

安装
```sh
composer require jenssegers/blade
composer require feather2/blade
composer require feather2/resource  
```

使用
```php
<?php
define('ROOT', dirname(__DIR__));
define('CACHE_ROOT', ROOT . '/cache');
define('VIEW_ROOT', ROOT . '/view');

require ROOT . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Feather2\Blade as BladeProvider;
use Feather2\Resource;

$container = new Container;
$blade = new Blade(VIEW_ROOT, CACHE_ROOT, $container);
$config = $container['config'];
//兼容下独立blade包无法正常读取 view.xx的bug
$config['view'] = [
    'paths' => $blade->viewPaths,
    'compiled' => $blade->cachePath
];
$config['view.suffix'] = 'lala';
$container['config'] = $config;

(new BladeProvider\ResourceProvider($container))->register();
echo $blade->make($path, array(/*页面数据*/))->render();
```

#### 插件开发及使用

直接在view目录下建立一个_plugins_目录即可
```
└── view
    └── _plugins_
        └── datetime.php
```

插件的名字即为文件名

view/_plugins_/datetime.php
```php
function blade_datetime(){
    return '<?php echo date("Y-m-d H:i:s");?>';
}
```

view/main.lala
```php
现在时间: <div id="datetime">@datetime()</div>
```
