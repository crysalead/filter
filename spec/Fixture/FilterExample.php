<?php
namespace Lead\Filter\Spec\Fixture;

use Lead\Filter\Filters;

class FilterExample
{
    public function filterable()
    {
        return Filters::run($this, 'filterable', func_get_args(), function($next, $message) {
            return "Hello {$message}";
        });
    }
}
