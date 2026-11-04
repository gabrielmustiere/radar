<?php

declare(strict_types=1);

namespace App\Service\Preview;

final class ImageCropper
{
    /** Remplit `$width` × `$height` en recadrant au centre, transparence conservée. */
    public static function cover(\GdImage $source, int $width, int $height): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);

        $target = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagealphablending($target, enable: false);
        imagesavealpha($target, enable: true);
        imagefill(
            $target,
            x: 0,
            y: 0,
            color: (int) imagecolorallocatealpha($target, red: 0, green: 0, blue: 0, alpha: 127),
        );
        imagecopyresampled(
            $target,
            $source,
            dst_x: 0,
            dst_y: 0,
            src_x: intdiv($sourceWidth - $cropWidth, num2: 2),
            src_y: intdiv($sourceHeight - $cropHeight, num2: 2),
            dst_width: $width,
            dst_height: $height,
            src_width: $cropWidth,
            src_height: $cropHeight,
        );

        return $target;
    }

    /** Pour un format sans transparence : une zone transparente sortirait noire. */
    public static function onWhite(\GdImage $source): \GdImage
    {
        $target = imagecreatetruecolor(imagesx($source), imagesy($source));
        imagefill($target, x: 0, y: 0, color: (int) imagecolorallocate($target, red: 255, green: 255, blue: 255));
        imagecopy(
            $target,
            $source,
            dst_x: 0,
            dst_y: 0,
            src_x: 0,
            src_y: 0,
            src_width: imagesx($source),
            src_height: imagesy($source),
        );

        return $target;
    }
}
