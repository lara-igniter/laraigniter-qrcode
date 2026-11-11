<?php

namespace Laraigniter\QrCode\Styles;

use Laraigniter\QrCode\Generator;

class SimpleStyle extends Style
{
    public function name(): string
    {
        return 'simple';
    }

    public function label(): string
    {
        return 'Simple';
    }

    public function apply(Generator $generator): Generator
    {
        return $generator
            ->format('png')
            ->margin(1)
            ->errorCorrection('M')
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255);
    }
}
