<?php

/** @psalm-var array $data*/

declare(strict_types=1);

$message = $data['message'] ?? 'This is error.php!';

echo $message . PHP_EOL;
