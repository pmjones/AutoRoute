<?php
declare(strict_types=1);

namespace AutoRoute;

class ReflectorTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $reflector = new Reflector(new Config(
            namespace: 'AutoRoute\\Http',
            directory: __DIR__ . DIRECTORY_SEPARATOR . 'Http',
        ));

        $this->expectException(Exception\NotFound::CLASS);
        $this->expectExceptionMessage("Class not found: NoSuchClass");
        $reflector->getConstructorParameters('NoSuchClass');
    }
}
