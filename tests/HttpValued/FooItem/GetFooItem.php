<?php
namespace AutoRoute\HttpValued\FooItem;

use AutoRoute\Value\Id;

class GetFooItem extends FooItem
{
    public function __invoke(Id $id)
    {
    }
}
