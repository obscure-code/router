<?php

declare(strict_types=1);

namespace ObscureCode;

use ObscureCode\Exceptions\RouterRuntimeException;

abstract class Router
{
    private array $data = [];
    private array $path = [];
    private array $params = [];
    private array $route = [];

    public function __construct(
        private string $root,
        private array $config,
        private string $defaultScript = 'index',
        private string $defaultErrorPattern = 'error',
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

        $this->route = $this->setRoute($route);
        $pattern = $this->getPattern();
        $this->callPattern($pattern);
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
     * @return list<string>
     */
    private function setRoute(string $route): array
    {
        $route = trim($route);
        $route = trim($route, "/");

        return explode("/", strtolower($route));
    }

    /**
     * @return string
     */
    private function getPattern(): string
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

            return $section['pattern'];
        }

        return $this->defaultErrorPattern;
    }

    /**
     * @param string $pattern
     */
    private function callPattern(string $pattern): void
    {
        $patternMethod = 'pattern' . ucfirst($pattern);

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
            throw new RouterRuntimeException('Script ' . $fullPath . ' does not exists');
        }

        $this->addData($data);

        // phpcs:ignore
        $data = $this->data;

        // phpcs:ignore
        $params = $this->params;

        include $fullPath;
    }
}
