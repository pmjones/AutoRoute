<?php
declare(strict_types=1);

namespace AutoRoute;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    public function testAsArray()
    {
        $route = new Route(
            'FooClass',
            '__invoke',
            ['arg0', 'arg1']
        );

        $expect = [
            'class' => 'FooClass',
            'method' => '__invoke',
            'arguments' => [
                'arg0',
                'arg1',
            ],
            'error' => null,
            'exception' => null,
            'headers' => [],
            'messages' => [],
        ];
        $this->assertSame($expect, $route->asArray());
    }
}
