<?php

namespace Laraigniter\QrCode\Styles;

class RoundedStyle extends ShapedStyle
{
    public function name(): string
    {
        return 'rounded';
    }

    public function label(): string
    {
        return 'Rounded';
    }

    protected function shape(): string
    {
        return 'rounded';
    }

    protected function finder(): string
    {
        return 'extra-rounded';
    }
}
