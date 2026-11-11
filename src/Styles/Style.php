<?php

namespace Laraigniter\QrCode\Styles;

use Laraigniter\QrCode\Generator;

abstract class Style
{
    abstract public function name(): string;

    abstract public function label(): string;

    abstract public function apply(Generator $generator): Generator;
}
