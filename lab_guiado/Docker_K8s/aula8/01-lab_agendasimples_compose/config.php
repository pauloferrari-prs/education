<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

function envValue(string $key, ?string $default = null): string
{
    $value = getenv($key);
    return $value === false ? ($default ?? '') : $value;
}