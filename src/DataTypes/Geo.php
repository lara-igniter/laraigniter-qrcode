<?php

namespace Laraigniter\QrCode\DataTypes;

class Geo implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'geo:';

    /**
     * @var string
     */
    protected $separator = ',';

    /**
     * @var string
     */
    protected $latitude;

    /**
     * @var string
     */
    protected $longitude;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        $this->latitude = (string) $arguments[0];
        $this->longitude = (string) $arguments[1];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->prefix . $this->latitude . $this->separator . $this->longitude;
    }
}
