<?php
declare(strict_types=1);

namespace AutoRoute\Value;

class Id
{
    public function __construct(protected int $id)
    {
        $this->id = $id;
    }

    public function get() : int
    {
        return $this->id;
    }
}
