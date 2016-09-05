<?php
namespace Feather2\Blade;

use Feather2\Resource;
use Illuminate\View;

class Factory extends View\Factory{
    public function getRenderCount(){
        return $this->renderCount;
    }
}
