<?php
namespace filter\spec\fixture\jit\patcher;

use string\String;

trait ExampleTrait
{
    protected function dump()
    {
        return String::dump('Hello');
    }
}
