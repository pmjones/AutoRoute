<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\HttpSuffix\Admin\Dashboard\GetAdminDashboardAction;
use AutoRoute\HttpSuffix\FooItem\Add\GetFooItemAddAction;
use AutoRoute\HttpSuffix\FooItem\Edit\GetFooItemEditAction;
use AutoRoute\HttpSuffix\FooItem\Extras\GetFooItemExtrasAction;
use AutoRoute\HttpSuffix\FooItem\GetFooItemAction;
use AutoRoute\HttpSuffix\FooItem\Variadic\GetFooItemVariadicAction;
use AutoRoute\HttpSuffix\FooItems\Archive\GetFooItemsArchiveAction;
use AutoRoute\HttpSuffix\FooItems\GetFooItemsAction;
use AutoRoute\HttpSuffix\GetAction;
use AutoRoute\HttpSuffix\Repo\GetRepoAction;
use AutoRoute\HttpSuffix\Repo\Issue\Comment\Add\GetRepoIssueCommentAddAction;
use AutoRoute\HttpSuffix\Repo\Issue\Comment\GetRepoIssueCommentAction;
use AutoRoute\HttpSuffix\Repo\Issue\GetRepoIssueAction;

class SuffixTest extends \PHPUnit\Framework\TestCase
{
    protected $router;

    protected $generator;

    protected $dumper;

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            namespace: 'AutoRoute\\HttpSuffix',
            directory: __DIR__ . '/HttpSuffix',
            suffix: 'Action',
        );

        $this->router = $autoRoute->getRouter();
        $this->generator = $autoRoute->getGenerator();
        $this->dumper = $autoRoute->getDumper();
    }

    public function testRouter()
    {
        $route = $this->router->route('GET', '/foo-item/add');
        $this->assertSame(GetFooItemAddAction::CLASS, $route->class);
        $this->assertSame([], $route->arguments);

        $route = $this->router->route('GET', '/foo-item/1');
        $this->assertSame(GetFooItemAction::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);

        $route = $this->router->route('GET', '/foo-item/1/edit');
        $this->assertSame(GetFooItemEditAction::CLASS, $route->class);
        $this->assertSame([1], $route->arguments);
    }

    public function testGenerator()
    {
        $actual = $this->generator->generate(GetFooItemEditAction::CLASS, 1);
        $expect = '/foo-item/1/edit';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetAdminDashboardAction::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        // repeat for coverage
        $actual = $this->generator->generate(GetAdminDashboardAction::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemAddAction::CLASS);
        $expect = '/foo-item/add';
        $this->assertSame($expect, $actual);

        // root
        $actual = $this->generator->generate(GetAction::CLASS);
        $expect = '/';
        $this->assertSame($expect, $actual);

        // parameter types
        $actual = $this->generator->generate(
            GetFooItemExtrasAction::CLASS,
            1,
            2.3,
            'bar',
            'baz',
            true,
            ['a', 'b', 'c']
        );
        $expect = '/foo-item/1/extras/2.3/bar/baz/1/a,b,c';
        $this->assertSame($expect, $actual);

        // variadics
        $actual = $this->generator->generate(
            GetFooItemVariadicAction::CLASS,
            1,
            'foo',
            'bar',
            'baz'
        );
        $expect = '/foo-item/1/variadic/foo/bar/baz';
        $this->assertSame($expect, $actual);

        // optionals
        $actual = $this->generator->generate(GetFooItemsArchiveAction::CLASS);
        $expect = '/foo-items/archive';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchiveAction::CLASS, '1970');
        $expect = '/foo-items/archive/1970';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchiveAction::CLASS, '1970', '11');
        $expect = '/foo-items/archive/1970/11';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchiveAction::CLASS, '1970', '11', '07');
        $expect = '/foo-items/archive/1970/11/07';
        $this->assertSame($expect, $actual);
    }

    public function testDumper()
    {
        $expect = array (
          '/' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\GetAction',
            'Head' => 'AutoRoute\\HttpSuffix\\GetAction',
          ),
          '/admin/dashboard' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\Admin\\Dashboard\\GetAdminDashboardAction',
            'Head' => 'AutoRoute\\HttpSuffix\\Admin\\Dashboard\\GetAdminDashboardAction',
          ),
          '/foo-item' =>
          array (
            'Post' => 'AutoRoute\\HttpSuffix\\FooItem\\PostFooItemAction',
          ),
          '/foo-item/add' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItem\\Add\\GetFooItemAddAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItem\\Add\\GetFooItemAddAction',
          ),
          '/foo-item/{int:id}' =>
          array (
            'Delete' => 'AutoRoute\\HttpSuffix\\FooItem\\DeleteFooItemAction',
            'Get' => 'AutoRoute\\HttpSuffix\\FooItem\\GetFooItemAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItem\\GetFooItemAction',
            'Patch' => 'AutoRoute\\HttpSuffix\\FooItem\\PatchFooItemAction',
          ),
          '/foo-item/{int:id}/edit' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItem\\Edit\\GetFooItemEditAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItem\\Edit\\GetFooItemEditAction',
          ),
          '/foo-item/{int:id}/extras/{float:foo}/{string:bar}/{mixed:baz}/{bool:dib}[/{array:gir}]' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItem\\Extras\\GetFooItemExtrasAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItem\\Extras\\GetFooItemExtrasAction',
          ),
          '/foo-item/{int:id}/variadic[/{...string:more}]' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItem\\Variadic\\GetFooItemVariadicAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItem\\Variadic\\GetFooItemVariadicAction',
          ),
          '/foo-items/archive[/{int:year}][/{int:month}][/{int:day}]' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItems\\Archive\\GetFooItemsArchiveAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItems\\Archive\\GetFooItemsArchiveAction',
          ),
          '/foo-items[/{int:page}]' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\FooItems\\GetFooItemsAction',
            'Head' => 'AutoRoute\\HttpSuffix\\FooItems\\GetFooItemsAction',
          ),
          '/repo/{string:ownerName}/{string:repoName}' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\Repo\\GetRepoAction',
            'Head' => 'AutoRoute\\HttpSuffix\\Repo\\GetRepoAction',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\GetRepoIssueAction',
            'Head' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\GetRepoIssueAction',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/add' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAddAction',
            'Head' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAddAction',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/{int:commentNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\Comment\\GetRepoIssueCommentAction',
            'Head' => 'AutoRoute\\HttpSuffix\\Repo\\Issue\\Comment\\GetRepoIssueCommentAction',
          ),
        );

        $actual = $this->dumper->dump();
        $this->assertSame($expect, $actual);
    }
}
