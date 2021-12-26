<?php

declare(strict_types=1);

namespace ObscureCode;

use ObscureCode\Exceptions\RouterException;

abstract class Router
{
    private array $config;
    private array $data = [];
    private string $path = '';
    private string $root;
    private array $params = [];

    public function __construct(
        string $root,
        array $config,
        private string $defaultFile = 'index',
        private string $defaultErrorPattern = 'error',
    ) {
        if (!str_ends_with($root, '/')) {
            $root .= '/';
        }

        $this->root = $root;
        $this->config = $config;

        if (!method_exists($this, 'pattern' . ucfirst($defaultErrorPattern))) {
            throw new RouterException('Pattern ' . $defaultErrorPattern . ' should be created');
        }
    }

    /**
     * @param string $route
     * @param array $data
     */
    public function call(
        string $route,
        array $data = [],
    ): void {
        $this->addData($data);
        $this->buildPath(
            $this->processRoute($route)
        );
        $this->callPattern();
    }

    private function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * @param string $route
     *
     * @return array
     */
    private function processRoute(string $route): array
    {
        $route = trim($route);
        $route = trim($route, "/");

        return explode("/", strtolower($route));
    }

    /**
     * @param array $route
     */
    private function buildPath(array $route): void
    {
        if (
            isset($route[0]) &&
            is_dir($this->root . $route[0])
        ) {
            $directory = $route[0];
            $this->path .= $directory . "/";
            array_shift($route);
        }

        $file = $route[0] ?? $this->defaultFile;

        $this->path .= $file;
        array_shift($route);

        foreach ($route as $value) {
            $this->params[] = $value;
        }
    }

    /**
     * @throws RouterException
     */
    private function callPattern(): void
    {
        if (!isset($this->config[$this->path]['pattern'])) {
            throw new RouterException('Pattern missed for ' . $this->path);
        }

        $pattern = array_key_exists($this->path, $this->config) ?
            'pattern' . ucfirst($this->config[$this->path]['pattern']) :
            'pattern' . ucfirst($this->defaultErrorPattern);

        if (!method_exists($this, $pattern)) {
            throw new RouterException('Pattern ' . $pattern . ' not created');
        }

        if (isset($this->config[$this->path]['data'])) {
            $this->addData($this->config[$this->path]['data']);
        }

        $this->$pattern($this->path);
    }

    /**
     * @param string $name
     * @param array $data
     *
     * @throws RouterException
     */
    public function load(string $name, array $data = []): void
    {
        $path = $this->root . "{$name}.php";

        if (!file_exists($path)) {
            throw new RouterException('File ' . $this->root . $name . '.php does not exists');
        }

        $this->addData($data);

        // phpcs:ignore
        $data = $this->data;

        // phpcs:ignore
        $params = $this->params;

        include $path;
    }
}
