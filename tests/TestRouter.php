<?php

declare(strict_types=1);

namespace ObscureCode\Tests;

use ObscureCode\Exceptions\NonExistentPatternException;
use ObscureCode\Exceptions\NonExistentScriptException;
use ObscureCode\Exceptions\NotFoundException;
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
            "not_found" => [
                "pattern" => "default",
            ],
            "data" => [
                "pattern" => "data",
            ],
            "params" => [
                "pattern" => "params",
            ],
            "bad_pattern" => [
                "pattern" => "non_existent_pattern",
            ],
            "bad_script" => [
                "pattern" => "default",
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

    public function testRouterWithEmptyRoute(): void
    {
        $this->testRouter->call(
            '',
        );

        $expectedOutput = PHP_EOL . 'This is header.php!' . PHP_EOL;
        $expectedOutput .= 'This is index.php!' . PHP_EOL;
        $expectedOutput .= 'This is footer.php!' . PHP_EOL;

        $this->expectOutputString($expectedOutput);
    }

    public function testRouterWithGoodRoute(): void
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

    public function testRouterWithNonExistentRoute(): void
    {
        $this->expectException(NotFoundException::class);

        $this->testRouter->call(
            '/non_existent_route',
        );
    }

    public function testRouterNotFoundExceptionThrown(): void
    {
        $this->expectException(NotFoundException::class);

        $this->testRouter->call(
            '/not_found',
        );
    }

    public function testRouterWithNonExistentPattern(): void
    {
        $this->expectException(NonExistentPatternException::class);

        $this->testRouter->call(
            '/bad_pattern',
        );
    }

    public function testRouterWithNonExistentScript(): void
    {
        $this->expectException(NonExistentScriptException::class);

        $this->testRouter->call(
            '/bad_script',
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
