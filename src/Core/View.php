<?php
declare(strict_types=1);

namespace GamesPool\Core;

class View
{
    private static string $layout = 'layouts/app';
    private static array $sections = [];
    private static array $stack = [];

    public static function render(string $template, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        $content = ob_get_clean();

        if (isset(self::$sections['_layout'])) {
            $layout = self::$sections['_layout'];
            unset(self::$sections['_layout']);
        } else {
            $layout = null;
        }

        if ($layout !== null) {
            self::$sections['content'] = $content;
            $layoutFile = BASE_PATH . '/views/' . $layout . '.php';
            if (!is_file($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layout}");
            }
            $sections = self::$sections;
            extract($data, EXTR_SKIP);
            ob_start();
            include $layoutFile;
            $content = ob_get_clean();
            self::$sections = [];
        }
        return $content;
    }

    public static function extend(string $layout): void
    {
        self::$sections['_layout'] = $layout;
    }

    public static function section(string $name): void
    {
        self::$stack[] = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        $name = array_pop(self::$stack);
        self::$sections[$name] = ob_get_clean();
    }

    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }
}
