<?php

namespace Laraigniter\QrCode\Renderer;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use Laraigniter\QrCode\Generator;

class GdMatrixRenderer
{
    public function render(Generator $generator, string $text): string
    {
        $level = $generator->correctionLevel() ?? ErrorCorrectionLevel::M();
        $matrix = Encoder::encode($text, $level, $generator->characterEncoding())->getMatrix();
        $count = $matrix->getWidth();
        $requested = max(1, $generator->pixelSize());
        $margin = max(0, $generator->marginSize());
        $span = $count + ($margin * 2);
        $module = max(1, (int) round($requested / $span));
        $pixels = $module * $span;
        $image = imagecreatetruecolor($pixels, $pixels);

        if (function_exists('imageantialias')) {
            imageantialias($image, false);
        }

        $background = $this->allocate($image, $generator->background());
        $foreground = $this->allocate($image, $generator->foregroundColor());
        imagefill($image, 0, 0, $background);

        $eyes = [];

        foreach ($generator->eyeFills() as $index => $fill) {
            if (!$fill instanceof EyeFill) {
                continue;
            }

            $eyes[(int) $index] = [
                'outer' => $fill->inheritsExternalColor() ? $foreground : $this->allocate($image, $fill->getExternalColor()),
                'inner' => $fill->inheritsInternalColor() ? $foreground : $this->allocate($image, $fill->getInternalColor()),
            ];
        }

        $shape = $generator->moduleShape();
        $finderStyle = $generator->finderStyle();
        $clearance = $generator->centerClearance();
        $logo = $generator->fitsLogo()
            ? LogoMask::fromPng((string) $generator->mergedImage(), $pixels, $generator->imagePercentage(), $generator->logoGap())
            : null;
        $foregroundColor = $generator->foregroundColor();
        $red = $foregroundColor->toRgb()->getRed();
        $green = $foregroundColor->toRgb()->getGreen();
        $blue = $foregroundColor->toRgb()->getBlue();
        $figures = new ModuleFigure();
        $findersDrawn = [];

        for ($y = 0; $y < $count; $y++) {
            for ($x = 0; $x < $count; $x++) {
                if ((int) $matrix->get($x, $y) === 0) {
                    continue;
                }

                $box = $this->box($pixels, $span, $margin, $x, $y);
                $finder = $this->finder($x, $y, $count);

                if ($finder !== null) {
                    if ($finderStyle === 'square') {
                        $part = $this->finderPart($x, $y, $finder['x'], $finder['y']);
                        $color = $eyes[$finder['index']][$part] ?? $foreground;
                        $this->square($image, $box, $color);
                        continue;
                    }

                    if (!isset($findersDrawn[$finder['index']])) {
                        $outer = $eyes[$finder['index']]['outer'] ?? $foreground;
                        $inner = $eyes[$finder['index']]['inner'] ?? $foreground;
                        $this->finderFrame($image, $pixels, $span, $margin, $finder, $finderStyle, $outer, $inner, $background);
                        $findersDrawn[$finder['index']] = true;
                    }

                    continue;
                }

                if ($this->coveredByCenter($x, $y, $count, $clearance) || $this->coveredByLogo($box, $logo)) {
                    continue;
                }

                if ($shape === 'dot') {
                    $this->dot($image, $box, $foreground, $generator->moduleStyleSize());
                    continue;
                }

                if ($shape === 'round') {
                    $this->round($image, $box, $foreground, $generator->moduleStyleSize());
                    continue;
                }

                if ($shape === 'square') {
                    $this->square($image, $box, $foreground);
                    continue;
                }

                $figures->draw($image, $box, $red, $green, $blue, $shape, [
                    'left' => $x > 0 ? (int) $matrix->get($x - 1, $y) : 0,
                    'right' => $x < $count - 1 ? (int) $matrix->get($x + 1, $y) : 0,
                    'top' => $y > 0 ? (int) $matrix->get($x, $y - 1) : 0,
                    'bottom' => $y < $count - 1 ? (int) $matrix->get($x, $y + 1) : 0,
                ]);
            }
        }

        if ($logo instanceof LogoMask) {
            $logo->paint($image);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        unset($image);

        return is_string($png) ? $png : '';
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function box(int $pixels, int $span, int $margin, int $x, int $y): array
    {
        $module = intdiv($pixels, $span);

        return [
            ($margin + $x) * $module,
            ($margin + $y) * $module,
            ($margin + $x + 1) * $module,
            ($margin + $y + 1) * $module,
        ];
    }

    /**
     * @return array{index: int, x: int, y: int}|null
     */
    private function finder(int $x, int $y, int $count): ?array
    {
        $boxes = [
            ['index' => 0, 'x' => 0, 'y' => 0],
            ['index' => 1, 'x' => $count - 7, 'y' => 0],
            ['index' => 2, 'x' => 0, 'y' => $count - 7],
        ];

        foreach ($boxes as $box) {
            if ($x >= $box['x'] && $x < $box['x'] + 7 && $y >= $box['y'] && $y < $box['y'] + 7) {
                return $box;
            }
        }

        return null;
    }

    /**
     * @param array{index: int, x: int, y: int} $finder
     * @param resource|\GdImage $image
     */
    private function finderFrame($image, int $pixels, int $span, int $margin, array $finder, string $style, int $outer, int $inner, int $background): void
    {
        $start = $this->box($pixels, $span, $margin, $finder['x'], $finder['y']);
        $end = $this->box($pixels, $span, $margin, $finder['x'] + 6, $finder['y'] + 6);
        $left = $start[0];
        $top = $start[1];
        $width = max(1, $end[2] - $left);
        $height = max(1, $end[3] - $top);
        $centerX = $left + (int) ($width / 2);
        $centerY = $top + (int) ($height / 2);

        if ($style === 'dot') {
            imagefilledellipse($image, $centerX, $centerY, $width, $height, $outer);
            imagefilledellipse($image, $centerX, $centerY, (int) ($width * 5 / 7), (int) ($height * 5 / 7), $background);
            imagefilledellipse($image, $centerX, $centerY, (int) ($width * 3 / 7), (int) ($height * 3 / 7), $inner);

            return;
        }

        $this->roundedFrame($image, $left, $top, $width, $height, (int) round($width * 2.5 / 7), $outer);
        $hole = (int) round($width / 7);
        $this->roundedFrame(
            $image,
            $left + $hole,
            $top + $hole,
            $width - ($hole * 2),
            $height - ($hole * 2),
            (int) round($width * 1.5 / 7),
            $background
        );
        imagefilledellipse($image, $centerX, $centerY, (int) ($width * 3 / 7), (int) ($height * 3 / 7), $inner);
    }

    /**
     * @param resource|\GdImage $image
     */
    private function roundedFrame($image, int $left, int $top, int $width, int $height, int $radius, int $color): void
    {
        $radius = max(1, min($radius, (int) (min($width, $height) / 2)));
        imagefilledrectangle($image, $left + $radius, $top, $left + $width - $radius - 1, $top + $height - 1, $color);
        imagefilledrectangle($image, $left, $top + $radius, $left + $width - 1, $top + $height - $radius - 1, $color);
        imagefilledellipse($image, $left + $radius, $top + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $left + $width - $radius, $top + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $left + $radius, $top + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $left + $width - $radius, $top + $height - $radius, $radius * 2, $radius * 2, $color);
    }

    private function coveredByCenter(int $x, int $y, int $count, float $ratio): bool
    {
        if ($ratio <= 0) {
            return false;
        }

        $span = $count * $ratio;
        $start = ($count - $span) / 2;

        return $x >= $start && $x < $start + $span && $y >= $start && $y < $start + $span;
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     */
    private function coveredByLogo(array $box, ?LogoMask $logo): bool
    {
        if (!$logo instanceof LogoMask) {
            return false;
        }

        return $logo->covers((int) (($box[0] + $box[2]) / 2), (int) (($box[1] + $box[3]) / 2));
    }

    private function finderPart(int $x, int $y, int $originX, int $originY): string
    {
        $relativeX = $x - $originX;
        $relativeY = $y - $originY;

        if ($relativeX >= 2 && $relativeX <= 4 && $relativeY >= 2 && $relativeY <= 4) {
            return 'inner';
        }

        return 'outer';
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     */
    private function square($image, array $box, int $color): void
    {
        imagefilledrectangle($image, $box[0], $box[1], max($box[0], $box[2] - 1), max($box[1], $box[3] - 1), $color);
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     */
    private function dot($image, array $box, int $color, float $amount): void
    {
        $width = max(1, $box[2] - $box[0]);
        $height = max(1, $box[3] - $box[1]);
        $dot = max(1, (int) round(min($width, $height) * $amount));
        $left = $box[0] + (int) (($width - $dot) / 2);
        $top = $box[1] + (int) (($height - $dot) / 2);

        imagefilledellipse($image, $left + (int) ($dot / 2), $top + (int) ($dot / 2), $dot, $dot, $color);
    }

    /**
     * @param array{0: int, 1: int, 2: int, 3: int} $box
     */
    private function round($image, array $box, int $color, float $amount): void
    {
        $width = max(1, $box[2] - $box[0]);
        $height = max(1, $box[3] - $box[1]);
        $radius = max(1, (int) round(min($width, $height) * $amount / 2));
        $right = $box[0] + $width;
        $bottom = $box[1] + $height;

        imagefilledrectangle($image, $box[0] + $radius, $box[1], $right - $radius, $bottom - 1, $color);
        imagefilledrectangle($image, $box[0], $box[1] + $radius, $right - 1, $bottom - $radius, $color);
        imagefilledellipse($image, $box[0] + $radius, $box[1] + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $right - $radius, $box[1] + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $box[0] + $radius, $bottom - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * @param resource|\GdImage $image
     */
    private function allocate($image, ColorInterface $color): int
    {
        $alpha = 0;

        if ($color instanceof Alpha) {
            $alpha = (int) round((100 - $color->getAlpha()) * 127 / 100);
            $color = $color->getBaseColor();
        }

        if (!$color instanceof Rgb) {
            $color = $color->toRgb();
        }

        if ($alpha > 0) {
            return imagecolorallocatealpha($image, $color->getRed(), $color->getGreen(), $color->getBlue(), $alpha);
        }

        return imagecolorallocate($image, $color->getRed(), $color->getGreen(), $color->getBlue());
    }
}
