<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\HttpIgnore\Admin\Dashboard\GetAdminDashboard;
use AutoRoute\HttpIgnore\FooItem\Add\GetFooItemAdd;
use AutoRoute\HttpIgnore\FooItem\Edit\GetFooItemEdit;
use AutoRoute\HttpIgnore\FooItem\Extras\GetFooItemExtras;
use AutoRoute\HttpIgnore\FooItem\GetFooItem;
use AutoRoute\HttpIgnore\FooItem\Variadic\GetFooItemVariadic;
use AutoRoute\HttpIgnore\FooItems\Archive\GetFooItemsArchive;
use AutoRoute\HttpIgnore\FooItems\GetFooItems;
use AutoRoute\HttpIgnore\Get;
use AutoRoute\HttpIgnore\Repo\GetRepo;
use AutoRoute\HttpIgnore\Repo\Issue\Comment\Add\GetRepoIssueCommentAdd;
use AutoRoute\HttpIgnore\Repo\Issue\Comment\GetRepoIssueComment;
use AutoRoute\HttpIgnore\Repo\Issue\GetRepoIssue;

class IgnoreTest extends \PHPUnit\Framework\TestCase
{
    protected $router;

    protected $generator;

    protected $dumper;

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\\HttpIgnore',
            __DIR__ . '/HttpIgnore'
        );

        $autoRoute->setMethod('exec');
        $autoRoute->setIgnoreParams(1);
        $autoRoute->setWordSeparator('_');

        $this->router = $autoRoute->newRouter();
        $this->generator = $autoRoute->newGenerator();
        $this->dumper = $autoRoute->newDumper();
    }

    public function testRouter()
    {
        $route = $this->router->route('GET', '/foo_item/add');
        $this->assertSame(GetFooItemAdd::CLASS, $route->class);
        $this->assertSame([], $route->params);

        $route = $this->router->route('GET', '/foo_item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertSame([1], $route->params);

        $route = $this->router->route('GET', '/foo_item/1/edit');
        $this->assertSame(GetFooItemEdit::CLASS, $route->class);
        $this->assertSame([1], $route->params);
    }

    public function testGenerator()
    {
        $actual = $this->generator->generate(GetFooItemEdit::CLASS, 1);
        $expect = '/foo_item/1/edit';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        // repeat for coverage
        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemAdd::CLASS);
        $expect = '/foo_item/add';
        $this->assertSame($expect, $actual);

        // root
        $actual = $this->generator->generate(Get::CLASS);
        $expect = '/';
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
        $expect = '/foo_item/1/extras/2.3/bar/baz/1/a,b,c';
        $this->assertSame($expect, $actual);

        // variadics
        $actual = $this->generator->generate(
            GetFooItemVariadic::CLASS,
            1,
            'foo',
            'bar',
            'baz'
        );
        $expect = '/foo_item/1/variadic/foo/bar/baz';
        $this->assertSame($expect, $actual);

        // optionals
        $actual = $this->generator->generate(GetFooItemsArchive::CLASS);
        $expect = '/foo_items/archive';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970');
        $expect = '/foo_items/archive/1970';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11');
        $expect = '/foo_items/archive/1970/11';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11', '07');
        $expect = '/foo_items/archive/1970/11/07';
        $this->assertSame($expect, $actual);
    }

    public function testDumper()
    {
        $expect = array (
          '/' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Get',
            'Head' => 'AutoRoute\\HttpIgnore\\Get',
          ),
          '/admin/dashboard' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Admin\\Dashboard\\GetAdminDashboard',
            'Head' => 'AutoRoute\\HttpIgnore\\Admin\\Dashboard\\GetAdminDashboard',
          ),
          '/foo_item' =>
          array (
            'Post' => 'AutoRoute\\HttpIgnore\\FooItem\\PostFooItem',
          ),
          '/foo_item/add' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItem\\Add\\GetFooItemAdd',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItem\\Add\\GetFooItemAdd',
          ),
          '/foo_item/{int:id}' =>
          array (
            'Delete' => 'AutoRoute\\HttpIgnore\\FooItem\\DeleteFooItem',
            'Get' => 'AutoRoute\\HttpIgnore\\FooItem\\GetFooItem',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItem\\GetFooItem',
            'Patch' => 'AutoRoute\\HttpIgnore\\FooItem\\PatchFooItem',
          ),
          '/foo_item/{int:id}/edit' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItem\\Edit\\GetFooItemEdit',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItem\\Edit\\GetFooItemEdit',
          ),
          '/foo_item/{int:id}/extras/{float:foo}/{string:bar}/{string:baz}/{bool:dib}[/{array:gir}]' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItem\\Extras\\GetFooItemExtras',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItem\\Extras\\GetFooItemExtras',
          ),
          '/foo_item/{int:id}/variadic[/{string:...more}]' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItem\\Variadic\\GetFooItemVariadic',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItem\\Variadic\\GetFooItemVariadic',
          ),
          '/foo_items/archive[/{int:year}][/{int:month}][/{int:day}]' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItems\\Archive\\GetFooItemsArchive',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItems\\Archive\\GetFooItemsArchive',
          ),
          '/foo_items[/{int:page}]' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\FooItems\\GetFooItems',
            'Head' => 'AutoRoute\\HttpIgnore\\FooItems\\GetFooItems',
          ),
          '/repo/{string:ownerName}/{string:repoName}' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Repo\\GetRepo',
            'Head' => 'AutoRoute\\HttpIgnore\\Repo\\GetRepo',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\GetRepoIssue',
            'Head' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\GetRepoIssue',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/add' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAdd',
            'Head' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAdd',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/{int:commentNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\Comment\\GetRepoIssueComment',
            'Head' => 'AutoRoute\\HttpIgnore\\Repo\\Issue\\Comment\\GetRepoIssueComment',
          ),
        );

        $actual = $this->dumper->dumpRoutes();
        $this->assertSame($expect, $actual);
    }
}
