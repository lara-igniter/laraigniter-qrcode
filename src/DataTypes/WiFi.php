<?php

namespace Laraigniter\QrCode\DataTypes;

class WiFi implements DataTypeInterface
{
    /**
     * @var string
     */
    protected $prefix = 'WIFI:';

    /**
     * @var string
     */
    protected $separator = ';';

    /**
     * @var string|null
     */
    protected $encryption;

    /**
     * @var string|null
     */
    protected $ssid;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string|null
     */
    protected $hidden;

    /**
     * @param array<int, mixed> $arguments
     */
    public function create(array $arguments)
    {
        $arguments = $arguments[0];

        if (isset($arguments['encryption'])) {
            $this->encryption = (string) $arguments['encryption'];
        }

        if (isset($arguments['ssid'])) {
            $this->ssid = (string) $arguments['ssid'];
        }

        if (isset($arguments['password'])) {
            $this->password = (string) $arguments['password'];
        }

        if (isset($arguments['hidden'])) {
            $this->hidden = (string) $arguments['hidden'];
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $wifi = $this->prefix;

        if (isset($this->encryption)) {
            $wifi .= 'T:' . $this->encryption . $this->separator;
        }

        if (isset($this->ssid)) {
            $wifi .= 'S:' . $this->ssid . $this->separator;
        }

        if (isset($this->password)) {
            $wifi .= 'P:' . $this->password . $this->separator;
        }

        if (isset($this->hidden)) {
            $wifi .= 'H:' . $this->hidden . $this->separator;
        }

        return $wifi;
    }
}
