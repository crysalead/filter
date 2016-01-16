<?php
namespace Lead\Filter\Spec\Fixture\Jit\Patcher;

class Parrot2
{
    public function tell($message)
    {
        return $message;
    }

    public static function say($message)
    {
        return $message;
    }
}