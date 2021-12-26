<?php

declare(strict_types=1);

namespace ObscureCoder;

use ObscureCoder\Exceptions\RouterException;

abstract class Router
{
    private string $file;
    private array $data = [];
    private array $config;
    private string $path = '';
    private string $root;

    public function __construct(
        string $root,
        string $route,
        array $config,
    ) {
        $this->root = $root;
        $this->config = $config;

        $this->buildPath(
            $this->processRoute($route)
        );
        $this->callPattern();
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
     * @param $route
     */
    private function buildPath($route): void
    {
        if (
            isset($route[0]) &&
            is_dir($this->root . "include/" . $route[0])
        ) {
            $directory = $route[0];
            $this->path .= $directory . "/";
            array_shift($route);
        }

        if (isset($route[0])) {
            $this->file = $route[0];
        }

        $this->path .= $this->file;
        array_shift($route);

        foreach ($route as $value) {
            $this->data['args'] = $value;
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

        if (array_key_exists($this->path, $this->config)) {
            $pattern = 'pattern_' . $this->config[$this->path]['pattern'];
        } else {
            $pattern = 'pattern_error';
        }

        if (!method_exists($this, $pattern)) {
            throw new RouterException('Pattern ' . $pattern . ' not created');
        }

        if (isset($this->routes[$this->path]['data'])) {
            $this->data = $this->routes[$this->path]['data'];
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
        $path = $this->root . "/include/{$name}.php";

        if (!file_exists($path)) {
            throw new RouterException('File '.$name.'.php does not exists');
        }

        if (isset($data['args'])) {
            throw new RouterException('Given data cannot contains "args" key');
        }

        $data = array_merge($this->data, $data);

        include($path);
    }
}