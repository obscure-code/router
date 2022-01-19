## Requirements

* PHP >= 8.0
* [Composer](https://getcomposer.org/)

## Installation

```
$ composer require obscure-code/router
```

## Basic Usage

Create Router class:

```php
use ObscureCode\Router as AbstractRouter;

class Router extends AbstractRouter {
    public function patternDefault(string $path): void
    {
        $this->load('header');
        $this->load($path);
        $this->load('footer');
    }

    public function patternBlank(string $path): void
    {
        $this->load($path);
    }

    public function patternError(string $path): void
    {
        http_response_code(404);
        $this->load('header');
        $this->load('error');
        $this->load('footer');
    }
};
```

Create config:

```php
$config = [
    "index" => [
        "pattern" => "default",
    ],
    "ajax" => [
        "pattern" => "blank",
    ],    
    "directory" => [
        "index" => [
            "pattern" => "default",
        ],
    ],
    "error" => [
        "pattern" => "error",
    ],
];
```

Create Router instance with root directory and config:

```php
$router = new Router(
    __DIR__ . '/include',
    $config,
);

```

Examples:

```php
// Call /include/index.php by default
$router->call('/');

// Call /include/directory/index.php
$router->call('/directory/index');

// If /include/a/b.php route exists, ['c', 'd', 'e'] will passed in $params
// If only /include/a.php exists, ['b', 'c', 'd', 'e'] will passed in $params
$router->call('/a/b/c/d/e');

// ['data'] will passed in $data
$router->call('/index', ['data']);


```

Also you can pass data in `load` method:

```php
public function patternDefault(string $path): void
{
    $someClass = new SomeClass();

    $this->load('header');
    $this->load($path, ['someClass' => $someClass]);
    $this->load('footer');
}
```

If route not found in config, `NotFoundException` will be thrown.
So you can use router like that:
```php
use ObscureCode\Exceptions\NotFoundException;

try {
    ob_start();
    $router->call($route);
} catch (NotFoundException $exception) {
    ob_clean();
    $router->call('error');
} catch (SomeOtherException $exception) {
    // do something
} finally {
    ob_end_flush();
}
```

If route exist in config, but pattern or file not exists, `LogicException` will be thrown.

You can see an example of a boilerplate application [**here**](https://github.com/obscure-code/boilerplate).