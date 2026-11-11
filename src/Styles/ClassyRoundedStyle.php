<?php

namespace Laraigniter\QrCode\Styles;

class ClassyRoundedStyle extends ShapedStyle
{
    public function name(): string
    {
        return 'classy-rounded';
    }

    public function label(): string
    {
        return 'Classy rounded';
    }

    protected function shape(): string
    {
        return 'classy-rounded';
    }

    protected function finder(): string
    {
        return 'extra-rounded';
    }
}
