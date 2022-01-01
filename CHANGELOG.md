# Change Log

## NEXT

- Added Route::asArray()

- The Route now carries the Router messages that led to the route

- Added invokable helper class for route generation

- Added support for root-level catchalls with params

- Route is now JsonSerializable


## 2.0.0

Initial release.

### Upgrading from 1.x to 2.0.0

#### Configuration

In 1.x, the _AutoRoute_ options were configured with setters ...

```php
$autoRoute = new AutoRoute(
    'Project\Http',
    dirname(__DIR__) . '/src/Project/Http/'
);

$autoRoute->setBaseUrl('/api');
$autoRoute->setIgnoreParams(1);
$autoRoute->setMethod('exec');
$autoRoute->setSuffix('Action');
$autoRoute->setWordSeparator('_');
```
... but in 2.x they are configured with named constructor parameters:

```php
$autoRoute = new AutoRoute(
    namespace: 'Project\Http',
    directory: dirname(__DIR__) . '/src/Project/Http/',
    baseUrl: '/api',
    ignoreParams: 1,
    method: 'exec',
    suffix: 'Action',
    wordSeparator: '_',
);
```

#### Retrieving Objects

The methods to retrieve _AutoRoute_ objects have been renamed from `new*()` to
`get*()`:

```php
// 1.x                          // 2.x
$autoRoute->newRouter();        $autoRoute->getRouter();
$autoRoute->newGenerator();     $autoRoute->getGenerator();
$autoRoute->newDumper();        $autoRoute->getDumper();
$autoRoute->newCreator();       $autoRoute->getCreator();
```

#### Route Properties

The 1.x _Route_ property `$params` has been renamed to `$arguments`.

```php
// 1.x
$response = call_user_func([$action, $route->method], ...$route->params);

// 2.x
$response = call_user_func([$action, $route->method], ...$route->arguments);
```

#### Error Handling

In 1.x, the _Router_ would throw exceptions on errors:

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

In 2.x, the _Router_ always returns a _Route_, and captures exceptions into the
returned _Route_ property `$error`. Examine that property instead of catching
exceptions:

```php
use AutoRoute\Exception;

switch ($route->error) {
    case null:
        // no errors! create the action class instance
        // and call it with the method and arguments.
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
        /* N.b.: Examine $route->headers to find the 'allowed' methods for the
        resource, if any. */
        break;

    default:
        $response = /* 500 Server Error */;
        break;
}
```

Note that _InvalidNamespace_ has been combined with _InvalidArgument_ and is no
longer a separate exception type. Likewise, the new `$headers` property contains
suggested headers to return with the response.
