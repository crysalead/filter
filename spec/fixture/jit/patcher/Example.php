<?php
namespace filter\spec\fixture\jit\patcher;

class Example
{
    public function classic($min, $max = 10)
    {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    }

    public static function classicStatic($min, $max = 10)
    {
        $rand = rand($min, $max);
        $rand++;
        return $rand;
    }

    public function noParams()
    {
        $rand = rand(2, 5);
        $rand++;
        return $rand;
    }

    public function closure()
    {
        return function() {
            $rand = rand(2, 5);
            $rand++;
            return $rand;
        };
    }

    abstract public function abstractMethod();
}
