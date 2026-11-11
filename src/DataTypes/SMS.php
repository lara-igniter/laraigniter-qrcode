<?php

namespace Laraigniter\QrCode\DataTypes;

class SMS implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'sms:';

    /**
     * @var string
     */
    protected $separator = '&body=';

    /**
     * @var string
     */
    protected $phoneNumber;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        if (isset($arguments[0])) {
            $this->phoneNumber = (string) $arguments[0];
        }

        if (isset($arguments[1])) {
            $this->message = (string) $arguments[1];
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $sms = $this->prefix . $this->phoneNumber;

        if (isset($this->message)) {
            $sms .= $this->separator . $this->message;
        }

        return $sms;
    }
}
