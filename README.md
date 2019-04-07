# AutoRoute

AutoRoute automatically maps incoming HTTP requests (by verb and path) to PHP
action classes in a specified namespace, reflecting on a specified action method
within that class to determine the dynamic URL parameters. In addition, it lets
you generate URL paths based on action class names, and checks the dynamic
segment typehints for you automatically.

AutoRoute is low-maintenance. Merely adding a class to your source code, in the
recognized namespace and with the recognized action method name, automatically
makes it available as a route. No more managing a routes file to keep it in
sync with your action classes!

AutoRoute is fast. In fact, it is [faster than FastRoute][benchmark] in common
cases -- even when FastRoute is using cached route definitions.

  [benchmark]: https://github.com/pmjones/AutoRoute-benchmark

> **Note:**
>
> When comparing alternatives, please consider AutoRoute as being in the same
> category as [AltoRouter](https://github.com/dannyvankooten/AltoRouter),
> [FastRoute](https://github.com/nikic/FastRoute),
> [Klein](https://github.com/klein/klein.php), etc.,
> and not of [Aura](https://github.com/auraphp/Aura.Router),
> [Laravel](https://github.com/illuminate/routing),
> [Symfony](https://github.com/symfony/Routing),
> [Zend](https://github.com/zendframework/zend-router), etc.

**Contents**

- [Motivation](#motivation)
- [Examples](#examples)
- [How It Works](#how-it-works)
- [Usage](#usage)
- [Generating Route Paths](#generating-route-paths)
- [Alternative Configurations](#alternative-configurations)
- [Dumping All Routes](#dumping-all-routes)
- [Questions and Recipes](#questions-and-recipes)


## Motivation

Regular-expression (regex) routers generally duplicate important information
that can be found by reflection instead. If you change the action method
parameters targeted by a route, you need to change the route regex itself as
well.  As such, regex router usage may be considered a violation of the DRY
principle. For systems with only a few routes, maintaining a routes file as
duplicated information is not such a chore. But for systems with a hundred or
more routes, keeping the routes in sync with their target action classes and
methods can be onerous.

Similarly, annotation-based routers place routing instructions in comments,
often duplicating dynamic parameters that are already present in explicit
method signatures.

As an alternative to regex and annotation-based routers, this router
implementation eliminates the need for route definitions by automatically
mapping the HTTP action class hierarchy to the HTTP method verb and URL path,
reflecting on typehinted action method parameters to determine the dynamic
portions of the URL. It presumes that the action class names conform to a
well-defined convention, and that the action method parameters indicate the
dynamic portions of the URL. This makes the implementation both flexible and
relatively maintenance-free.

## Examples

Given a base namespace of `App\Http` and a base url of `/`, this request ...

    GET /photos

... auto-routes to the class `App\Http\Photos\GetPhotos`.

Likewise, this request ...

    POST /photo

... auto-routes to the class `App\Http\Photo\PostPhoto`.

Given an action class with method parameters, such as this ...

```php
namespace App\Http\Photo;

class GetPhoto
{
    public function __invoke(int $photoId)
    {
        // ...
    }
}
```

... the following request will route to it ...

    GET /photo/1

... recognizing that `1` should be the value of `$photoId`.

AutoRoute supports static "tail" parameters on the URL. If the URL ends in a
path segment that matches the underscore-separated tail portion of a class name,
and the action class method has the same number and type of parameters as its
parent or grandparent class, it will route to that class name. For example,
given an action class with method parameters such as this ...

```php
namespace App\Http\Photo\Edit;

class GetPhotoEdit // parent: GetPhoto
{
    public function __invoke(int $photoId)
    {
        // ...
    }
}
```

... the following request will route to it:

    GET /photo/1/edit

Finally, a request for the root URL ...

    GET /

... auto-routes to to the the class `App\Http\Get`.


## How It Works

### Class File Naming

Action class files are presumed to be named according to PSR-4 standards;
further:

1. The class name starts with the HTTP verb it responds to;

2. Followed by the concatenated names of preceding subnamespaces;

4. Ending in `.php`.

Thus, given a base namespace of `App\Http`, the class `App\Http\Photo\PostPhoto`
will be the action for `POST /photo[/*]`.

Likewise, `App\Http\Photos\GetPhotos` will be the action class for `GET /photos[/*]`.

And `App\Http\Photo\Edit\GetPhotoEdit` will be the action class for `GET /photo[/*]/edit`.

Finally, at the URL root path, `App\Http\Get` will be the action class for `GET /`.

### Dynamic Parameters

The action method parameter typehints are honored by the _Router_. For example,
the following action ...

```php
namespace App\Http\Photos\Archive;

class GetPhotosArchive
{
    public function __invoke(int $year = null, int $month = null)
    {
        // ...
    }
}
```

... will respond to the following:

    GET /photos/archive
    GET /photos/archive/1970
    GET /photos/archive/1970/08

... but not to the following ...

    GET /photos/archive/z
    GET /photos/archive/1970/z

... because `z` is not recognized as an integer. (More finely-tuned validations
of the method parameters must be accomplished in the action method itself, or
more preferably in the domain logic, and cannot be intuited by the _Router_.)

The _Router_ can recognize typehints of `int`, `float`, `string`, `bool`, and
`array`.

For `bool`, the _Router_ will case-insensitively cast these URL segment values
to `true`: `1, t, true, y, yes`. Similarly, it will case-insensitively  cast
these URL segment values to `false`: `0, f, false, n, no`.

For `array`, the _Router_ will use `str_getcsv()` on the URL segment value to
generate an array. E.g., an array typehint for a segment value of `a,b,c` will
receive `['a', 'b', 'c']`.

Finally, trailing variadic parameters are also honored by the _Router_. Given an
action method like the following ...

```php
namespace App\Http\Photos\ByTag;

class GetPhotosByTag
{
    public function __invoke(string $tag, string ...$tags)
    {
        // ...
    }
}
```

... the _Router_ will honor this request ...

    GET /photos/by-tag/foo/bar/baz/

... and recognize the method parameters as `__invoke('foo', 'bar', 'baz')`.

### Extended Example

By way of an extended example, these classes would be routed to by these URLs:

```
App/
    Http/
        Get.php                     GET /               (root)
        Photos/
            GetPhotos.php           GET /photos         (browse/index)
        Photo/
            DeletePhoto.php         DELETE /photo/1     (delete)
            GetPhoto.php            GET /photo/1        (read)
            PatchPhoto.php          PATCH /photo/1      (update)
            PostPhoto.php           POST /photo         (create)
            Add/
                GetPhotoAdd.php     GET /photo/add      (form for creating)
            Edit/
                GetPhotoEdit.php    GET /photo/1/edit   (form for updating)
```

## Usage

Instantiate the _AutoRoute_ container class with the top-level HTTP action
namespace and the directory path to classes in that namespace:

```php
use AutoRoute\AutoRoute;

$autoRoute = new AutoRoute(
    'App\Http',
    dirname(__DIR__) . '/src/App/Http/'
);
```

Then, pull a new _Router_ out of the container ...

```php
$router = $autoRoute->newRouter();
```

... and call `route()` with the HTTP request method verb and the path string to
get back a _Route_, catching exceptions along the way:

```php
try {
    $route = $router->route($request->method, $request->url[PHP_URL_PATH]);

} catch (\AutoRoute\InvalidNamespace $e) {
    // 400 Bad Request

} catch (\AutoRoute\InvalidArgument $e) {
    // 400 Bad Request

} catch (\AutoRoute\NotFound $e) {
    // 404 Not Found

} catch (\AutoRoute\MethodNotAllowed $e) {
    // 405 Method Not Allowed

}
```

Finally, dispatch to the action class method using the returned _Route_
information:

```php
// presuming a DI-based Factory that can create new action class instances:
$action = Factory::newInstance($route->class);

// call the action instance with the method and params,
// presumably getting back an HTTP Response
$response = call_user_func($action, $route->method, ...$route->params);
```

## Generating Route Paths

Using the _AutoRoute_ container, pull out a new _Generator_:

```php
$generator = $autoRoute->newGenerator();
```

Then call the `generate()` method with the action class name, along with any
action method parameters as variadic arguments:

```php
use App\Http\Photo\Edit\GetPhotoEdit;
use App\Http\Photos\ByTag\GetPhotosByTag;

$path = $generator->generate(GetPhotoEdit::CLASS, 1);
// /photo/1/edit

$path = $generator->generate(GetPhotosByTag::CLASS, 'foo', 'bar', 'baz');
// /photos/by-tag/foo/bar/baz
```

> **Tip**:
>
> Using the action class name for the route name means that all routes in
> AutoRoute are automatically named routes.

The _Generator_ will automatically check the argument values against the action
method signature to make sure the values will be recognized by the _Router_.
This means that you cannot (or at least, should not!) be able to generate a
path that the _Router_ will not recognize.

## Alternative Configurations

Set these options on the _AutoRoute_ container before pulling out a new
_Router_ or _Generator_.

### Class Name Suffix

If your code base gives all action class names the same suffix, such as
"Action", you can tell _AutoRoute_ to disregard that suffix like so:

```php
$autoRoute->setSuffix('Action');
```

The _Router_ and _Generator_ will now ignore the suffix portion of the class
name.

### Method

If you use an action method name other than `__invoke()`, such as `exec()` or
`run()`, you can tell _AutoRoute_ to reflect on its parameters instead:

```php
$autoRoute->setMethod('exec');
```

The _Router_ and _Generator_ will now examine the `exec()` method to determine
the dynamic segments of the URL path.

### Base URL

You may specify a base URL (i.e., a URL path prefix) like so:

```php
$autoRoute->setBaseUrl('/api');
```

The _Router_ will ignore the base URL when determining the target action class
for the route, and the _Generator_ will prefix all paths with the base URL.

### Word Separator

By default, the _Router_ and _Generator_ will inflect static URL path segments
from `foo-bar` to `FooBar`, using the dash as a word separator. If you want to
use a different word separator, such as an underscore, you may do so like this:

```php
$autoRoute->setWordSeparator('_');
```

This will cause the _Router_ and _Generator_ to inflect from `foo_bar` to
`FooBar` (and back again).

### Ignoring Action Method Parameters

Some UI systems may use a shared Request object, in which case it is easy to
inject the Request into the action constructor. However, other systems may
not have access to a shared Request object, or may be using a Request that is
fully-formed only at the moment the Action is called, so it must be passed in
some way other than via the constructor.

Typically, these kinds of parameters are passed at the moment the action is
called, which means they must be part of the aciton method signature. However,
_AutoRoute_ will see that parameter and incorrectly interpret it as a dynamic
segment; for example:

```php
class PatchPhoto
{
    public function __invoke(\ServerRequest $request, int $id)
    {
        // ...
    }
}
```

To remedy this, _AutoRoute_ can skip over any number of leading parameters
on the action method. To do so, set the number of parameters to ignore at the
_AutoRoute_ container ...

```php
$autoRoute->setIgnoreParams(1);
```

... and then any new _Router_ and _Generator_ will ignore the first parameter.

Note that you will need to pass that first parameter yourself when you invoke
the action:

```php
// determine the route
$route = $router->route($request->method, $request->url[PHP_URL_PATH]);

// create the action object
$action = Factory::newInstance($route->class);

// pass the request first, then any route params
$response = call_user_func($action, $route->method, $request, ...$route->params);
```


## Dumping All Routes

You can dump a list of all recognized routes, and their target action classes,
using the `bin/autoroute-dump.php` command line tool. Pass the base HTTP action
namespace, and the directory where the action classes are stored:

```
$ php bin/autoroute-dump.php App\\Http ./src/Http
```

The output will look something like this:

```
GET     /
        App\Http\Get
POST    /photo
        App\Http\Photo\PostPhoto
GET     /photo/add
        App\Http\Photo\Add\GetPhotoAdd
DELETE  /photo/{int:id}
        App\Http\Photo\DeletePhoto
GET     /photo/{int:id}
        App\Http\Photo\GetPhoto
PATCH   /photo/{int:id}
        App\Http\Photo\PatchPhoto
GET     /photo/{int:id}/edit
        App\Http\Photo\Edit\GetPhotoEdit
GET     /photos/archive[/{int:year}][/{int:month}][/{int:day}]
        App\Http\Photos\Archive\GetPhotosArchive
GET     /photos[/{int:page}]
        App\Http\Photos\GetPhotos
```

You can specify an alternative configurations with these command line options:

- `--base-url=` to set the base URL
- `--ignore-params=` to ignore a number of leading method parameters
- `--method=` to set the action class method name
- `--suffix=` to note a standard action class suffix
- `--word-separator=` to specify an alternative word separator

## Questions and Recipes

### Child Resources

> N.b.: Deeply-nested child resources are currently considered a poor practice,
> but they are common enough that they demand attention here.

Deeply-nested child resources are supported, but their action class method
parameters must conform to a "routine" signature, so that the _Router_ and
_Generator_ can recognize which segments of the URL are dynamic and which are
static.

1. A child resource action MUST have at least the same number and type of
parameters as its "parent" resource action; OR, in the case of static tail
parameter actions, exactly the same number and type or parameters as its
"grandparent" resource action. (If there is no parent or grandparent resource
action, then it need not have any parameters.)

2. A child resource action MAY add parameters after that, either as required or
optional.

3. When the URL path includes any of the optional parameter segments, routing to
further child resource actions beneath it will be terminated.

> **Tip**:
>
> The above terms "parent" and "grandparent" are used in the URL path sense,
> not in the class hierarchy sense.

```php
/* GET /company/{companyId} # get an existing company */
namespace App\Http\Company;

class GetCompany // no parent resource
{
    public function __invoke(int $companyId)
    {
        // ...
    }
}

/* POST /company # add a new company*/
class PostCompany // no parent resource
{
    public function __invoke()
    {
        // ...
    }
}

/* PATCH /company/{companyId} # edit an existing company */
class PatchCompany // no parent resource
{
    public function __invoke(int $companyId)
    {
        // ...
    }
}

/* GET /company/{companyId}/employee/{employeeNum} # get an existing company employee */
namespace App\Http\Company\Employee;

class GetCompanyEmployee // parent resource: GetCompany
{
    public function __invoke(int $companyId, int $employeeNum)
    {
        // ...
    }
}

/* POST /company/{companyId}/employee # add a new company employee */
namespace App\Http\Company\Employee;

class PostCompanyEmployee // parent resource: PostCompany
{
    public function __invoke(int $companyId)
    {
        // ...
    }
}

/* PATCH /company/{companyId}/employee/{employeeNum} # edit an existing company employee */
namespace App\Http\Company\Employee;

class PatchCompanyEmployee // parent resource: PatchCompany
{
    public function __invoke(int $companyId, int $employeeNum)
    {
        // ...
    }
}
```


### Fine-Grained Input Validation

Q: How do I specify something similar to the regex route `path('/foo/{id}')->token(['id' => '\d{4}'])` ?

A: You don't.

Your domain does fine validation of the inputs, not your routing system (coarse
validation only). AutoRoute, in casting the params to arguments, will set the
type on the argument, which may raise an _InvalidArgument_ or _NotFound_
exception if the value cannot be typecast correctly.

For example, in the action:

```php
namespace App\Http\Photos\Archive;

class GetPhotosArchive
{
    public function __invoke(
        int $year = null,
        int $month = null,
        int $day = null
    ) : Response
    {
        $payload = $this->domain->fetchAllBySpan($year, $month, $day);
        return $this->responder->response($payload);
    }
}
```

Then, in the domain:

```php
namespace App\Domain;

class PhotoService
{
    public function fetchAllBySpan(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null
    ) : Payload
    {
        $select = $this->atlas
            ->select(Photos::class)
            ->orderBy('year DESC', 'month DESC', 'day DESC');

        if ($year !== null) {
            $select->where('year = ', $year);
        }

        if ($month !== null) {
            $select->where('month = ', $month);
        }

        if ($day !== null) {
            $select->where('day = ', $day);
        }

        $result = $select->fetchRecordSet();
        if ($result->isEmpty()) {
            return Payload::notFound();
        }

        return Payload::found($result);
    }
}
```

### Capturing Other Request Values

Q: How to capture the hostname? Headers? Query parameters? Body?

A: Read them from your Request object.

For example, in the action:

```php
namespace App\Http\Foos;

class GetFoos
{
    public function __construct(
        \ServerRequest $request,
        FooService $fooService
    ) {
        $this->request = $request;
        $this->fooService = $fooService;
    }

    public function __invoke(int $fooId)
    {
        $host = $this->request->headers['host'] ?? null;
        $bar = $this->request->get['bar'] ?? null;
        $body = json_decode($this->request->content, true) ?? [];

        $payload = $this->fooService->fetch($host, $foo, $body);
        // ...
    }
}
```

Then, in the domain:

```php
namespace App\Domain;

class FooService
{
    public function fetch(int $fooId, string $host, string $bar, array $body)
    {
        // ...
    }
}
```
