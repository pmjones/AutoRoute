<?php
namespace AutoRoute\HttpIgnore\FooItem\Variadic;

class GetFooItemVariadic
{
    public function exec(\ServerRequest $request, int $id, string ...$more)
    {
    }
}
