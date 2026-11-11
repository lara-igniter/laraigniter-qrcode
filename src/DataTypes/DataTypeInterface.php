<?php

namespace Laraigniter\QrCode\DataTypes;

interface DataTypeInterface
{
    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments);

    /**
     * @return string
     */
    public function __toString();
}
