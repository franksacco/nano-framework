# Routing package

This package is build on top of Nikic's [FastRoute](https://github.com/nikic/FastRoute) library and adds the
following functionalities:
 - **named routes**: for each route a name can be provided in order to uniquely identify a route;
 - **reverse routing**: when a named route is created, parsed data from the definition can be used by the
 `Nano\Routing\FastRoute\UrlGenerator` class to perform reverse routing;
 - **middlewares on a route**: it is possible to specify middlewares that are executed only for some routes;
 - **object oriented wrapper**: you can refer to the FastRoute routing engine through the
 `Nano\Routing\FastRoute\FastRoute` class;
 - **comfortable results**: the result of the dispatcher is a class implementing the
 `Nano\Routing\FastRoute\Result\RoutingResultInterface` interface;
 - **routing as a middleware**: routes definition and URI dispatching are inside the
 `Nano\Routing\AbstractRoutingMiddleware` class.

### Table of contents

 - [Usage](#usage)
   - [Example](#example)
   - [Defining routes](#defining-routes)
     - [Route groups](#route-groups)
     - [Middlewares on a route](#middlewares-on-a-route)
   - [Reverse routing](#reverse-routing)
   - [Caching](#caching)
   - [Dispatching a URI](#dispatching-a-uri)
     - [Request dispatching using the middleware](#request-dispatching-using-the-middleware)
     - [Manual request dispatching](#manual-request-dispatching)
 - [API](#api)
   - [`Nano\Routing\AbstractRoutingMiddleware`](#nanoroutingabstractroutingmiddleware)
   - [`Nano\Routing\FastRoute\FastRoute`](#nanoroutingfastroutefastroute)
   - [`Nano\Routing\FastRoute\Router`](#nanoroutingfastrouterouter)
   - [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute)
   - [`Nano\Routing\FastRoute\RouteGroup`](#nanoroutingfastrouteroutegroup)

## Usage

The documentation below is based on the FastRoute documentation but has been adapted to the functionalities of this
package.

### Example

Here's a basic usage example:
```php
use Nano\Routing\AbstractRoutingMiddleware;
use Nano\Routing\FastRoute\RouteGroup;
use Nano\Routing\FastRoute\Router;

class MyRoutingMiddleware extends AbstractRoutingMiddleware
{
    public function routing(Router $router){
        $router->get('/home', HomeController::class, 'home');
        // {id} must be a number (\d+)
        $router->get('/posts/{id:\d+}', 'App\Controller\PostsController@show', 'posts.show');
        // /login suffix is optional
        $router->get('/admin[/login]', [AdminLoginController::class, 'index'], 'admin_login');
        $router->post('/admin/login', [AdminLoginController::class, 'login']);
        // Group with common prefix and middlewares
        $router->group('/admin', function (RouteGroup $router) {
            $router->get('/users', [UsersController::class, 'index'], 'users.index');
        })->middleware(new YourAuthMiddleware());
    }
}
```

### Defining routes

The routes are defined by extending the
[`Nano\Routing\AbstractRoutingMiddleware`](#nanoroutingabstractroutingmiddleware) class and implementing the `routing()`
method which takes a [`Nano\Routing\FastRoute\Router`](#nanoroutingfastrouterouter) instance. But you can also use only
the [`Nano\Routing\FastRoute\FastRoute`](#nanoroutingfastroutefastroute) wrapper and provide a route definition
callback as the first parameter of the constructor.

Routes will run in the order they are defined. Higher routes will always take precedence over lower ones.

The routes are added by calling `route()` on the router instance:

```php
$router->route($method, $route, $handler, $name);
```

The `$method` is an uppercase HTTP method string for which a certain route should match. For the `GET`, `POST`, `PUT`,
`PATCH`, `DELETE` and `HEAD` request methods shortcut methods are available. For example:

```php
$router->get('/get-route', 'get_handler');
$router->post('/post-route', 'post_handler');
```

Is equivalent to:

```php
$router->route('GET', '/get-route', 'get_handler');
$router->route('POST', '/post-route', 'post_handler');
```

By default, the `$route` parameter uses a syntax where `{foo}` specifies a placeholder with name `foo`and matching the
regex `[^/]+`. To adjust the pattern the placeholder matches, you can specify a custom pattern by writing
`{bar:[0-9]+}`. Some examples:

```php
// Matches /user/42, but not /user/xyz
$router->get('/users/{id:\d+}', 'handler');

// Matches /user/foobar, but not /user/foo/bar
$router->get('/users/{name}', 'handler');

// Matches /user/foo/bar as well
$router->get('/users/{name:.+}', 'handler');
```

Custom patterns for route placeholders cannot use capturing groups. For example `{lang:(en|de)}` is not a valid
placeholder, because `()` is a capturing group. Instead you can use either `{lang:en|de}` or `{lang:(?:en|de)}`.

Furthermore parts of the route enclosed in `[...]` are considered optional, so that `/foo[bar]` will match both `/foo`
and `/foobar`. Optional parts are only supported in a trailing position, not in the middle of a route.

```php
// This route
$router->get('/users/{id:\d+}[/{name}]', 'handler');
// Is equivalent to these two routes
$router->get('/users/{id:\d+}', 'handler');
$router->get('/users/{id:\d+}/{name}', 'handler');

// Multiple nested optional parts are possible as well
$router->get('/users[/{id:\d+}[/{name}]]', 'handler');

// This route is NOT valid, because optional parts can only occur at the end
$router->get('/users[/{id:\d+}]/{name}', 'handler');
```

A valid `$handler` can be any of the following:
 - a string in the format `"class::method"`,
 - a string in the format `"class@method"`,
 - the name of a class implementing `__invoke()` method,
 - an instance of a class implementing `__invoke()` method,
 - an array in the format `["class", "method"]`,
 - an array in the format `[object, "method"]`,
 - the name of a function,
 - a closure (anonymous function).
 
Each method in the list above is suggested to be non-static, but it can also be static.

#### Route Groups

Additionally, you can specify routes inside of a group. All routes defined inside a group will have a common prefix.

For example, defining your routes as:

```php
$router->group('/admin', function (Router $router) {
    $router->route('GET', '/do-something', 'handler');
    $router->route('GET', '/do-another-thing', 'handler');
    $router->route('GET', '/do-something-else', 'handler');
});
```

Will have the same result as:

 ```php
$router->route('GET', '/admin/do-something', 'handler');
$router->route('GET', '/admin/do-another-thing', 'handler');
$router->route('GET', '/admin/do-something-else', 'handler');
 ```

Nested groups are also supported, in which case the prefixes of all the nested groups are combined.

#### Middlewares on a route

It is possible to specify middlewares that are executed only for some routes or for some groups of routes. This can be
done using the `middleware($middleware)` method provided by [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute)
or [`Nano\Routing\FastRoute\RouteGroup`](#nanoroutingfastrouteroutegroup) classes.

The `$middleware` parameter can be an instance of `Psr\Http\Server\MiddlewareInterface` or a callable that accepts
two arguments:
 - `Psr\Http\Message\ServerRequestInterface $request`: the PSR-3 server request,
 - `Psr\Http\Server\RequestHandlerInterface $handler`: the PSR-3 request handler,

and returns a `Psr\Http\Message\ResponseInterface` instance. Alternately, can be a classname or a string that
identifies an instance of `Psr\Http\Server\MiddlewareInterface` from the optional DI container provided to
[`Nano\Routing\FastRoute\FastRoute`](#nanoroutingfastroutefastroute).

### Reverse routing

To use reverse routing, we need named routes which allow the convenient generation of URLs or redirects for specific
routes. You may specify a name for a route by the `$name` parameter in `route()` method:

```php
$router->get('/login', [LoginController::class, 'index'], 'login');
$router->get('/posts/{id:\d+}', [PostsController::class, 'show'], 'posts.show');
$router->get(
    '/posts/{id:\d+}/comments/{comment_id:\d+}',
    [CommentsController::class, 'show'],
    'posts.comments.show'
);
```

You can obtain an instance of [`Nano\Routing\FastRoute\UrlGenerator`](#nanoroutingfastrouteurlgenerator) class through
the `getUrlGenerator()` method  in `FastRoute` class. Then you can use the `getPath(string $name, ...$params)` method
to generate a path from a route name. Make sure that the number of parameters that you provide to the url generator is
correct or an exception will be thrown. 

```php
$urlGenerator = $fastRoute->getUrlGenerator();

// Returns '/login'
$urlGenerator->getPath('login');
// Returns '/posts/123'
$urlGenerator->getPath('posts.show', 123);
// This throws an exception because there are not enough params
$urlGenerator->getPath('posts.comments.show', 123);
// Returns '/posts/123/comments/4'
$urlGenerator->getPath('posts.comments.show', 123, 4);
```

### Caching

The reason [`Nano\Routing\FastRoute\FastRoute`](#nanoroutingfastroutefastroute) constructor accepts a callback for
defining the routes is to allow seamless caching. It is possible to enable this feature passing `true` as the first
parameter in `loadData()` and specify the cache directory as the second parameter:

```php
use Nano\Routing\FastRoute\FastRoute;
use Nano\Routing\FastRoute\Router;

$fastRoute = new FastRoute(function(Router $router) {
    $router->get('/users', 'UsersController@index', 'users.index');
    $router->get('/users/{id:[0-9]+}', 'UsersController::show', 'users.show');
    $router->get('/users/{name}', [UsersController::class, 'search'], 'users.search');
});
$fastRoute->loadData(true, __DIR__ . '/cache/routing');
```

> :warning: When caching is enabled, do not use anonymous function as handlers
  as direct closure serialization is not allowed by PHP.

### Dispatching a URI

#### Request dispatching using the middleware

[`Nano\Routing\AbstractRoutingMiddleware`](#nanoroutingabstractroutingmiddleware) class takes care of all that concern
the dispatch of a server request to the corresponding handler, so you don't have to worry about it.

> In the middleware, the request path is transformed in order to have always a leading `/` and never a trailing `/`.
  In this way, paths `/example` and `/example/` give the same result.

In case of a positive routing result, the middleware adds two attribute to the server request:
 - `Nano/Routing/Dispatcher::REQUEST_HANDLER_ACTION`: the handler associated to the active route,
 - `Nano/Routing/Dispatcher::REQUEST_HANDLER_PARAMS`: an associative array containing route parameters.

If a correct route is not found, the middleware uses the server response factory provided in its constructor to create
a `404 Not Found` or `405 Method Not Allowed` error response.

#### Manual request dispatching

A URI is dispatched by calling the `dispatch()` method of the
[`Nano\Routing\FastRoute\FastRoute`](#nanoroutingfastroutefastroute) class. This method accepts the HTTP method and a
URI. Getting those two bits of information (and normalizing them appropriately) is your job.

Remember always to call `loadData()` method before calling `dispatch()` or `getUrlGenerator()`.

The `dispatch()` method returns an object implementing `Nano\Routing\FastRoute\Result\RoutingResultInterface` and
representing the result of the dispatching.

The result is an instance of the following 3 classes:

 - `Nano\Routing\FastRoute\Result\FoundResult`: this represents a _Found_ routing result. This object provides:
   - the list of middlewares associated to the route through the `getMiddlewares()` method;
   - the handler associated to the route through the `getHandler()` method;
   - the list of parameters associated with the route through the `getParams()` method.
   
   `Nano/Routing/RouteRequestHandler` class can be used to executes middlewares associated to the route.

 - `Nano\Routing\FastRoute\Result\MethodNotAllowedResult`: this represents a _Method Not Allowed_ routing result. This
    object provides the list of allowed HTTP methods through the `getAllowedMethods()` method.
    
    > **Note:** The HTTP specification requires that a `405 Method Not Allowed` response includes the `Allow:` header
      to detail available methods for the requested resource. Applications using this library should use the array
      retrieved to add this header when relaying a 405 response.

 - `Nano\Routing\FastRoute\Result\NotFoundResult`: this represents a _Not Found_ routing result.


## API

### `Nano\Routing\AbstractRoutingMiddleware`

Wrapper middleware for FastRoute library.

<br />

```php
public function __construct(ContainerInterface $container,
                            ConfigurationInterface $config,
                            ResponseFactoryInterface $factory)
```
Initialize the routing middleware.\
**Parameters**\
&nbsp;&nbsp;`Psr\Container\ContainerInterface $container` The DI container.\
&nbsp;&nbsp;`Nano\Config\ConfigurationInterface $config` The application settings.\
&nbsp;&nbsp;`Psr\Http\Message\ResponseFactoryInterface $factory` The server response factory used to create
a 404 or 405 error response.

<br />

```php
abstract public function routing(Router $router)
```
Define application routes.\
**Parameters**\
&nbsp;&nbsp;[`Nano\Routing\FastRoute\Router`](#nanoroutingfastrouterouter)`$router` The router instance.

<br />

```php
public function process(ServerRequestInterface $request,
                        RequestHandlerInterface $handler): ResponseInterface
```
Process an incoming server request.\
**Parameters**\
&nbsp;&nbsp;`Psr\Http\Message\ServerRequestInterface $request` The server request.\
&nbsp;&nbsp;`Psr\Http\Server\RequestHandlerInterface $handler` The request handler.\
**Return** `Psr\Http\Message\ResponseInterface` Returns the server response.

---

### `Nano\Routing\FastRoute\FastRoute`

Wrapper class for FastRoute routing engine.

<br />

```php
public function __construct(callable $routeDefinitionCallback,
                            ?RouteParser $routeParser = null,
                            ?DataGenerator $dataGenerator = null,
                            ?string $dispatcherClass = null,
                            ?ContainerInterface $container = null)
```
Define application routes.\
**Parameters**\
&nbsp;&nbsp;`callable $routeDefinitionCallback` The callback used to define routes; this callable expects a
Router instance as unique parameter.\
&nbsp;&nbsp;`FastRoute\RouteParser|null $routeParser` _\[optional\]_ The route parser;
default: `FastRoute\RouteParser\Std`.\
&nbsp;&nbsp;`FastRoute\DataGenerator|null $dataGenerator` _\[optional\]_ The data generator;
default: `FastRoute\DataGenerator\GroupCountBased`.\
&nbsp;&nbsp;`string|null $dispatcherClass` _\[optional\]_ The dispatcher class name;
default: `FastRoute\Dispatcher\GroupCountBased`.\
&nbsp;&nbsp;`Psr\Container\ContainerInterface|null $container` _\[optional\]_ The DI container
used to resolve middleware definitions.

<br />

```php
public function loadData(bool $cache = false, string $cacheDir = null)
```
Load routing data by cache or the given callback.\
**Parameters**\
&nbsp;&nbsp;`bool $cache` _\[optional\]_ Whether the caching is enabled or not; default: `false`.\
&nbsp;&nbsp;`string $cacheDir` _\[optional\]_ The directory where to save cache files.\
**Throws**\
&nbsp;&nbsp;`Nano\Routing\FastRoute\RoutingException` if an error occur during data loading.

<br />

```php
public function dispatch(string $method, string $path): Result\RoutingResultInterface
```
Perform the URI dispatching.\
**Parameters**\
&nbsp;&nbsp;`string $method` _\[optional\]_ Whether the caching is enabled or not; default: `false`.\
&nbsp;&nbsp;`string $path` _\[optional\]_ The directory where to save cache files.\
**Return** `Nano\Routing\FastRoute\Result\RoutingResultInterface` Returns an object representing the result of the
dispatching.\
**Throws**\
&nbsp;&nbsp;`Nano\Routing\FastRoute\RoutingException` if the routing data has not yet been loaded.

<br />

```php
public function getUrlGenerator(): UrlGenerator
```
Get the url generator.\
**Return** [`Nano\Routing\FastRoute\UrlGenerator`](#nanoroutingfastrouteurlgenerator) Returns the url generator.\
**Throws**\
&nbsp;&nbsp;`Nano\Routing\FastRoute\RoutingException` if the routing data has not yet been loaded.

---

### `Nano\Routing\FastRoute\Router`

Routes collector and manager.

<br />

```php
public function __construct(RouteParser $routeParser,
                            DataGenerator $dataGenerator,
                            ?ContainerInterface $container = null)
```
Initialize the router.\
**Parameters**\
&nbsp;&nbsp;`FastRoute\RouteParser $routeParser` The route parser.\
&nbsp;&nbsp;`FastRoute\DataGenerator $dataGenerator` The data generator.\
&nbsp;&nbsp;`Psr\Container\ContainerInterface|null $container` _\[optional\]_ The DI container.

<br />

```php
public function route(string $method, string $route, $handler, ?string $name = null): Route
```
Adds a route to the collection.\
The syntax used in the `$route` string depends on the used route parser.\
**Parameters**\
&nbsp;&nbsp;`string $method` The HTTP method of the route.\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string|null $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function get(string $route, $handler, string $name = null): Route
```
Adds a GET route to the collection.\
This is an alias of `$this->route('GET', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function head(string $route, $handler, string $name = null): Route
```
Adds a HEAD route to the collection.\
This is an alias of `$this->route('HEAD', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function post(string $route, $handler, string $name = null): Route
```
Adds a POST route to the collection.\
This is an alias of `$this->route('POST', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function put(string $route, $handler, string $name = null): Route
```
Adds a PUT route to the collection.\
This is an alias of `$this->route('PUT', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function patch(string $route, $handler, string $name = null): Route
```
Adds a PATCH route to the collection.\
This is an alias of `$this->route('PATCH', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function delete(string $route, $handler, string $name = null): Route
```
Adds a DELETE route to the collection.\
This is an alias of `$this->route('DELETE', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function getRoutes(): array
```
Get the list of defined routes.\
**Return** `Route[]` Returns the list of routes.

<br />

```php
public function group(string $prefix, callable $callback): RouteGroup
```
Create a route group with a common prefix.\
All routes created in the passed callback will have the given group prefix prepended.\
**Parameters**\
&nbsp;&nbsp;`string $prefix` The prefix of the group.\
&nbsp;&nbsp;`callable $callback` The callable used to define routes that expects as single argument
an instance of [RouteGroup](#nanoroutingfastrouteroutegroup).\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function getGroups(): array
```
Get the list of defined groups.\
**Return** `RouteGroup[]` Returns the group list.

<br />

```php
public function getContainer(): ?ContainerInterface
```
Get the DI container, if defined.\
**Return** `ContainerInterface|null` Returns the DI container if defined, `null` otherwise.

<br />

```php
public function getData(): array
```
Returns the collected route data, as provided by the data generator.\
**Return** `array`

<br />

```php
public function getReverseData(): array
```
Retrieve data used for reverse routing.\
The returned array is in the form: `route_name => route_parsed_data`. This data can be used by
[`UrlGenerator`](#nanoroutingfastrouteurlgenerator) to perform reverse routing.\
**Return** `array` Returns the named routes with parsed data.

---

### `Nano\Routing\FastRoute\Route`

Representation of a route.

<br />

```php
public function __construct(string $method,
                            string $route,
                            $handler,
                            ?string $name = null,
                            ?ContainerInterface $container = null)
```
Initialize the route.\
**Parameters**\
&nbsp;&nbsp;`string $method` The HTTP method of the route.\
&nbsp;&nbsp;`string $route` The HTTP method of the route.\
&nbsp;&nbsp;`mixed $handler` The HTTP method of the route.\
&nbsp;&nbsp;`string|null $name` _\[optional\]_ The HTTP method of the route.\
&nbsp;&nbsp;`Psr\Container\ContainerInterface|null $container` _\[optional\]_ The HTTP method of the route.

<br />

```php
public function getMethod(): string
```
Get the HTTP method of the route.\
**Return** `string` Returns the HTTP method.

<br />

```php
public function getRoute(): string
```
Get the route pattern.\
**Return** `string` Returns the route pattern.

<br />

```php
public function getHandler()
```
Get the handler of the route.\
**Return** `mixed` Returns the handler.

<br />

```php
public function getName(): ?string
```
Get the name of the route.\
**Return** `string|null` Returns the route name if set, `null` otherwise.

<br />

```php
public function middleware($middleware): self
```
Add a middleware to this route.\
**Parameters**\
&nbsp;&nbsp;`Psr\Http\Server\MiddlewareInterface|string|callable $middleware` The middleware.\
**Return** `self` Returns self reference for method chaining.

<br />

```php
public function getMiddlewares(): array
```
Get the list of middlewares.\
**Return** `Psr\Http\Server\MiddlewareInterface[]` Returns the middleware list.

---

### `Nano\Routing\FastRoute\RouteGroup`

Representation of a route group with a common prefix.

<br />

```php
public function __construct(string $prefix, callable $callback, ?ContainerInterface $container = null)
```
Initialize the route group.\
**Parameters**\
&nbsp;&nbsp;`string $prefix` The prefix of the group.\
&nbsp;&nbsp;`callable $callback` The callable that defines group routes.\
&nbsp;&nbsp;`Psr\Container\ContainerInterface|null $container` _\[optional\]_ The DI container.

<br />

```php
public function getPrefix(): string
```
Get the prefix of the route group.\
**Return** `string` Returns the prefix of the group.

<br />

```php
public function route(string $method, string $route, $handler, ?string $name = null): Route
```
Adds a route to the collection.\
The syntax used in the `$route` string depends on the used route parser.\
**Parameters**\
&nbsp;&nbsp;`string $method` The HTTP method of the route.\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string|null $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function get(string $route, $handler, string $name = null): Route
```
Adds a GET route to the collection.\
This is an alias of `$this->route('GET', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function head(string $route, $handler, string $name = null): Route
```
Adds a HEAD route to the collection.\
This is an alias of `$this->route('HEAD', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function post(string $route, $handler, string $name = null): Route
```
Adds a POST route to the collection.\
This is an alias of `$this->route('POST', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function put(string $route, $handler, string $name = null): Route
```
Adds a PUT route to the collection.\
This is an alias of `$this->route('PUT', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function patch(string $route, $handler, string $name = null): Route
```
Adds a PATCH route to the collection.\
This is an alias of `$this->route('PATCH', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function delete(string $route, $handler, string $name = null): Route
```
Adds a DELETE route to the collection.\
This is an alias of `$this->route('DELETE', $route, $handler, $name)`.\
**Parameters**\
&nbsp;&nbsp;`string $route` The pattern of the route.\
&nbsp;&nbsp;`mixed $handler` The handler of the route.\
&nbsp;&nbsp;`string $name` _\[optional\]_ The name of the route.\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function getRoutes(): array
```
Get the list of defined routes.\
**Return** `Route[]` Returns the list of routes.

<br />

```php
public function group(string $prefix, callable $callback): RouteGroup
```
Create a route group with a common prefix.\
All routes created in the passed callback will have the given group prefix prepended.\
**Parameters**\
&nbsp;&nbsp;`string $prefix` The prefix of the group.\
&nbsp;&nbsp;`callable $callback` The callable used to define routes that expects as single argument
an instance of [RouteGroup](#nanoroutingfastrouteroutegroup).\
**Return** [`Nano\Routing\FastRoute\Route`](#nanoroutingfastrouteroute) Returns the created route.

<br />

```php
public function getGroups(): array
```
Get the list of defined groups.\
**Return** `RouteGroup[]` Returns the group list.

<br />

```php
public function middleware($middleware): self
```
Add a middleware to this route.\
**Parameters**\
&nbsp;&nbsp;`Psr\Http\Server\MiddlewareInterface|string|callable $middleware` The middleware.\
**Return** `self` Returns self reference for method chaining.

<br />

```php
public function getMiddlewares(): array
```
Get the list of middlewares.\
**Return** `Psr\Http\Server\MiddlewareInterface[]` Returns the middleware list.

---

### `Nano\Routing\FastRoute\UrlGenerator`

This class provides a path generator from named routes.

<br />

```php
public function __construct(array $data)
```
Initialize the url generator.\
**Parameters**\
&nbsp;&nbsp;`array $data` The named routes with parsed data.

<br />

```php
public function getPath(string $name, ...$params): string
```
Generate a path from a route name.\
**Parameters**\
&nbsp;&nbsp;`string $name` The name of a route.\
&nbsp;&nbsp;`mixed ...$params` The parameters of the route.\
**Return** `string` Returns the path associated to the route and parameters.\
**Throws**\
&nbsp;&nbsp;`Nano\Routing\FastRoute\RoutingException` if an error occur during the path generation.