<?php
namespace filter\spec\fixture\jit\patcher;

use string\String;

trait ExampleTrait
{
    protected function dump()
    {\filter\Filter::on(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($chain) {
        return String::dump('Hello');
    });}
}
