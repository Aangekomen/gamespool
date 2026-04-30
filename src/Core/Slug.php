<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Slug
{
    public static function make(string $input): string
    {
        $s = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        $s = strtolower($s);
        $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim($s, '-');
        return $s === '' ? 'item' : $s;
    }

    /**
     * Make a unique slug given an existence-checker callable.
     * The callable receives a candidate slug and returns true if it is taken.
     */
    public static function unique(string $input, callable $exists): string
    {
        $base = self::make($input);
        $candidate = $base;
        $i = 2;
        while ($exists($candidate)) {
            $candidate = $base . '-' . $i;
            $i++;
            if ($i > 1000) {
                $candidate = $base . '-' . bin2hex(random_bytes(4));
                break;
            }
        }
        return $candidate;
    }
}
