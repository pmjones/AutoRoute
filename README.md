# AutoRoute

AutoRoute automatically maps incoming HTTP requests (by verb and path) to PHP
action classes in a specified namespace, reflecting on a specified action
method within that class to determine the dynamic URL argument values. Those
parameters may be typical scalar values (int, float, string, bool), or arrays,
or even [value objects](#value-objects-as-action-parameters) of your own
creation. AutoRoute also helps you generate URL paths based on action class
names, and checks the dynamic argument typehints for you automatically.

AutoRoute is low-maintenance. Merely adding a class to your source code, in the
recognized namespace and with the recognized action method name, automatically
makes it available as a route. No more managing a routes file to keep it in
sync with your action classes!

AutoRoute is fast. In fact, it is [roughly 2x faster than FastRoute]
[benchmark] in common cases -- even when FastRoute is using cached route
definitions.

  [benchmark]: https://github.com/pmjones/AutoRoute-benchmark

> **Note:**
>
> When comparing alternatives, please consider AutoRoute as being in the same
> category as
> [AltoRouter](https://github.com/dannyvankooten/AltoRouter),
> [FastRoute](https://github.com/nikic/FastRoute),
> [Klein](https://github.com/klein/klein.php),
> etc., and not of
> [Aura](https://github.com/auraphp/Aura.Router),
> [Laminas](https://github.com/laminas/laminas-router),
> [Laravel](https://github.com/illuminate/routing),
> [Symfony](https://github.com/symfony/Routing),
> etc.

**Contents**

- [Motivation](#motivation)
- [Examples](#examples)
- [How It Works](#how-it-works)
- [Usage](#usage)
- [Generating Route Paths](#generating-route-paths)
- [Custom Configuration](#custom-configuration)
- [Dumping All Routes](#dumping-all-routes)
- [Creating Classes From Routes](#creating-classes-from-routes)
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

Given a base namespace of `Project\Http` and a base url of `/`, this request ...

    GET /photos

... auto-routes to the class `Project\Http\Photos\GetPhotos`.

Likewise, this request ...

    POST /photo

... auto-routes to the class `Project\Http\Photo\PostPhoto`.

Given an action class with method parameters, such as this ...

```php
namespace Project\Http\Photo;

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
path segment that matches the tail portion of a class name,
and the action class method has the same number and type of parameters as its
parent or grandparent class, it will route to that class name. For example,
given an action class with method parameters such as this ...

```php
namespace Project\Http\Photo\Edit;

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

... auto-routes to the class `Project\Http\Get`.

> **Tip:**
>
> Any HEAD request will auto-route to an explicit `Project\Http\...\Head*` class,
> if one exists. If an explicit `Head` class does not exist, the request will
> implicitly be auto-routed to the matching `Project\Http\...\Get*` class, if one
> exists.

## How It Works

### Class File Naming

Action class files are presumed to be named according to PSR-4 standards;
further:

1. The class name starts with the HTTP verb it responds to;

2. Followed by the concatenated names of preceding subnamespaces;

3. Ending in `.php`.

Thus, given a base namespace of `Project\Http`, the class `Project\Http\Photo\PostPhoto`
will be the action for `POST /photo[/*]`.

Likewise, `Project\Http\Photos\GetPhotos` will be the action class for `GET /photos[/*]`.

And `Project\Http\Photo\Edit\GetPhotoEdit` will be the action class for `GET /photo[/*]/edit`.

An explicit `Project\Http\Photos\HeadPhotos` will be the action class for
`HEAD /photos[/*]`. If the `HeadPhotos` class does not exist, the action class
is inferred to be `Project\Http\Photos\HeadPhotos` instead.

Finally, at the URL root path, `Project\Http\Get` will be the action class for `GET /`.

### Dynamic Parameters

The action method parameter typehints are honored by the _Router_. For example,
the following action ...

```php
namespace Project\Http\Photos\Archive;

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
namespace Project\Http\Photos\ByTag;

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

### HEAD Requests

[RFC 2616](http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1.1)
requires that "methods GET and HEAD **must** be supported by all general-purpose
servers".

As such, AutoRoute will automatically fall back to a `Get*` action class if a
relevant `Head*` action class is not found. This keeps you from having to create
a `Head*` class for every possible `Get*` action.

However, you may still define any `Head*` action class you like, and AutoRoute
will use it.

## Usage

Instantiate the AutoRoute container class with the top-level HTTP action
namespace and the directory path to classes in that namespace:

```php
use AutoRoute\AutoRoute;

$autoRoute = new AutoRoute(
    'Project\Http',
    dirname(__DIR__) . '/src/Project/Http/'
);
```

You may use named constructor parameters if you wish:

```php
use AutoRoute\AutoRoute;

$autoRoute = new AutoRoute(
    namespace: 'Project\Http',
    directory: dirname(__DIR__) . '/src/Project/Http/',
);
```

Then pull the _Router_ out of the container, and call `route()` with the HTTP
request method verb and the path string to get back a _Route_:

```php
$router = $autoRoute->getRouter();
$route = $router->route($request->method, $request->url[PHP_URL_PATH]);
```

You can then dispatch to the action class method using the returned _Route_
information, or handle errors:

```php
use AutoRoute\Exception;

switch ($route->error) {
    case null:
        // no errors! create the action class instance
        // ... and call it with the method and arguments.
        $action = Factory::newInstance($route->class);
        $method = $route->method;
        $arguments = $route->arguments;
        $response = $action->$method(...$arguments);
        break;

    case Exception\InvalidArgument::CLASS:
        $response = /* 400 Bad Request */;
        break;

    case Exception\NotFound::CLASS:
        $response = /* 404 Not Found */;
        break;

    case Exception\MethodNotAllowed::CLASS:
        $response = /* 405 Not Allowed */;
        /*
        N.b.: Examine $route->headers to find the 'allowed' methods for the
        resource, if any.
        */
        break;

    default:
        $response = /* 500 Server Error */;
        break;
}
```


## Debugging

To see how the _Router_ gets where it does, call its `getLogger()` method,
then get the array of logger messages from the default _AutoRoute\Logger_:

```php
$route = $router->route($request->method, $request->path);
$logger = $router->getLogger();
print_r($logger->getMessages());
```

> **Note:**
>
> You may inject a custom PSR-3 _LoggerInterface_ implementation factory as part
> of [custom configuration](#custom-configuration).

## Generating Route Paths

Using the AutoRoute container, pull out the _Generator_:

```php
$generator = $autoRoute->getGenerator();
```

Then call the `generate()` method with the action class name, along with any
action method parameters as variadic arguments:

```php
use Project\Http\Photo\Edit\GetPhotoEdit;
use Project\Http\Photos\ByTag\GetPhotosByTag;

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

## Custom Configuration

You may set these named constructor parameters at AutoRoute instantiation time
to configure its behavior.

### `baseUrl`

You may specify a base URL (i.e., a URL path prefix) using the following
named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    baseUrl: '/api',
);
```

The _Router_ will ignore the base URL when determining the target action class
for the route, and the _Generator_ will prefix all paths with the base URL.


### `ignore`

Some UI systems may use a shared Request object, in which case it is easy to
inject the Request into the action constructor. However, other systems may
not have access to a shared Request object, or may be using a Request that is
fully-formed only at the moment the Action is called, so it must be passed in
some way other than via the constructor.

Typically, these kinds of parameters are passed at the moment the action is
called, which means they must be part of the aciton method signature. However,
AutoRoute will see that parameter and incorrectly interpret it as a dynamic
segment; for example:

```php
namespace Project\Http\Photo;

use SapiRequest;

class PatchPhoto
{
    public function __invoke(SapiRequest $request, int $id)
    {
        // ...
    }
}
```

To remedy this, AutoRoute can skip over any number of leading parameters
on the action method. To do so, set the number of parameters to ignore using the
following named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    ignoreParams: 1,
);
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
$response = call_user_func([$action, $route->method], $request, ...$route->arguments);
```

### `loggerFactory`

To inject a custom PSR-3 Logger instance into the _Router_, use the following
named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    loggerFactory: function () {
        // return a \Psr\Log\LoggerInterface implementation
    },
);
```

### `method`

If you use an action method name other than `__invoke()`, such as `exec()` or
`run()`, you can tell AutoRoute to reflect on its parameters instead using the
following named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    method: 'exec',
);
```

The _Router_ and _Generator_ will now examine the `exec()` method to determine
the dynamic segments of the URL path.

### `suffix`

If your code base gives all action class names the same suffix, such as
"Action", you can tell AutoRoute to disregard that suffix using the following
named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    suffix: 'Action',
);

```

The _Router_ and _Generator_ will now ignore the suffix portion of the class
name.

### `wordSeparator`

By default, the _Router_ and _Generator_ will inflect static URL path segments
from `foo-bar` to `FooBar`, using the dash as a word separator. If you want to
use a different word separator, such as an underscore, you may do using the
following named constructor parameter:

```php
$autoRoute = new AutoRoute(
    // ...
    wordSeparator: '_',
);
```

This will cause the _Router_ and _Generator_ to inflect from `foo_bar` to
`FooBar` (and back again).


## Dumping All Routes

You can dump a list of all recognized routes, and their target action classes,
using the `bin/autoroute-dump.php` command line tool. Pass the base HTTP action
namespace, and the directory where the action classes are stored:

```
$ php bin/autoroute-dump.php Project\\Http ./src/Http
```

The output will look something like this:

```
GET     /
        Project\Http\Get
POST    /photo
        Project\Http\Photo\PostPhoto
GET     /photo/add
        Project\Http\Photo\Add\GetPhotoAdd
DELETE  /photo/{int:id}
        Project\Http\Photo\DeletePhoto
GET     /photo/{int:id}
        Project\Http\Photo\GetPhoto
PATCH   /photo/{int:id}
        Project\Http\Photo\PatchPhoto
GET     /photo/{int:id}/edit
        Project\Http\Photo\Edit\GetPhotoEdit
GET     /photos/archive[/{int:year}][/{int:month}][/{int:day}]
        Project\Http\Photos\Archive\GetPhotosArchive
GET     /photos[/{int:page}]
        Project\Http\Photos\GetPhotos
```

You can specify alternative configurations with these command line options:

- `--base-url=` to set the base URL
- `--ignore-params=` to ignore a number of leading method parameters
- `--method=` to set the action class method name
- `--suffix=` to note a standard action class suffix
- `--word-separator=` to specify an alternative word separator


## Creating Classes From Routes

AutoRoute provides minimalist support for creating class files based on a
route verb and path, using a template.

To do so, invoke `autoroute-create.php` with the base namespace, the directory
for that namespace, the HTTP verb, and the URL path with parameter token
placeholders.

For example, the following command ...

```
$ php bin/autoroute-create.php Project\\Http ./src/Http GET /photo/{photoId}
```

... will create this class file at `./src/Http/Photo/GetPhoto.php`:

```php
namespace Project\Http\Photo;

class GetPhoto
{
    public function __invoke($photoId)
    {
    }
}
```

The command will not overwrite existing files.

You can specify alternative configurations with these command line options:

- `--method=` to set the action class method name
- `--suffix=` to note a standard action class suffix
- `--template=` to specify the path to a custom template
- `--word-separator=` to specify an alternative word separator

The default class template file is `resources/templates/action.tpl`. If you
decide to write a custom template of your own, the available string-replacement
placeholders are:

- `{NAMESPACE}`
- `{CLASS}`
- `{METHOD}`
- `{PARAMETERS}`

These names should be self-explanatory.

> **Note:**
>
> Even with a custom template, you will almost certainly need to edit the new
> file to add a constructor, typehints, default values, and so on. The file
> creation functionality is necessarily minimalist, and cannot account for all
> possible variability in your specific situation.


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
namespace Project\Http\Company;

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
namespace Project\Http\Company\Employee;

class GetCompanyEmployee // parent resource: GetCompany
{
    public function __invoke(int $companyId, int $employeeNum)
    {
        // ...
    }
}

/* POST /company/{companyId}/employee # add a new company employee */
namespace Project\Http\Company\Employee;

class PostCompanyEmployee // parent resource: PostCompany
{
    public function __invoke(int $companyId)
    {
        // ...
    }
}

/* PATCH /company/{companyId}/employee/{employeeNum} # edit an existing company employee */
namespace Project\Http\Company\Employee;

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

A: You don't. (However, see the topic on "Value Objects as Action Parameters", below.)

Your domain does fine validation of the inputs, not your routing system (coarse
validation only). AutoRoute, in casting the params to arguments, will set the
type on the argument, which may raise an _InvalidArgument_ or _NotFound_
exception if the value cannot be typecast correctly.

For example, in the action:

```php
namespace Project\Http\Photos\Archive;

use SapiResponse;

class GetPhotosArchive
{
    public function __invoke(
        int $year = null,
        int $month = null,
        int $day = null
    ) : SapiResponse
    {
        $payload = $this->domain->fetchAllBySpan($year, $month, $day);
        return $this->responder->response($payload);
    }
}
```

Then, in the domain:

```php
namespace Project\Domain;

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

### Value Objects as Action Parameters

Q: Can I use an object (instead of a scalar or array) as an action parameter?

A: Yes, with some caveats.

Although you cannot specify input validation in the routing itself, per se, you
*can* specify a value object as parameter, and do validation within its
constructor. These value objects may come from anywhere, including the Domain.

For example, your underlying Application Service classes might need Domain
value objects as inputs, with the action creating those value objects itself:

```php
namespace Project\Http\Company;

use Domain\Company\CompanyId;

class GetCompany
{
    // ...
    public function __invoke(int $companyId)
    {
        // ...
        $payload = $this->domain->fetchCompany(
            new CompanyId($companyId)
        );
        // ...
    }
}
```

The corresponding value object might look like this:

```php
namespace Domain\Company;

use Domain\ValueObject;

class CompanyId extends ValueObject
{
    public function __construct(protected int $companyId)
    {
    }
}
```

To avoid the manual conversion of dynamic path segments to value objects, you
may use the value object type itself as an action parameter, like so:

```php
namespace Project\Http\Company;

use Domain\Company\CompanyId;

class GetCompany
{
    // ...
    public function __invoke(CompanyId $companyId)
    {
        // ...
        $payload = $this->domain->fetchCompany($companyId);
        // ...
    }
}
```

Given the HTTP request `GET /company/1`, the _Router_ will notice that the
action parameter is of the _CompanyId_ type, and use the relevant segments of
the URL path to build the _CompanyId_ argument.

Further, you can attempt to validate and/or sanitize the value object arguments,
throwing an exception on invalidation. For example:

```php
namespace Domain\Photo;

use Domain\Exception\InvalidValue;
use Domain\ValueObject;

class Year extends ValueObject
{
    public function __construct(protected int $year)
    {
        if ($this->year < 0 || $this->year > 9999) {
            throw new InvalidValue("The year must be between 0000 and 9999").
        }
    }
}
```

It is up to you to catch these exceptions and send the appropriate HTTP
response.

Some additional notes:

- You can use as many value object constructor parameters as you like; each
  parameter will capture one path segment, in order.

- The path segments will be cast to the correct data types for you, per the
  value object constructor parameter typehints.

- Using a class type as a value object parameter will not work correctly; use
  only scalars and arrays as value object parameter types.

- Using optional or variadic parameters in a value object may not always work as
  intended. If your value objects have optional or variadic parameters, save
  those value objects for the terminating portions of URL paths.

- You can combine value object parameters with scalar and array parameters in
  the action method signature.

#### Generating Paths With Value Objects

When generating a path for an action that uses value objects, you need to pass
the individual arguments as they would appear in the URL, not as they appear
when calling the action. Given the above _GetCompany_ action, you would not
instantiate _CompanyId_; instead, you would pass the integer value argument.

```php
// wrong:
$path = $generator->generate(GetCompany::CLASS, new CompanyId(1));

// right:
$path = $generator->generate(GetCompany::CLASS, 1);

```

#### Dumping Paths With Value Objects

When you dump the routes via the _Dumper_, you will find that the dynamic
segments associated with value objects are named for the value object
constructor parameters. If you have multiple value objects in an action method
signature, and those value objects use the same parameter names in their
constructors, you will see repetition of those names in the dumped path. This
does not cause any ill effect to AutoRoute itself, though it might be confusing
when reviewing the path strings.

### Capturing Other Request Values

Q: How to capture the hostname? Headers? Query parameters? Body?

A: Read them from your Request object.

For example, in the action:

```php
namespace Project\Http\Foos;

use SapiRequest;

class GetFoos
{
    public function __construct(
        SapiRequest $request,
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
namespace Project\Domain;

class FooService
{
    public function fetch(int $fooId, string $host, string $bar, array $body)
    {
        // ...
    }
}
```
