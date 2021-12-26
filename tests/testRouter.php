<?php

declare(strict_types=1);

use ObscureCoder\Exceptions\RouterException;
use ObscureCoder\Router;
use PHPUnit\Framework\TestCase;

final class testRouter extends TestCase
{
    public function testRouterWithGoodData(): void
    {
        $config = [
            "index" => [
                "pattern" => "default",
                "data" => [
                    "title" => "page title",
                    "description" => "page description"
                ]
            ],
            "error" => ["pattern" => "error"],
        ];

        $testRouter = new class(
            __DIR__,
            '/index',
            $config,
        ) extends Router {
            public function pattern_default($path): void
            {
                $this->load('header');
                $this->load($path, []);
                $this->load('footer');
            }

            public function pattern_blank($path): void
            {
                $this->load($path);
            }

            public function pattern_error($path): void
            {
                http_response_code(404);
                $this->load('header');
                $this->load('error');
                $this->load('footer');
            }
        };

        $expectedOutput = PHP_EOL . 'This is header.php!' . PHP_EOL;
        $expectedOutput .= 'This is index.php!' . PHP_EOL;
        $expectedOutput .= 'This is footer.php!' . PHP_EOL;

        $this->expectOutputString($expectedOutput);
    }

    public function testRouterWithBadData(): void
    {
        $config = [
            "index" => [
                "pattern" => "default",
                "data" => [
                    "title" => "page title",
                    "description" => "page description"
                ]
            ],
            "error" => ["pattern" => "error"],
        ];

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Pattern missed for non-existent');

        $testRouter = new class(
            __DIR__,
            '/non-existent',
            $config,
        ) extends Router {
            public function pattern_default($path): void
            {
                $this->load('header');
                $this->load($path, []);
                $this->load('footer');
            }

            public function pattern_blank($path): void
            {
                $this->load($path);
            }

            public function pattern_error($path): void
            {
                http_response_code(404);
                $this->load('header');
                $this->load('error');
                $this->load('footer');
            }
        };
    }
}