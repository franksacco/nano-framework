# Nano Framework
A simple, light and fast PHP framework based on middlewares.

## Create an app
In order to create your application based on Nano Framework, you have to extend the `AbstractApplication` class and
setup the middlewares queue of the application. The method 
`AbstractApplication::middleware(MiddlewareQueue $middleware)` must be overwritten in order to setup the middlewares
queue used to process the server request and create the response. Note that the order in which the middlewares are
added defines the order in which they are executed.\
A basic example:
```php
class YourApplication extends AbstractApplication {
    public function middleware(MiddlewareQueue $middleware)
    {
        $middleware
            ->add(ErrorHandlerMiddleware::class)     // Error/Exception handling.
            ->add(ResponseEmitterMiddleware::class)  // Send response to the user.
            ->add(BufferOutputMiddleware::class)     // Output buffering and compressing.
            ->add(RoutingMiddleware::class);         // Routing engine.
    }
}
```
The `AbstractApplication` class implements `MiddlewareInterface` as it is always the last middleware in the
queue. Its job is to dispatch the server request to an action in order to produce a response. The action to be
executed is a callable set as attribute in the server request. In this example, this is automatically made by
the `RoutingMiddleware`.\
Now, you have to start your application from your `index.php` file:
```php
require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$app = new \App\YourApplication($config);
$app->run();
```
That's it. Now you have to implement the logic of your application.

## Documentation
> :warning: Documentation is under development.

Documentation for this project is available in the [docs folder](docs/README.md).

## License
The Nano Framework is open-sourced software licensed under the [MIT license](README.md).

## Author
[Francesco Saccani](https://github.com/franksacco) (saccani.francesco@gmail.com)