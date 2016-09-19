<?php
namespace Feather2\Blade;

use Feather2\Resource;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ViewServiceProvider;

class ResourceProvider extends ViewServiceProvider{
    const COMPILER_PLUGINS_STR = 'blade_plugin_%s';

    public function register()
    {
        parent::register();
        $this->registerFeatherResource();
    }

    public function registerFeatherResource()
    {
        $this->app->singleton('feather.resource', function ($app) {
            $config = $app['config']['view'];
            $config['cacheDir'] = $config['compiled'] . '/feather';

            if (!isset($config['cache'])) {
                $config['cache'] = true;
            }

            return new Resource\Resources($config['paths'], $config);
        });
    }

    //rewrite parent, support more compilers and functions
    public function registerBladeEngine($resolver)
    {
        parent::registerBladeEngine($resolver);

        $this->app->singleton('blade.compiler', function ($app) {
            $cache = $app['config']['view.compiled'];
                
            //if blade compiler support directive, use it.
            if (method_exists('Illuminate\\View\\Compilers\\BladeCompiler', 'directive')) {
                $compiler = new BladeCompiler($app['files'], $cache);
            } else {
                //compatible
                $compiler = new Compiler($app['files'], $cache);
            }

            //load all plugins
            foreach ((array)$app['config']['view.paths'] as $dir) {
                $files = glob($dir . '/_plugins_/**.php');

                foreach ($files as $file) {
                    $name = basename($file, '.php');

                    require $file;
                    
                    $callback = sprintf(self::COMPILER_PLUGINS_STR, $name);
                    function_exists($callback) && $compiler->directive($name, $callback);
                }
            }

            //extends statements
            // $compiler->extend(function ($value, $compiler) {
            //     return preg_replace_callback(self::COMPILER_REGEXP, function ($match) {
            //         $callback = sprintf(self::COMPILER_PLUGINS_STR, $match[1]);
                    
            //         if (function_exists($callback)) {
            //             $match[0] = $callback(Arr::get($match, 3));
            //         }

            //         return isset($match[3]) ? $match[0] : $match[0] . $match[2];
            //     }, $value);
            // });

            return $compiler;
        });
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('view', function ($app) {
            //finally, initialize config to prevent template's directory is wrong.
            $this->initializeConfig();

            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];
            $finder = $app['view.finder'];

            $env = new Factory($resolver, $finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $env->setContainer($app);

            //support our extensions
            if (isset($app['config']['view.suffix'])) {
                $env->addExtension($app['config']['view.suffix'], 'blade');
            }

            //composer feather resource
            $env->composer('*', function ($view) use ($app, $env) {
                //be sure it's not a include or extends file is runing make method
                //prevent don't analyse feather resource when include or extends file is running
                if ($env->getRenderCount() === 1) {
                    //analyse id
                    $id = $path = $view->getPath();

                    foreach ($env->getExtensions() as $ext => $engine) {
                        //if found extension
                        if (strrchr($path, $ext) == $ext) {
                            $id = sprintf('%s.%s', str_replace('.', '/', $view->getName()), $ext);
                            break;
                        }
                    }

                    //analyse feather resource
                    $fData = $app['feather.resource']->getResourcesData($id);
                    //only set feather's common data in current page
                    $env->share($fData);
                }
            });

            return $env;
        });
    }

    //load config in template's directory
    protected function initializeConfig()
    {
        $config = $this->app['config'];

        foreach ((array)$config['view.paths'] as $dir) {
            $file = $dir . '/engine.json';

            if (is_file($file)) {
                $json = json_decode(file_get_contents($file), true);
                $config['view'] = array_merge($config['view'], $json);
                break;
            }
        }

        //rewrite all config
        $this->app['config'] = $config;
    }
}