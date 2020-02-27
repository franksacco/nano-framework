# Routing package
This package is build on top of Nikic's [FastRoute](https://github.com/nikic/FastRoute) library and provides the
following functionalities:
 - **named routes**: for each route a name can be provided in order to uniquely identify a route;
 - **reverse routing**: when a named route is created, parsed data from the definition can be used by `UrlGenerator`
 to perform reverse routing;
 - **middlewares on a route**: it is possible to specify middlewares that are executed only for some routes.
 
> TODO