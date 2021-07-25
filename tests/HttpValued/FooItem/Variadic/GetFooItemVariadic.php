<?php
namespace AutoRoute\HttpValued\FooItem\Variadic;

class GetFooItemVariadic
{
    public function __invoke(int $id, string ...$more)
    {
    }
}
