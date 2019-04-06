<?php
namespace AutoRoute\Http\FooItem\Variadic;

class GetFooItemVariadic
{
    public function __invoke(int $id, string ...$more)
    {
    }
}
