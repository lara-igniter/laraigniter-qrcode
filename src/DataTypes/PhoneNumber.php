<?php

namespace Laraigniter\QrCode\DataTypes;

class PhoneNumber implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'tel:';

    /**
     * @var string
     */
    protected $phoneNumber;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        $this->phoneNumber = (string) $arguments[0];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->prefix . $this->phoneNumber;
    }
}
