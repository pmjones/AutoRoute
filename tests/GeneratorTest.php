<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\Http\Admin\Dashboard\GetAdminDashboard;
use AutoRoute\Http\FooItem\Add\GetFooItemAdd;
use AutoRoute\Http\FooItem\Edit\GetFooItemEdit;
use AutoRoute\Http\FooItem\Extras\GetFooItemExtras;
use AutoRoute\Http\FooItem\GetFooItem;
use AutoRoute\Http\FooItem\Variadic\GetFooItemVariadic;
use AutoRoute\Http\FooItems\Archive\GetFooItemsArchive;
use AutoRoute\Http\FooItems\GetFooItems;
use AutoRoute\Http\Get;

class GeneratorTest extends \PHPUnit\Framework\TestCase
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

    public function testGenerate()
    {
        $actual = $this->generator->generate(GetFooItemEdit::CLASS, 1);
        $expect = '/api/foo-item/1/edit';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/api/admin/dashboard';
        $this->assertSame($expect, $actual);

        // repeat for coverage
        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/api/admin/dashboard';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemAdd::CLASS);
        $expect = '/api/foo-item/add';
        $this->assertSame($expect, $actual);

        // root
        $actual = $this->generator->generate(Get::CLASS);
        $expect = '/api';
        $this->assertSame($expect, $actual);

        // parameter types
        $actual = $this->generator->generate(
            GetFooItemExtras::CLASS,
            1,
            2.3,
            'bar',
            'baz',
            true,
            ['a', 'b', 'c']
        );
        $expect = '/api/foo-item/1/extras/2.3/bar/baz/1/a,b,c';
        $this->assertSame($expect, $actual);

        // variadics
        $actual = $this->generator->generate(
            GetFooItemVariadic::CLASS,
            1,
            'foo',
            'bar',
            'baz'
        );
        $expect = '/api/foo-item/1/variadic/foo/bar/baz';
        $this->assertSame($expect, $actual);

        // optionals
        $actual = $this->generator->generate(GetFooItemsArchive::CLASS);
        $expect = '/api/foo-items/archive';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970');
        $expect = '/api/foo-items/archive/1970';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11');
        $expect = '/api/foo-items/archive/1970/11';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11', '07');
        $expect = '/api/foo-items/archive/1970/11/07';
        $this->assertSame($expect, $actual);
    }

    public function testGenerateInvalidNamespace()
    {
        $this->expectException(Exception\InvalidNamespace::CLASS);
        $this->expectExceptionMessage('Expected namespace AutoRoute\Http\, actually Mismatched\Namespace\GetFooItemEdit');
        $this->generator->generate('Mismatched\Namespace\GetFooItemEdit');
    }

    public function testGenerateNoSuchClass()
    {
        $this->expectException(Exception\NotFound::CLASS);
        $this->expectExceptionMessage('Expected class AutoRoute\Http\NoSuchAction, actually not found');
        $this->generator->generate(\AutoRoute\Http\NoSuchAction::CLASS);
    }

    public function testGenerateTooManySegments()
    {
        $this->expectException(Exception\NotFound::CLASS);
        $this->expectExceptionMessage('Too many arguments provided for AutoRoute\Http\FooItem\Extras\GetFooItemExtras');
        $this->generator->generate(
            GetFooItemExtras::CLASS,
            1,
            2.3,
            'bar',
            'baz',
            true,
            ['a', 'b', 'c'],
            'one-too-many'
        );
    }

    public function testGenerateNotEnoughSegments()
    {
        $this->expectException(Exception\InvalidArgument::CLASS);
        $this->expectExceptionMessage('Expected non-blank argument for AutoRoute\Http\FooItem\Edit\GetFooItemEdit::__invoke() parameter 0 ($id), actually NULL');
        $this->generator->generate(GetFooItemEdit::CLASS);
    }

    public function testGenerate_noBaseUrl()
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\\Http',
            __DIR__ . DIRECTORY_SEPARATOR . 'Http'
        );

        $generator = $autoRoute->getGenerator();

        $actual = $generator->generate(GetFooItemEdit::CLASS, 1);
        $expect = '/foo-item/1/edit';
        $this->assertSame($expect, $actual);

        $actual = $generator->generate(Get::CLASS);
        $expect = '/';
        $this->assertSame($expect, $actual);
    }
}
