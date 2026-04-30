<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Config
{
    private static array $items = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException(
                "Config file not found at {$path}. Copy config/config.example.php to config/config.php and edit it."
            );
        }
        self::$items = require $path;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
