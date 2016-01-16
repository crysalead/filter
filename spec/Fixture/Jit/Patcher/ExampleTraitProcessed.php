<?php
namespace Lead\Filter\Spec\Fixture\Jit\Patcher;

use Lead\Text\Text;

trait ExampleTrait
{
    protected function dump()
    {return \Lead\Filter\Filters::run(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($next) {
        return String::dump('Hello');
    });}
}
