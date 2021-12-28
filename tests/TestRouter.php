<?php

declare(strict_types=1);

namespace ObscureCode\Tests;

use ObscureCode\Router;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class TestRouter extends TestCase
{
    private Router $testRouter;

    public function setUp(): void
    {
        $config = [
            "index" => [
                "pattern" => "default",
            ],
            "data" => [
                "pattern" => "data",
            ],
            "params" => [
                "pattern" => "params",
            ],
            "directory" => [
                "index" => [
                    "pattern" => "default",
                ],
                "non-existent-script" => [
                    "pattern" => "default",
                ],
            ],
        ];

        $this->testRouter = new class (
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

            public function patternData(string $path): void
            {
                $this->load($path);
            }

            public function patternParams(string $path): void
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
    }

    public function testRouterWithGoodData(): void
    {
        $this->testRouter->call(
            '/index',
        );

        $expectedOutput = PHP_EOL . 'This is header.php!' . PHP_EOL;
        $expectedOutput .= 'This is index.php!' . PHP_EOL;
        $expectedOutput .= 'This is footer.php!' . PHP_EOL;

        $this->expectOutputString($expectedOutput);
    }

    public function testRouterWithDirectoryRoute(): void
    {
        $this->testRouter->call(
            '/directory/',
        );

        $expectedOutput = PHP_EOL . 'This is header.php!' . PHP_EOL;
        $expectedOutput .= 'This is directory/index.php!' . PHP_EOL;
        $expectedOutput .= 'This is footer.php!' . PHP_EOL;

        $this->expectOutputString($expectedOutput);
    }

    public function testRouterWithMissedPattern(): void
    {
        $expectedOutput = PHP_EOL . 'This is header.php!' . PHP_EOL;
        $expectedOutput .= 'This is error.php!' . PHP_EOL;
        $expectedOutput .= 'This is footer.php!' . PHP_EOL;

        $this->expectOutputString($expectedOutput);

        $this->testRouter->call(
            '/non-existent-route',
        );
    }

    public function testRouterWithData(): void
    {
        ob_start();

        $this->testRouter->call(
            '/data',
            ['test_data_from_call' => 200],
        );

        $output = ob_get_clean();

        $this->assertEquals(
            var_export(['test_data_from_call' => 200], true),
            $output,
        );
    }

    public function testRouterWithParams(): void
    {
        ob_start();

        $this->testRouter->call(
            '/params/a/b/c',
        );

        $output = ob_get_clean();

        $this->assertEquals(
            var_export(['a', 'b', 'c'], true),
            $output,
        );
    }
}
