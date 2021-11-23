<?php
declare(strict_types=1);

namespace AutoRoute;

class Helper
{
    public function __construct(protected Generator $generator)
    {
    }

    public function __invoke(string $class, ...$values) : string
    {
        return $this->generator->generate($class, ...$values);
    }
}
