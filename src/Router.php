<?php

declare(strict_types=1);

namespace ObscureCode;

use ObscureCode\Exceptions\NotFoundException;
use ObscureCode\Exceptions\RouterRuntimeException;

abstract class Router
{
    private array $data = [];
    private array $path = [];
    private array $params = [];
    private array $route = [];
    private string $pattern;

    public function __construct(
        private string $root,
        private array $config,
        private string $defaultScript = 'index',
        private string $notFoundPattern = 'error',
    ) {
        $this->pattern = $this->notFoundPattern;
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
        $patternMethod = 'pattern' . ucfirst($this->pattern);

        $path = implode(DIRECTORY_SEPARATOR, $this->path);

        try {
            ob_start();
            $this->$patternMethod($path);
        } catch (NotFoundException $exception) {
            ob_clean();
            $patternMethod = 'pattern' . ucfirst($this->notFoundPattern);
            $this->addData([
                'message' => $exception->getMessage(),
            ]);
            http_response_code(404);
            $this->$patternMethod($path);
        } finally {
            ob_end_flush();
        }
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
