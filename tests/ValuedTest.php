<?php
declare(strict_types=1);

namespace AutoRoute;

use AutoRoute\HttpValued\Admin\Dashboard\GetAdminDashboard;
use AutoRoute\HttpValued\FooItem\Add\GetFooItemAdd;
use AutoRoute\HttpValued\FooItem\Edit\GetFooItemEdit;
use AutoRoute\HttpValued\FooItem\Extras\GetFooItemExtras;
use AutoRoute\HttpValued\FooItem\GetFooItem;
use AutoRoute\HttpValued\FooItem\Variadic\GetFooItemVariadic;
use AutoRoute\HttpValued\FooItems\Archive\GetFooItemsArchive;
use AutoRoute\HttpValued\FooItems\GetFooItems;
use AutoRoute\HttpValued\Get;
use AutoRoute\HttpValued\Repo\GetRepo;
use AutoRoute\HttpValued\Repo\Issue\Comment\Add\GetRepoIssueCommentAdd;
use AutoRoute\HttpValued\Repo\Issue\Comment\GetRepoIssueComment;
use AutoRoute\HttpValued\Repo\Issue\GetRepoIssue;
use AutoRoute\Value\Id;

class ValuedTest extends \PHPUnit\Framework\TestCase
{
    protected $router;

    protected $generator;

    protected $dumper;

    protected function setUp() : void
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\\HttpValued',
            __DIR__ . '/HttpValued'
        );

        $this->router = $autoRoute->getRouter();
        $this->generator = $autoRoute->getGenerator();
        $this->dumper = $autoRoute->getDumper();
    }

    public function testRouter()
    {
        $route = $this->router->route('GET', '/foo-item/1');
        $this->assertSame(GetFooItem::CLASS, $route->class);
        $this->assertInstanceOf(Id::CLASS, $route->arguments[0]);
        $this->assertSame(1, $route->arguments[0]->get());

        $route = $this->router->route('GET', '/foo-items/archive');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);

        $route = $this->router->route('GET', '/foo-items/archive/1979');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);

        $route = $this->router->route('GET', '/foo-items/archive/1979/11');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);

        $route = $this->router->route('GET', '/foo-items/archive/1979/11/07');
        $this->assertSame(GetFooItemsArchive::CLASS, $route->class);
    }

    public function testGenerator()
    {
        $actual = $this->generator->generate(GetFooItemEdit::CLASS, 1);
        $expect = '/foo-item/1/edit';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        // repeat for coverage
        $actual = $this->generator->generate(GetAdminDashboard::CLASS);
        $expect = '/admin/dashboard';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemAdd::CLASS);
        $expect = '/foo-item/add';
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
        $expect = '/foo-item/1/extras/2.3/bar/baz/1/a,b,c';
        $this->assertSame($expect, $actual);

        // variadics
        $actual = $this->generator->generate(
            GetFooItemVariadic::CLASS,
            1,
            'foo',
            'bar',
            'baz'
        );
        $expect = '/foo-item/1/variadic/foo/bar/baz';
        $this->assertSame($expect, $actual);

        // optionals
        $actual = $this->generator->generate(GetFooItemsArchive::CLASS);
        $expect = '/foo-items/archive';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970');
        $expect = '/foo-items/archive/1970';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11');
        $expect = '/foo-items/archive/1970/11';
        $this->assertSame($expect, $actual);

        $actual = $this->generator->generate(GetFooItemsArchive::CLASS, '1970', '11', '07');
        $expect = '/foo-items/archive/1970/11/07';
        $this->assertSame($expect, $actual);
    }

    public function testDumper()
    {
        $expect = array (
          '/' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Get',
            'Head' => 'AutoRoute\\HttpValued\\Get',
          ),
          '/admin/dashboard' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Admin\\Dashboard\\GetAdminDashboard',
            'Head' => 'AutoRoute\\HttpValued\\Admin\\Dashboard\\GetAdminDashboard',
          ),
          '/foo-item' =>
          array (
            'Post' => 'AutoRoute\\HttpValued\\FooItem\\PostFooItem',
          ),
          '/foo-item/add' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItem\\Add\\GetFooItemAdd',
            'Head' => 'AutoRoute\\HttpValued\\FooItem\\Add\\HeadFooItemAdd',
          ),
          '/foo-item/{int:id}' =>
          array (
            'Delete' => 'AutoRoute\\HttpValued\\FooItem\\DeleteFooItem',
            'Get' => 'AutoRoute\\HttpValued\\FooItem\\GetFooItem',
            'Head' => 'AutoRoute\\HttpValued\\FooItem\\HeadFooItem',
            'Patch' => 'AutoRoute\\HttpValued\\FooItem\\PatchFooItem',
          ),
          '/foo-item/{int:id}/edit' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItem\\Edit\\GetFooItemEdit',
            'Head' => 'AutoRoute\\HttpValued\\FooItem\\Edit\\GetFooItemEdit',
          ),
          '/foo-item/{int:id}/extras/{float:foo}/{string:bar}/{mixed:baz}/{bool:dib}[/{array:gir}]' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItem\\Extras\\GetFooItemExtras',
            'Head' => 'AutoRoute\\HttpValued\\FooItem\\Extras\\GetFooItemExtras',
          ),
          '/foo-item/{int:id}/variadic[/{...string:more}]' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItem\\Variadic\\GetFooItemVariadic',
            'Head' => 'AutoRoute\\HttpValued\\FooItem\\Variadic\\GetFooItemVariadic',
          ),
          '/foo-items/archive[/{int:year}][/{int:month}][/{int:day}]' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItems\\Archive\\GetFooItemsArchive',
            'Head' => 'AutoRoute\\HttpValued\\FooItems\\Archive\\GetFooItemsArchive',
          ),
          '/foo-items[/{int:page}]' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\FooItems\\GetFooItems',
            'Head' => 'AutoRoute\\HttpValued\\FooItems\\GetFooItems',
          ),
          '/repo/{string:ownerName}/{string:repoName}' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Repo\\GetRepo',
            'Head' => 'AutoRoute\\HttpValued\\Repo\\GetRepo',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Repo\\Issue\\GetRepoIssue',
            'Head' => 'AutoRoute\\HttpValued\\Repo\\Issue\\GetRepoIssue',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/add' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAdd',
            'Head' => 'AutoRoute\\HttpValued\\Repo\\Issue\\Comment\\Add\\GetRepoIssueCommentAdd',
          ),
          '/repo/{string:ownerName}/{string:repoName}/issue/{int:issueNum}/comment/{int:commentNum}' =>
          array (
            'Get' => 'AutoRoute\\HttpValued\\Repo\\Issue\\Comment\\GetRepoIssueComment',
            'Head' => 'AutoRoute\\HttpValued\\Repo\\Issue\\Comment\\GetRepoIssueComment',
          ),
        );

        $actual = $this->dumper->dump();
        $this->assertSame($expect, $actual);
    }
}
