<?php
namespace AutoRoute\HttpSuffix\FooItem\Variadic;

class GetFooItemVariadicAction
{
    public function __invoke(int $id, string ...$more)
    {
    }
}
