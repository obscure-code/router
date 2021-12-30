## Requirements

* PHP >= 8.0
* [Composer](https://getcomposer.org/)

## Installation

#### Install with composer

Run the following in your terminal to install Router with [Composer](https://getcomposer.org/).

```
$ composer require obscure-code/router
```

## Basic Usage

Basic example of creating a simple router for simple directory structure.

Create config

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
];
```

Create router class

```
$router = new class (
    __DIR__ . '/include',
    $config,
) extends Router {
    public function patternDefault(string $path): void
    {
        $this->load('header');
        $this->load($path, []);
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

Call router with route

```
$router->call('/directory/index')
```


