<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\Http\FooItem\Add\GetFooItemAdd;
use AutoRoute\Http\FooItem\Edit\GetFooItemEdit;
use AutoRoute\Http\FooItem\Extras\GetFooItemExtras;
use AutoRoute\Http\FooItem\GetFooItem;
use AutoRoute\Http\FooItem\Variadic\GetFooItemVariadic;
use AutoRoute\Http\FooItems\Archive\GetFooItemsArchive;
use AutoRoute\Http\FooItems\GetFooItems;
use AutoRoute\Http\Repo\GetRepo;
use AutoRoute\Http\Repo\Issue\Comment\GetRepoIssueComment;
use AutoRoute\Http\Repo\Issue\Comment\Add\GetRepoIssueCommentAdd;
use AutoRoute\Http\Repo\Issue\GetRepoIssue;

use AutoRoute\Http\Get;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    protected $router;

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\\Http',
            __DIR__ . DIRECTORY_SEPARATOR . 'Http'
        );

        $autoRoute->setBaseUrl('/api/');
        $this->router = $autoRoute->newRouter();
    }

    public function testDeepPaths()
    {
        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route');
        $this->assertSame(GetRepo::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route'], $route->params);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11');
        $this->assertSame(GetRepoIssue::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->params);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11/comment/22');
        $this->assertSame(GetRepoIssueComment::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11, 22], $route->params);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11/comment/add');
        $this->assertSame(GetRepoIssueCommentAdd::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->params);
    }

    public function testHappyPaths()
    {
        $route = $this->router->route('GET', '/api/foo-item/add');
        $this->assertSame(GetFooItemAdd::CLASS, $route->class);
        $this->assertSame([], $route->params);

        $route = $this->router->route('GET', '/api/foo-item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->params);

        $route = $this->router->route('GET', '/api/foo-item/1/edit');
        $this->assertSame(GetFooItemEdit::CLASS, $route->class);
        $this->assertSame([1], $route->params);
    }

    public function testWithoutBaseUrl()
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\Http\\',
            __DIR__ . '/Http/'
        );

        $router = $autoRoute->newRouter();

        $route = $router->route('GET', '/foo-item/1/edit');
        $this->assertSame(GetFooItemEdit::CLASS, $route->class);
        $this->assertSame([1], $route->params);

        $route = $router->route('GET', '/foo-item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->params);
    }

    public function testIncorrectBaseUrl()
    {
        $this->expectException(NotFound::CLASS);
        $this->expectExceptionMessage("Expected base URL /api, actually /wro");
        $route = $this->router->route('GET', '/wrong-base-url/foo-item/1/edit');
    }

    public function testInvalidNamespace()
    {
        $this->expectException(InvalidNamespace::CLASS);
        $this->expectExceptionMessage("Not a known namespace: AutoRoute\Http\Admin\NoSuchUrl");
        $this->router->route('GET', '/api/admin/no-such-url');
    }

    public function testInvalidNamespace_emptySegment()
    {
        $this->expectException(InvalidNamespace::CLASS);
        $this->expectExceptionMessage('Cannot convert empty segment to namespace part');
        $this->router->route('GET', '/api/admin//dashboard');
    }

    public function testInvalidNamepace_dotsNotAllowed()
    {
        $this->expectException(InvalidNamespace::CLASS);
        $this->expectExceptionMessage("Directory dots not allowed in segments");
        $this->router->route('GET', '/api/../etc/passwd');
    }

    public function testInvalidNamespace_tailSegment()
    {
        $this->expectException(InvalidNamespace::CLASS);
        $this->expectExceptionMessage("Not a known namespace: AutoRoute\Http\FooItem\NoSuchUrl");
        $this->router->route('GET', '/api/foo-item/1/no-such-url');
    }

    public function testInvalidNamespace_tooManySegments()
    {
        $this->expectException(InvalidNamespace::CLASS);
        $this->expectExceptionMessage("Not a known namespace: AutoRoute\Http\FooItem\\2");
        $this->router->route('GET', '/api/foo-item/1/2/3/edit');
    }

    public function testMethodNotAllowed()
    {
        $this->expectException(MethodNotAllowed::CLASS);
        $this->expectExceptionMessage('PUT action not found in namespace AutoRoute\Http\FooItem');
        $this->router->route('PUT', '/api/foo-item');
    }

    public function testNotEnoughSegments()
    {
        $this->expectException(NotFound::CLASS);
        $this->expectExceptionMessage('Not enough segments for AutoRoute\Http\FooItem\Edit\GetFooItemEdit::__invoke()');
        $this->router->route('GET', '/api/foo-item/edit');
    }

    public function testTooManySegments()
    {
        $this->expectException(NotFound::CLASS);
        $this->expectExceptionMessage('Too many router segments for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke()');
        $this->router->route('GET', '/api/foo-item/1/extras/1/2.0/bar/baz/true/a,b,c/one-too-many');
    }

    public function testOptionalParams()
    {
        $route = $this->router->route('GET', '/api/foo-items');
        $this->assertSame(GetFooItems::CLASS, $route->class);
        $this->assertSame([], $route->params);

        $route = $this->router->route('GET', '/api/foo-items/2');
        $this->assertSame(GetFooItems::CLASS, $route->class);
        $this->assertSame([2], $route->params);

        $route = $this->router->route('GET', '/api/foo-items/archive');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([], $route->params);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979], $route->params);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979/11');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979, 11], $route->params);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979/11/07');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979, 11, 7], $route->params);
    }

    public function testRoot()
    {
        $route = $this->router->route('GET', '/api');
        $this->assertSame(Get::CLASS, $route->class);
    }

    public function testVariadicParam()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/variadic/bar/baz/dib');
        $this->assertSame(GetFooItemVariadic::CLASS, $route->class);
        $this->assertSame([1, 'bar', 'baz', 'dib'], $route->params);
    }

    public function testParamCasting()
    {
        // true
        $route = $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/true/5,6,7');
        $this->assertSame(GetFooItemExtras::CLASS, $route->class);
        $this->assertSame([1, 2.0, '3', '4', true, ['5', '6', '7']], $route->params);

        // false
        $route = $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/false/5,6,7');
        $this->assertSame(GetFooItemExtras::CLASS, $route->class);
        $this->assertSame([1, 2.0, '3', '4', false, ['5', '6', '7']], $route->params);
    }

    public function testBadIntParam()
    {
        $this->expectException(InvalidArgument::CLASS);
        $this->expectExceptionMessage("Expected numeric integer argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 0 (\$id), actually 'z'");
        $this->router->route('GET', '/api/foo-item/z/extras/2/3/4/true/5,6,7');
    }

    public function testBadFloatParam()
    {
        $this->expectException(InvalidArgument::CLASS);
        $this->expectExceptionMessage("Expected numeric float argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 1 (\$foo), actually 'z'");
        $this->router->route('GET', '/api/foo-item/1/extras/z/3/4/true/5,6,7');
    }

    public function testBadBoolParam()
    {
        $this->expectException(InvalidArgument::CLASS);
        $this->expectExceptionMessage("Expected boolean-equivalent argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 4 (\$dib), actually 'z'");
        $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/z/5,6,7');
    }

    public function testEmptyParamEarly()
    {
        $this->expectException(InvalidArgument::CLASS);
        $this->expectExceptionMessage("Expected non-blank argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 0 (\$id), actually ' '");
        $this->router->route('GET', '/api/foo-item/ /extras/2/3/4true//5,6,7');
    }

    public function testEmptyParamLater()
    {
        $this->expectException(InvalidArgument::CLASS);
        $this->expectExceptionMessage("Expected non-blank argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 1 (\$foo), actually ' '");
        $this->router->route('GET', '/api/foo-item/1/extras/ /3/4/true/5,6,7');
    }
}
