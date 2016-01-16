<?php
namespace Lead\Filter\Spec\Fixture\Jit\Patcher;

use Lead\Text\Text;

trait ExampleTrait
{
    protected function dump()
    {
        return String::dump('Hello');
    }
}
