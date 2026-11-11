<?php

namespace Laraigniter\QrCode\Renderer;

/**
 * Scales a logo and remembers the pixels it covers, plus a thin ring around its visible shape.
 */
class LogoMask
{
    /**
     * @var resource|\GdImage
     */
    private $image;

    /**
     * @var int
     */
    private $left;

    /**
     * @var int
     */
    private $top;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var int
     */
    private $pad;

    /**
     * @var array<int, array<int, bool>>
     */
    private $covered = [];

    /**
     * @param string $contents
     * @param int $pixels
     * @param float $percentage
     *
     * @return self|null
     */
    public static function fromPng(string $contents, int $pixels, float $percentage, float $gap = 0.03): ?self
    {
        $source = imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $bounds = self::opaqueBounds($source);

        if ($bounds === null) {
            unset($source);

            return null;
        }

        $cropWidth = $bounds['right'] - $bounds['left'] + 1;
        $cropHeight = $bounds['bottom'] - $bounds['top'] + 1;
        $width = max(1, (int) round($pixels * $percentage));
        $height = max(1, (int) round($width * ($cropHeight / max(1, $cropWidth))));

        if ($height > $pixels) {
            $height = $pixels;
            $width = max(1, (int) round($height * ($cropWidth / max(1, $cropHeight))));
        }

        $scaled = imagecreatetruecolor($width, $height);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefilledrectangle($scaled, 0, 0, $width, $height, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled(
            $scaled,
            $source,
            0,
            0,
            $bounds['left'],
            $bounds['top'],
            $width,
            $height,
            $cropWidth,
            $cropHeight
        );
        unset($source);

        $mask = new self();
        $mask->image = $scaled;
        $mask->width = $width;
        $mask->height = $height;
        $mask->left = (int) (($pixels - $width) / 2);
        $mask->top = (int) (($pixels - $height) / 2);
        $mask->pad = max(1, (int) round(min($width, $height) * $gap));
        $mask->cover($scaled);

        return $mask;
    }

    public function covers(int $x, int $y): bool
    {
        $localX = $x - $this->left + $this->pad;
        $localY = $y - $this->top + $this->pad;

        return isset($this->covered[$localY][$localX]);
    }

    /**
     * @param resource|\GdImage $image
     */
    public function paint($image): void
    {
        imagealphablending($image, true);
        imagecopy($image, $this->image, $this->left, $this->top, 0, 0, $this->width, $this->height);
    }

    /**
     * @param resource|\GdImage $source
     *
     * @return array{left: int, top: int, right: int, bottom: int}|null
     */
    private static function opaqueBounds($source): ?array
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $left = $width;
        $top = $height;
        $right = 0;
        $bottom = 0;
        $found = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (!self::opaque(imagecolorat($source, $x, $y))) {
                    continue;
                }

                $found = true;
                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        if (!$found) {
            return null;
        }

        return [
            'left' => $left,
            'top' => $top,
            'right' => $right,
            'bottom' => $bottom,
        ];
    }

    /**
     * @param resource|\GdImage $scaled
     */
    private function cover($scaled): void
    {
        $opaque = [];

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (self::opaque(imagecolorat($scaled, $x, $y))) {
                    $opaque[$y][$x] = true;
                }
            }
        }

        $span = $this->pad;
        $limit = ($span * $span) + 1;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (!isset($opaque[$y][$x])) {
                    continue;
                }

                for ($offsetY = -$span; $offsetY <= $span; $offsetY++) {
                    for ($offsetX = -$span; $offsetX <= $span; $offsetX++) {
                        if (($offsetX * $offsetX) + ($offsetY * $offsetY) > $limit) {
                            continue;
                        }

                        $this->covered[$y + $offsetY + $span][$x + $offsetX + $span] = true;
                    }
                }
            }
        }
    }

    private static function opaque(int $color): bool
    {
        return (($color >> 24) & 0x7F) < 110;
    }
}
