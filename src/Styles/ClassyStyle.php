<?php

namespace Laraigniter\QrCode\Styles;

class ClassyStyle extends ShapedStyle
{
    public function name(): string
    {
        return 'classy';
    }

    public function label(): string
    {
        return 'Classy';
    }

    protected function shape(): string
    {
        return 'classy';
    }

    protected function finder(): string
    {
        return 'square';
    }
}
