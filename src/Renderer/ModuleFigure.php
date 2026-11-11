<?php

namespace Laraigniter\QrCode\Renderer;

/**
 * Draws one QR module the way qr-code-styling draws its dot types.
 * Connected neighbors stay square so the shape joins. Free corners and ends are rounded.
 */
class ModuleFigure
{
    /**
     * @param resource|\GdImage $image
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     * @param array{left: int, right: int, top: int, bottom: int} $neighbors
     */
    public function draw($image, array $box, int $red, int $green, int $blue, string $shape, array $neighbors): void
    {
        $left = $neighbors['left'];
        $right = $neighbors['right'];
        $top = $neighbors['top'];
        $bottom = $neighbors['bottom'];
        $count = $left + $right + $top + $bottom;

        if ($shape === 'dots' || ($count === 0 && ($shape === 'rounded' || $shape === 'extra-rounded'))) {
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) {
                imagefilledellipse($tile, (int) ($width / 2), (int) ($height / 2), $width, $height, $color);
            });

            return;
        }

        if ($shape === 'classy' || $shape === 'classy-rounded') {
            $this->drawClassy($image, $box, $red, $green, $blue, $shape === 'classy-rounded', $left, $right, $top, $bottom, $count);

            return;
        }

        if ($count > 2 || ($left && $right) || ($top && $bottom)) {
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) {
                imagefilledrectangle($tile, 0, 0, $width - 1, $height - 1, $color);
            });

            return;
        }

        if ($count === 2) {
            $corner = 'top-right';

            if ($left && $top) {
                $corner = 'bottom-right';
            } elseif ($top && $right) {
                $corner = 'bottom-left';
            } elseif ($right && $bottom) {
                $corner = 'top-left';
            }

            $extra = $shape === 'extra-rounded';
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) use ($corner, $extra) {
                if ($extra) {
                    $this->extraCorner($tile, $width, $height, $color, $corner);
                } else {
                    $this->roundedCorner($tile, $width, $height, $color, $corner);
                }
            });

            return;
        }

        $side = 'right';

        if ($top) {
            $side = 'bottom';
        } elseif ($right) {
            $side = 'left';
        } elseif ($bottom) {
            $side = 'top';
        }

        $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) use ($side) {
            $this->roundedSide($tile, $width, $height, $color, $side);
        });
    }

    /**
     * @param resource|\GdImage $image
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     */
    private function drawClassy($image, array $box, int $red, int $green, int $blue, bool $extra, int $left, int $right, int $top, int $bottom, int $count): void
    {
        if ($count === 0) {
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) {
                imagefilledellipse($tile, (int) ($width / 2), (int) ($height / 2), $width, $height, $color);
            });

            return;
        }

        if (!$left && !$top) {
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) use ($extra) {
                if ($extra) {
                    $this->extraCorner($tile, $width, $height, $color, 'top-left');
                } else {
                    $this->roundedCorner($tile, $width, $height, $color, 'top-left');
                }
            });

            return;
        }

        if (!$right && !$bottom) {
            $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) use ($extra) {
                if ($extra) {
                    $this->extraCorner($tile, $width, $height, $color, 'bottom-right');
                } else {
                    $this->roundedCorner($tile, $width, $height, $color, 'bottom-right');
                }
            });

            return;
        }

        $this->paint($image, $box, $red, $green, $blue, function ($tile, $width, $height, $color) {
            imagefilledrectangle($tile, 0, 0, $width - 1, $height - 1, $color);
        });
    }

    /**
     * @param resource|\GdImage $image
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     * @param callable $draw
     */
    private function paint($image, array $box, int $red, int $green, int $blue, callable $draw): void
    {
        $width = max(1, $box[2] - $box[0]);
        $height = max(1, $box[3] - $box[1]);
        $tile = imagecreatetruecolor($width, $height);
        imagealphablending($tile, false);
        $clear = imagecolorallocatealpha($tile, 0, 0, 0, 127);
        imagefilledrectangle($tile, 0, 0, $width, $height, $clear);
        imagesavealpha($tile, true);
        imagealphablending($tile, true);
        $color = imagecolorallocate($tile, $red, $green, $blue);
        $draw($tile, $width, $height, $color);
        imagealphablending($image, true);
        imagecopy($image, $tile, $box[0], $box[1], 0, 0, $width, $height);
        unset($tile);
    }

    /**
     * @param resource|\GdImage $tile
     */
    private function roundedCorner($tile, int $width, int $height, int $color, string $corner): void
    {
        $midX = (int) ($width / 2);
        $midY = (int) ($height / 2);
        imagefilledellipse($tile, $midX, $midY, $width, $height, $color);

        if ($corner === 'top-right') {
            imagefilledrectangle($tile, 0, 0, $midX, $height - 1, $color);
            imagefilledrectangle($tile, $midX, $midY, $width - 1, $height - 1, $color);

            return;
        }

        if ($corner === 'bottom-right') {
            imagefilledrectangle($tile, 0, 0, $midX, $height - 1, $color);
            imagefilledrectangle($tile, $midX, 0, $width - 1, $midY, $color);

            return;
        }

        if ($corner === 'bottom-left') {
            imagefilledrectangle($tile, $midX, 0, $width - 1, $height - 1, $color);
            imagefilledrectangle($tile, 0, 0, $midX, $midY, $color);

            return;
        }

        imagefilledrectangle($tile, $midX, 0, $width - 1, $height - 1, $color);
        imagefilledrectangle($tile, 0, $midY, $midX, $height - 1, $color);
    }

    /**
     * Quarter disk whose curve replaces the named corner.
     *
     * @param resource|\GdImage $tile
     */
    private function extraCorner($tile, int $width, int $height, int $color, string $corner): void
    {
        $centers = [
            'top-right' => [0, $height],
            'bottom-right' => [0, 0],
            'bottom-left' => [$width, 0],
            'top-left' => [$width, $height],
        ];
        $center = $centers[$corner];
        imagefilledellipse($tile, $center[0], $center[1], $width * 2, $height * 2, $color);
    }

    /**
     * Flat edge toward the neighbor and a round cap on the free side.
     *
     * @param resource|\GdImage $tile
     */
    private function roundedSide($tile, int $width, int $height, int $color, string $side): void
    {
        $midX = (int) ($width / 2);
        $midY = (int) ($height / 2);
        imagefilledellipse($tile, $midX, $midY, $width, $height, $color);

        if ($side === 'right') {
            imagefilledrectangle($tile, 0, 0, $midX, $height - 1, $color);
        } elseif ($side === 'left') {
            imagefilledrectangle($tile, $midX, 0, $width - 1, $height - 1, $color);
        } elseif ($side === 'bottom') {
            imagefilledrectangle($tile, 0, 0, $width - 1, $midY, $color);
        } else {
            imagefilledrectangle($tile, 0, $midY, $width - 1, $height - 1, $color);
        }
    }
}
