# Configuration

This package provides a very easy way to manage configuration items throughout the application.

### Table of contents
 - [Usage](#usage)
   - [Retrieve configuration items](#retrieve-configuration-items)
 - [API](#api)
   - [`Nano\Config\Configuration`](#nanoconfigconfiguration)
   - [`Nano\Config\ArrayDotNotationTrait`](#nanoconfigarraydotnotationtrait)

## Usage

Here is a typical example showing how you can store configurations for your entire application:

```php
return [
    'debug' => true,
    'log' => [
        // Settings for logging.
    ],
    'database' => [
        // Settings for database connection and driver.
    ],
    // ...
];
```

This file can be included and the returned array can be passed to the constructor of
[`Nano\Config\Configuration`](#nanoconfigconfiguration) to initialize your configurations manager.
However, it is possible to use array data from any source for this purpose.

```php
$data = require 'config/development.php';

$config = new Configuration($data);
```

By default, you should pass the settings array to the constructor of `Nano\Middleware\AbstractApplication`.
This class creates the configuration instance and adds it to the DI container.
The `Nano/Config/ConfigurationInterface` interface is provided so that it is possible to use the application
configurations in any class resolved through the DI container.

### Retrieve configuration items

Configurations are intended to be read-only, so [`Nano\Config\Configuration`](#nanoconfigconfiguration) class not
implements any method to modify its input values.

It is possible to check if a configuration item exists using `has` method:

```php
if ($config->has('key')) {
    // Do stuff...
}
```

You can get configuration value through the `get` method. Optionally, you can provide a default value that is
returned when the item is not set using the `$default` parameter.

```php
$value = $config->get('key', 'default');
``` 

[`Nano\Config\Configuration`](#nanoconfigconfiguration) uses internally the
[`Nano\Config\ArrayDotNotationTrait`](#nanoconfigarraydotnotationtrait) trait that helps you to access array using
**dot notation**. With this notation, dots are treated as an operator for accessing nested values.

```php
$config->get('foo.bar.qux');
// Is equal to:
$config->get('foo')['bar']['qux'];
```


## API

### `Nano\Config\Configuration`

Collector for application configuration items.

```php
public function has(string $key): bool
```
Check if a configuration item exists.\
You can use dot notation to access array elements.\
If a prefix is set, it is prepended to `$key`.\
**Parameters**\
&nbsp;&nbsp;`string $key` The key of the item to check.\
**Return** `bool` Returns `true` if the item is set, `false` otherwise.

<br />

```php
public function get(string $key, $default = null)
```
Get the specified configuration item.\
You can use dot notation to access array elements.\
If a prefix is set, it is prepended to `$key`.\
**Parameters**\
&nbsp;&nbsp;`string $key` The key of the item to obtain.\
&nbsp;&nbsp;`mixed $default` _\[optional\]_ The return value when the key is not set.\
**Return** `mixed` Returns configuration item if set, `$default` otherwise.

<br />

```php
public function all(): array
```
Retrieve all of the configuration items.\
If a prefix is set, this method returns all configuration items associated to it.\
**Return** `array`

<br />

```php
public function withPrefix(string $prefix): ConfigurationInterface
```
Get the configuration with a fixed prefix.\
You can use dot notation to access array elements.\
**Parameters**\
&nbsp;&nbsp;`string $prefix` The prefix that refers to an array.\
**Return** `Nano\Config\ConfigurationInterface` Returns a copy of this instance with a
fixed prefix for the provided keys.

<br />

```php
public function getPrefix(): string
```
Get the key prefix for this instance.\
**Return** `string`

---

### `Nano\Config\ArrayDotNotationTrait`

Helper trait for access arrays using dot notation.

```php
public function hasItem(array $array, string $key): bool
```
Check if the key is set in the array.\
**Parameters**\
&nbsp;&nbsp;`array $array` The array.\
&nbsp;&nbsp;`string $key` The key of the item.\
**Return** `bool` Returns `true` if the key is set, `false` otherwise.

<br />

```php
public function getItem(array $array, string $key, $default = null)
```
Retrieve the value of an item in the array.\
**Parameters**\
&nbsp;&nbsp;`array $array` The array.\
&nbsp;&nbsp;`string $key` The key of the item.\
&nbsp;&nbsp;`mixed $default` _\[optional\]_ The default value.\
**Return** `mixed` Returns item value if set, `$default` otherwise.

<br />

```php
public function setItem(array &$array, string $key, $value)
```
Set the value of an item in the array.\
**Parameters**\
&nbsp;&nbsp;`array &$array` The reference to the array.\
&nbsp;&nbsp;`string $key` The key of the item.\
&nbsp;&nbsp;`mixed $value` The value of the item.\
**Throws**\
&nbsp;&nbsp;`\UnexpectedValueException` when attempting to set a non-array value.