<?php
namespace filter\spec\fixture\jit\patcher;

class Example
{
    public function classic($min, $max = 10)
    {\filter\Filter::on(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($chain, $min, $max) {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    });}

    public static function classicStatic($min, $max = 10)
    {\filter\Filter::on(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($chain, $min, $max) {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    });}

    public function noParams()
    {\filter\Filter::on(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($chain) {
        $rand = rand(2, 5);
        $rand++;
        return $rand;
    });}

    public function closure()
    {\filter\Filter::on(isset($this) ? $this : get_called_class(), __FUNCTION__, func_get_args(), function ($chain) {
        return function() {
            $rand = rand(2, 5);
            $rand++;
            return $rand;
        };
    });}

    abstract public function abstractMethod();
}
