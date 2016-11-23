<?php
namespace Feather2\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

//5.1中直接支持了此类中所提供的一切方法
//but 5.1以下版本不支持，原本可通过extend去做扩展，但是extend会多一次编译，没有此方法的性能高
class Compiler extends BladeCompiler{
    /**
     * All custom "directive" handlers.
     *
     * This was implemented as a more usable "extend" in 5.1.
     *
     * @var array
     */
    protected $customDirectives = [];

    /**
     * Compile the view at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        $contents = $this->compileString($this->files->get($this->getPath()));

        if (!is_null($this->cachePath)) {
            $this->files->put($this->getCompiledPath($this->getPath()), $contents);
        }
    }

    /**
     * Compile the given Blade template contents.
     *
     * @param  string  $value
     * @return string
     */
    public function compileString($value)
    {
        $result = '';
        $this->footer = array();

        // Here we will loop through all of the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.
        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }

        // If there are any footer lines that need to get added to a template we will
        // add them here at the end of the template. This gets used mainly for the
        // template inheritance via the extends keyword that should be appended.
        if (count($this->footer) > 0) {
            $result = ltrim($result, PHP_EOL)
                    .PHP_EOL.implode(PHP_EOL, array_reverse($this->footer));
        }

        return $result;
    }

    /**
     * Register a handler for custom directives.
     *
     * @param  string  $name
     * @param  callable  $handler
     * @return void
     */
    public function directive($name, callable $handler)
    {
        $this->customDirectives[$name] = $handler;
    }

    /**
     * Call the given directive with the given value.
     *
     * @param  string  $name
     * @param  string|null  $value
     * @return string
     */
    protected function callCustomDirective($name, $value)
    {
        if (Str::startsWith($value, '(') && Str::endsWith($value, ')')) {
            $value = substr($value, 1, -1);
        }

        return call_user_func($this->customDirectives[$name], trim($value));
    }

    /**
     * Compile Blade statements that start with "@".
     *
     * @param  string  $value
     * @return mixed
     */
    protected function compileStatements($value)
    {
        $callback = function ($match) {
            if (isset($this->customDirectives[$match[1]])) {
                $match[0] = $this->callCustomDirective($match[1], Arr::get($match, 3));
            } elseif (method_exists($this, $method = 'compile'.ucfirst($match[1]))) {
                $match[0] = $this->$method(Arr::get($match, 3));
            }

            return isset($match[3]) ? $match[0] : $match[0].$match[2];
        };

        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $value);
    }
}