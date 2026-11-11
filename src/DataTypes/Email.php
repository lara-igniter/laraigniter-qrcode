<?php

namespace Laraigniter\QrCode\DataTypes;

use BaconQrCode\Exception\InvalidArgumentException;

class Email implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'mailto:';

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $subject;

    /**
     * @var string|null
     */
    protected $body;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        if (isset($arguments[0])) {
            if (!filter_var($arguments[0], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email provided');
            }

            $this->email = (string) $arguments[0];
        }

        if (isset($arguments[1])) {
            $this->subject = (string) $arguments[1];
        }

        if (isset($arguments[2])) {
            $this->body = (string) $arguments[2];
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $email = $this->prefix . $this->email;

        if (isset($this->subject) || isset($this->body)) {
            $email .= '?' . http_build_query([
                'subject' => $this->subject,
                'body' => $this->body,
            ]);
        }

        return $email;
    }
}
