<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class QrController
{
    public function svg(): void
    {
        $text = (string) ($_GET['text'] ?? '');
        $size = (int) ($_GET['size'] ?? 320);
        $size = max(120, min(1200, $size));

        if ($text === '') {
            http_response_code(400);
            echo 'Missing ?text=';
            return;
        }

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($text)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        header('Content-Type: ' . $result->getMimeType());
        header('Cache-Control: public, max-age=86400');
        echo $result->getString();
    }
}
