<?php

namespace Laraigniter\QrCode\Facades;

use Elegant\Support\Facades\Facade;
use Laraigniter\QrCode\Generator;

/**
 * @method static Generator size(int $pixels)
 * @method static Generator format(string $format)
 * @method static Generator preset(string $name)
 * @method static Generator color(int $red, int $green, int $blue, ?int $alpha = null)
 * @method static Generator backgroundColor(int $red, int $green, int $blue, ?int $alpha = null)
 * @method static Generator margin(int $margin)
 * @method static Generator style(string $style, float $size = 0.5)
 * @method static Generator shape(string $shape)
 * @method static Generator finder(string $style)
 * @method static Generator clearCenter(float $ratio)
 * @method static Generator fitLogo(bool $fit = true)
 * @method static Generator eye(string $style)
 * @method static Generator errorCorrection(string $errorCorrection)
 * @method static Generator encoding(string $encoding)
 * @method static Generator merge(string $filepath, ?float $percentage = null, bool $absolute = false)
 * @method static Generator mergeString(string $content, float $percentage = .2)
 * @method static string generate(string $text, ?string $filename = null)
 * @method static string dataUri(string $text)
 *
 * @see Generator
 */
class QrCode extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'qrcode';
    }

    /**
     * Each call starts from a new generator because the builder is mutable.
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return (new Generator())->{$method}(...$arguments);
    }
}
