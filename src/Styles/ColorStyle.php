<?php

namespace Laraigniter\QrCode\Styles;

use Laraigniter\QrCode\Generator;

class ColorStyle extends Style
{
    public function name(): string
    {
        return 'color';
    }

    public function label(): string
    {
        return 'Color';
    }

    public function apply(Generator $generator): Generator
    {
        return $generator
            ->format('png')
            ->margin(1)
            ->errorCorrection('M')
            ->color($this->red(), $this->green(), $this->blue())
            ->backgroundColor(255, 255, 255);
    }

    protected function red(): int
    {
        return 13;
    }

    protected function green(): int
    {
        return 148;
    }

    protected function blue(): int
    {
        return 136;
    }
}
