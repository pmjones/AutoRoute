<?php
declare(strict_types=1);

namespace AutoRoute;

use LogicException;

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

        $expect = json_encode($expect);
        $actual = json_encode($route);
        $this->assertSame($expect, $actual);
    }

    public function testJsonEncode()
    {
        $route = new Route(
            'FooClass',
            '__invoke',
            ['arg0', 'arg1'],
            LogicException::CLASS,
            new LogicException('fake message', 88),
        );

        $actual = json_decode(json_encode($route));
        $this->assertSame('FooClass', $actual->class);
        $this->assertSame('__invoke', $actual->method);
        $this->assertSame(['arg0', 'arg1'], $actual->arguments);
        $this->assertSame(LogicException::CLASS, $actual->error);
        $this->assertSame(LogicException::CLASS, $actual->exception->class);
    }
}
