<?php

namespace Laraigniter\QrCode\Styles;

use Laraigniter\QrCode\Generator;

abstract class ShapedStyle extends Style
{
    abstract protected function shape(): string;

    abstract protected function finder(): string;

    public function apply(Generator $generator): Generator
    {
        return $generator
            ->format('png')
            ->margin(1)
            ->errorCorrection('M')
            ->shape($this->shape())
            ->finder($this->finder())
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255);
    }
}
