<?php

namespace Laraigniter\QrCode\Styles;

use Laraigniter\QrCode\Generator;

class RoundStyle extends Style
{
    public function name(): string
    {
        return 'round';
    }

    public function label(): string
    {
        return 'Round';
    }

    public function apply(Generator $generator): Generator
    {
        return $generator
            ->format('png')
            ->margin(1)
            ->errorCorrection('M')
            ->style('round', 0.6)
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255);
    }
}
