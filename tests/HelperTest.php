<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\Http\FooItem\Edit\GetFooItemEdit;

class HelperTest extends \PHPUnit\Framework\TestCase
{
    protected $generator;

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            namespace: 'AutoRoute\\Http',
            directory: __DIR__ . DIRECTORY_SEPARATOR . 'Http',
            baseUrl: '/api/',
        );

        $this->generator = $autoRoute->getGenerator();
    }

    public function test()
    {
        $helper = new Helper($this->generator);
        $actual = $helper(GetFooItemEdit::CLASS, 1);
        $expect = '/api/foo-item/1/edit';
        $this->assertSame($expect, $actual);
    }
}
