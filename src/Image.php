<?php

namespace Laraigniter\QrCode;

class Image
{
    /**
     * @var resource|\GdImage
     */
    protected $image;

    /**
     * @param string $image
     */
    public function __construct($image)
    {
        $this->image = imagecreatefromstring($image);
    }

    public function getWidth(): int
    {
        return imagesx($this->image);
    }

    public function getHeight(): int
    {
        return imagesy($this->image);
    }

    /**
     * @return resource|\GdImage
     */
    public function getImageResource()
    {
        return $this->image;
    }

    /**
     * @param resource|\GdImage $image
     */
    public function setImageResource($image): void
    {
        $this->image = $image;
    }
}
