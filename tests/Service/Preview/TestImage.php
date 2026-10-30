<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

/**
 * Vraies images générées par GD, pour les tests qui conservent des visuels.
 */
final class TestImage
{
    public static function png(int $width = 1200, int $height = 630): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, x: 0, y: 0, color: (int) imagecolorallocate($image, red: 13, green: 148, blue: 136));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
