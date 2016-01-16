<?php
namespace Lead\Filter\Spec\Fixture\Jit\Patcher;

class Example
{
    public function classic($min, $max = 10)
    {return \Lead\Filter\Filters::run(isset($this) ? $this : get_called_class(), __FUNCTION__, [$min, $max] + func_get_args(), function ($next, $min, $max) {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    });}

    public static function classicStatic($min, $max = 10)
    {return \Lead\Filter\Filters::run(isset($this) ? $this : get_called_class(), __FUNCTION__, [$min, $max] + func_get_args(), function ($next, $min, $max) {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    });}

    public function noParams()
    {return \Lead\Filter\Filters::run(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($next) {
        $rand = rand(2, 5);
        $rand++;
        return $rand;
    });}

    public function closure()
    {return \Lead\Filter\Filters::run(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($next) {
        return function() {
            $rand = rand(2, 5);
            $rand++;
            return $rand;
        };
    });}

    abstract public function abstractMethod();
}
