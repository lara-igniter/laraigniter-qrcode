<?php

namespace Laraigniter\QrCode;

use InvalidArgumentException;
use Laraigniter\QrCode\Styles\Style;

class StyleRegistry
{
    /**
     * @var array<string, Style>
     */
    private $styles = [];

    public function register(Style $style): void
    {
        $this->styles[$style->name()] = $style;
    }

    public function has(string $name): bool
    {
        return isset($this->styles[$name]);
    }

    public function get(string $name): Style
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException('Unknown QR style [' . $name . '].');
        }

        return $this->styles[$name];
    }

    public function make(string $name): Generator
    {
        return $this->get($name)->apply(new Generator());
    }

    /**
     * @return array<string, Style>
     */
    public function all(): array
    {
        return $this->styles;
    }
}
