<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\Http\FooItem\Add\GetFooItemAdd;
use AutoRoute\Http\FooItem\Add\HeadFooItemAdd;
use AutoRoute\Http\FooItem\Edit\GetFooItemEdit;
use AutoRoute\Http\FooItem\Extras\GetFooItemExtras;
use AutoRoute\Http\FooItem\GetFooItem;
use AutoRoute\Http\FooItem\HeadFooItem;
use AutoRoute\Http\FooItem\Variadic\GetFooItemVariadic;
use AutoRoute\Http\FooItems\Archive\GetFooItemsArchive;
use AutoRoute\Http\FooItems\GetFooItems;
use AutoRoute\Http\Get;
use AutoRoute\Http\Repo\GetRepo;
use AutoRoute\Http\Repo\Issue\Comment\Add\GetRepoIssueCommentAdd;
use AutoRoute\Http\Repo\Issue\Comment\GetRepoIssueComment;
use AutoRoute\Http\Repo\Issue\GetRepoIssue;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    protected $router;

    protected function assertRouteError(
        string $expectClass,
        string $expectMessage,
        Route $actual
    ) {
        $this->assertSame($expectClass, $actual->error);
        $this->assertInstanceOf($expectClass, $actual->exception);
        $this->assertSame($expectMessage, $actual->exception->getMessage());
    }

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            namespace: 'AutoRoute\\Http',
            directory: __DIR__ . DIRECTORY_SEPARATOR . 'Http',
            baseUrl: '/api/',
        );

        $this->router = $autoRoute->getRouter();
    }

    public function testDeepPaths()
    {
        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route');
        $this->assertSame(GetRepo::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route'], $route->arguments);

        $route = $this->router->route('HEAD', '/api/repo/pmjones/auto-route');
        $this->assertSame(GetRepo::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route'], $route->arguments);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11');
        $this->assertSame(GetRepoIssue::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->arguments);

        $route = $this->router->route('HEAD', '/api/repo/pmjones/auto-route/issue/11');
        $this->assertSame(GetRepoIssue::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->arguments);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11/comment/22');
        $this->assertSame(GetRepoIssueComment::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11, 22], $route->arguments);

        $route = $this->router->route('HEAD', '/api/repo/pmjones/auto-route/issue/11/comment/22');
        $this->assertSame(GetRepoIssueComment::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11, 22], $route->arguments);

        $route = $this->router->route('GET', '/api/repo/pmjones/auto-route/issue/11/comment/add');
        $this->assertSame(GetRepoIssueCommentAdd::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->arguments);

        $route = $this->router->route('HEAD', '/api/repo/pmjones/auto-route/issue/11/comment/add');
        $this->assertSame(GetRepoIssueCommentAdd::CLASS, $route->class);
        $this->assertSame(['pmjones', 'auto-route', 11], $route->arguments);

        $route = $this->router->route('GET', '/api/repo/issue/comment/pmjones/auto-route/11/22');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Not a known namespace: AutoRoute\Http\Repo\Pmjones",
            $route
        );
    }

    public function testHappyPaths()
    {
        $route = $this->router->route('GET', '/api/foo-item/add');
        $this->assertSame(GetFooItemAdd::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('HEAD', '/api/foo-item/add');
        $this->assertSame(HeadFooItemAdd::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('HEAD', '/api/foo-item/add');
        $this->assertSame(HeadFooItemAdd::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);

        $route = $this->router->route('HEAD', '/api/foo-item/1');
        $this->assertSame(HeadFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-item/1/edit');
        $this->assertSame(GetFooItemEdit::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);
    }

    public function testWithoutBaseUrl()
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\Http\\',
            __DIR__ . '/Http/'
        );

        $router = $autoRoute->getRouter();

        $route = $router->route('GET', '/foo-item/1/edit');
        $this->assertSame(GetFooItemEdit::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);

        $route = $router->route('GET', '/foo-item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);
    }

    public function testIncorrectBaseUrl()
    {
        $route = $this->router->route('GET', '/wrong-base-url/foo-item/1/edit');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Expected base URL /api, actually /wro",
            $route
        );
    }

    public function testInvalidNamespace()
    {
        $route = $this->router->route('GET', '/api/admin/no-such-url');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Not a known namespace: AutoRoute\Http\Admin\NoSuchUrl",
            $route
        );
    }

    public function testInvalidNamespace_emptySegment()
    {
        $route = $this->router->route('GET', '/api/admin//dashboard');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            'Cannot convert empty segment to namespace part',
            $route
        );
    }

    public function testInvalidNamepace_dotsNotAllowed()
    {
        $route = $this->router->route('GET', '/api/../etc/passwd');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Directory dots not allowed in segments",
            $route
        );
    }

    public function testInvalidNamespace_tailSegment()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/no-such-url');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Not a known namespace: AutoRoute\Http\FooItem\NoSuchUrl",
            $route
        );
    }

    public function testInvalidNamespace_tooManySegments()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/2/3/edit');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "Not a known namespace: AutoRoute\Http\FooItem\\2",
            $route
        );
    }

    public function testInvalidNamespace_notEnoughArguments()
    {
        $route = $this->router->route('GET', '/api/foo-item/');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "AutoRoute\Http\FooItem\GetFooItem needs 1 argument(s), 0 found",
            $route
        );
    }

    public function testClassNotFound_emptyNamespace()
    {
        $route = $this->router->route('GET', '/api/admin/empty');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            "No actions found in namespace AutoRoute\Http\Admin\Empty",
            $route
        );
    }

    public function testClassNotFound_methodNotAllowed()
    {
        $route = $this->router->route('PUT', '/api/foo-item');

        $this->assertRouteError(
            Exception\MethodNotAllowed::CLASS,
            'PUT action not found in namespace AutoRoute\Http\FooItem',
            $route
        );

        $this->assertSame(
            ['allowed' => 'DELETE,GET,HEAD,PATCH,POST'],
            $route->headers
        );
    }

    public function testTooManySegments()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/extras/1/2.0/bar/true/a,b,c/one-too-many');
        $this->assertRouteError(
            Exception\NotFound::CLASS,
            'Too many router segments for AutoRoute\Http\FooItem\Extras\GetFooItemExtras',
            $route
        );
    }

    public function testOptionalParams()
    {
        $route = $this->router->route('GET', '/api/foo-items');
        $this->assertSame(GetFooItems::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-items/2');
        $this->assertSame(GetFooItems::CLASS, $route->class);
        $this->assertSame([2], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-items/archive');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979/11');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979, 11], $route->arguments);

        $route = $this->router->route('GET', '/api/foo-items/archive/1979/11/07');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
        $this->assertSame([1979, 11, 7], $route->arguments);
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
        $this->assertSame([1, 'bar', 'baz', 'dib'], $route->arguments);

        $route = $this->router->route('HEAD', '/api/foo-item/1/variadic/bar/baz/dib');
        $this->assertSame(GetFooItemVariadic::CLASS, $route->class);
        $this->assertSame([1, 'bar', 'baz', 'dib'], $route->arguments);
    }

    public function testParamCasting()
    {
        // true
        $route = $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/true/5,6,7');
        $this->assertSame(GetFooItemExtras::CLASS, $route->class);
        $this->assertSame([1, 2.0, '3', '4', true, ['5', '6', '7']], $route->arguments);

        // false
        $route = $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/false/5,6,7');
        $this->assertSame(GetFooItemExtras::CLASS, $route->class);
        $this->assertSame([1, 2.0, '3', '4', false, ['5', '6', '7']], $route->arguments);
    }

    public function testBadIntParam()
    {
        $route = $this->router->route('GET', '/api/foo-item/z/extras/2/3/4/true/5,6,7');
        $this->assertRouteError(
            Exception\InvalidArgument::CLASS,
            "Expected numeric integer argument for AutoRoute\Http\FooItem\GetFooItem::__invoke() parameter 0 (\$id), actually 'z'",
            $route
        );
    }

    public function testBadFloatParam()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/extras/z/3/4/true/5,6,7');
        $this->assertRouteError(
            Exception\InvalidArgument::CLASS,
            "Expected numeric float argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 1 (\$foo), actually 'z'",
            $route
        );
    }

    public function testBadBoolParam()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/extras/2/3/4/z/5,6,7');
        $this->assertRouteError(
            Exception\InvalidArgument::CLASS,
            "Expected boolean-equivalent argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 4 (\$dib), actually 'z'",
            $route
        );
    }

    public function testEmptyParamEarly()
    {
        $route = $this->router->route('GET', '/api/foo-item/ /extras/2/3/4true//5,6,7');
        $this->assertRouteError(
            Exception\InvalidArgument::CLASS,
            "Expected non-blank argument for AutoRoute\Http\FooItem\GetFooItem::__invoke() parameter 0 (\$id), actually ' '",
            $route
        );
    }

    public function testEmptyParamLater()
    {
        $route = $this->router->route('GET', '/api/foo-item/1/extras/ /3/4/true/5,6,7');
        $this->assertRouteError(
            Exception\InvalidArgument::CLASS,
            "Expected non-blank argument for AutoRoute\Http\FooItem\Extras\GetFooItemExtras::__invoke() parameter 1 (\$foo), actually ' '",
            $route
        );
    }
}
