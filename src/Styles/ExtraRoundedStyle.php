<?php

namespace Laraigniter\QrCode\Styles;

class ExtraRoundedStyle extends ShapedStyle
{
    public function name(): string
    {
        return 'extra-rounded';
    }

    public function label(): string
    {
        return 'Extra rounded';
    }

    protected function shape(): string
    {
        return 'extra-rounded';
    }

    protected function finder(): string
    {
        return 'extra-rounded';
    }
}
