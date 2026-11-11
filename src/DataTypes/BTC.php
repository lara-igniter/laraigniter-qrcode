<?php

namespace Laraigniter\QrCode\DataTypes;

class BTC implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'bitcoin:';

    /**
     * @var string
     */
    protected $address;

    /**
     * @var int|string|null
     */
    protected $amount;

    /**
     * @var string|null
     */
    protected $label;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var string|null
     */
    protected $returnAddress;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        if (isset($arguments[0])) {
            $this->address = (string) $arguments[0];
        }

        if (isset($arguments[1])) {
            $this->amount = $arguments[1];
        }

        if (isset($arguments[2]) && is_array($arguments[2])) {
            $options = $arguments[2];
            $this->label = isset($options['label']) ? (string) $options['label'] : null;
            $this->message = isset($options['message']) ? (string) $options['message'] : null;
            $this->returnAddress = isset($options['returnAddress']) ? (string) $options['returnAddress'] : null;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $query = http_build_query([
            'amount' => $this->amount,
            'label' => $this->label,
            'message' => $this->message,
            'r' => $this->returnAddress,
        ]);

        return $this->prefix . $this->address . '?' . $query;
    }
}
