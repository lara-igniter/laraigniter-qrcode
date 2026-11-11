<?php

namespace Laraigniter\QrCode\Styles;

class DotsStyle extends ShapedStyle
{
    public function name(): string
    {
        return 'dots';
    }

    public function label(): string
    {
        return 'Dots';
    }

    protected function shape(): string
    {
        return 'dots';
    }

    protected function finder(): string
    {
        return 'dot';
    }
}
