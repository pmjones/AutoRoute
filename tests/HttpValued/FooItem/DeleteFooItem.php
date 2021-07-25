<?php
namespace AutoRoute\HttpValued\FooItem;

use AutoRoute\Value\Id;

class DeleteFooItem extends FooItem
{
    public function __invoke(Id $id)
    {
    }
}
