<?php
declare(strict_types=1);

namespace GamesPool\Core;

class ImageUpload
{
    public const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Process and store an uploaded image as a square JPEG.
     *
     * @param array  $file       $_FILES[name] entry
     * @param string $directory  absolute target directory
     * @param int    $size       output size in pixels (square)
     * @param int    $maxBytes   max input file size in bytes
     * @return string            stored filename (basename only)
     * @throws \RuntimeException on validation/processing errors
     */
    public static function storeSquare(array $file, string $directory, int $size = 256, int $maxBytes = 4194304): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \RuntimeException('Ongeldige upload.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::errorLabel((int) $file['error']));
        }
        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException('Bestand te groot (max ' . round($maxBytes / 1024 / 1024, 1) . ' MB).');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            throw new \RuntimeException('Alleen JPG, PNG of WebP toegestaan.');
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if (!$src) {
            throw new \RuntimeException('Kon afbeelding niet inlezen.');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $cropSize = min($w, $h);
        $x = (int) (($w - $cropSize) / 2);
        $y = (int) (($h - $cropSize) / 2);

        $dst = imagecreatetruecolor($size, $size);
        // White background for transparent PNGs once flattened to JPEG
        $bg = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $size, $size, $bg);
        imagecopyresampled($dst, $src, 0, 0, $x, $y, $size, $size, $cropSize, $cropSize);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Upload-map kan niet worden aangemaakt.');
            }
        }

        $filename = bin2hex(random_bytes(8)) . '.jpg';
        $path = rtrim($directory, '/') . '/' . $filename;
        imagejpeg($dst, $path, 85);

        imagedestroy($src);
        imagedestroy($dst);

        return $filename;
    }

    public static function delete(string $directory, ?string $filename): void
    {
        if (!$filename) return;
        $path = rtrim($directory, '/') . '/' . basename($filename);
        if (is_file($path)) @unlink($path);
    }

    private static function errorLabel(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Bestand te groot.',
            UPLOAD_ERR_PARTIAL => 'Upload incompleet, probeer opnieuw.',
            UPLOAD_ERR_NO_FILE => 'Geen bestand geüpload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server-fout: geen temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server-fout: kan niet schrijven.',
            UPLOAD_ERR_EXTENSION => 'Upload geblokkeerd door PHP-extensie.',
            default => 'Upload mislukt.',
        };
    }
}
