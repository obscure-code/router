<?php

declare(strict_types=1);

namespace ObscureCode;

use ObscureCode\Exceptions\NonExistentPatternException;
use ObscureCode\Exceptions\NonExistentScriptException;
use ObscureCode\Exceptions\NotFoundException;

abstract class Router
{
    private array $data = [];
    private array $path = [];
    private array $params = [];
    private array $route = [];
    private ?string $pattern = null;

    public function __construct(
        private string $root,
        private array $config,
        private string $defaultScript = 'index',
    ) {
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
        $this->setRoute($route);
        $this->setPattern();
        $this->callPattern();
    }

    /**
     * @param array $data
     */
    private function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * @param string $route
     *
     * @return void
     */
    private function setRoute(string $route): void
    {
        $route = trim($route);
        $route = trim($route, "/");

        if ($route !== '') {
            $this->route = explode("/", strtolower($route));
        }
    }

    private function setPattern(): void
    {
        $route = empty($this->route) ? [$this->defaultScript] : $this->route;
        $section = $this->config;

        while (isset($route[0], $section[$route[0]])) {
            $section = $section[$route[0]];
            $this->path[] = array_shift($route);
        }

        if (
            isset($section[$this->defaultScript]) &&
            !empty($this->path)
        ) {
            $section = $section[$this->defaultScript];
            $this->path[] = $this->defaultScript;
        }

        if (
            isset($section['pattern']) &&
            is_string($section['pattern'])
        ) {
            $this->params = $route;
            $this->pattern = $section['pattern'];
        }
    }

    private function callPattern(): void
    {
        if ($this->pattern === null) {
            throw new NotFoundException();
        }

        $patternMethod = 'pattern' . ucfirst($this->pattern);

        if (!method_exists($this, $patternMethod)) {
            throw new NonExistentPatternException("Method $patternMethod does not exist");
        }

        $path = implode(DIRECTORY_SEPARATOR, $this->path);

        $this->$patternMethod($path);
    }

    /**
     * @param string $path
     * @param array $data
     */
    public function load(string $path, array $data = []): void
    {
        $fullPath = $this->root . DIRECTORY_SEPARATOR . $path . '.php';

        if (!file_exists($fullPath)) {
            throw new NonExistentScriptException('Script ' . $fullPath . ' does not exists');
        }

        $this->addData($data);

        // phpcs:ignore
        $data = $this->data;

        // phpcs:ignore
        $params = $this->params;

        include $fullPath;
    }
}
