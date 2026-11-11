<?php

namespace Laraigniter\QrCode;

use InvalidArgumentException;

class ImageMerge
{
    /**
     * @var Image
     */
    protected $sourceImage;

    /**
     * @var Image
     */
    protected $mergeImage;

    /**
     * @var int
     */
    protected $sourceImageHeight;

    /**
     * @var int
     */
    protected $sourceImageWidth;

    /**
     * @var int
     */
    protected $mergeImageHeight;

    /**
     * @var int
     */
    protected $mergeImageWidth;

    /**
     * @var float
     */
    protected $mergeRatio;

    /**
     * @var int
     */
    protected $postMergeImageHeight;

    /**
     * @var int
     */
    protected $postMergeImageWidth;

    /**
     * @var int
     */
    protected $centerY;

    /**
     * @var int
     */
    protected $centerX;

    public function __construct(Image $sourceImage, Image $mergeImage)
    {
        $this->sourceImage = $sourceImage;
        $this->mergeImage = $mergeImage;
    }

    public function merge(float $percentage): string
    {
        $this->setProperties($percentage);

        $img = imagecreatetruecolor($this->sourceImage->getWidth(), $this->sourceImage->getHeight());
        imagealphablending($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        imagesavealpha($img, true);

        imagecopy(
            $img,
            $this->sourceImage->getImageResource(),
            0,
            0,
            0,
            0,
            $this->sourceImage->getWidth(),
            $this->sourceImage->getHeight()
        );

        imagecopyresampled(
            $img,
            $this->mergeImage->getImageResource(),
            $this->centerX,
            $this->centerY,
            0,
            0,
            $this->postMergeImageWidth,
            $this->postMergeImageHeight,
            $this->mergeImageWidth,
            $this->mergeImageHeight
        );

        $this->sourceImage->setImageResource($img);

        return $this->createImage();
    }

    protected function createImage(): string
    {
        ob_start();
        imagepng($this->sourceImage->getImageResource());
        $png = ob_get_clean();

        return is_string($png) ? $png : '';
    }

    protected function setProperties(float $percentage): void
    {
        if ($percentage > 1) {
            throw new InvalidArgumentException('$percentage must be less than 1');
        }

        $this->sourceImageHeight = $this->sourceImage->getHeight();
        $this->sourceImageWidth = $this->sourceImage->getWidth();
        $this->mergeImageHeight = $this->mergeImage->getHeight();
        $this->mergeImageWidth = $this->mergeImage->getWidth();

        $this->calculateOverlap($percentage);
        $this->calculateCenter();
    }

    protected function calculateCenter(): void
    {
        $this->centerX = (int) (($this->sourceImageWidth / 2) - ($this->postMergeImageWidth / 2));
        $this->centerY = (int) (($this->sourceImageHeight / 2) - ($this->postMergeImageHeight / 2));
    }

    protected function calculateOverlap(float $percentage): void
    {
        $this->mergeRatio = round($this->mergeImageWidth / max(1, $this->mergeImageHeight), 2);
        $this->postMergeImageWidth = (int) ($this->sourceImageWidth * $percentage);
        $this->postMergeImageHeight = (int) ($this->postMergeImageWidth / max(0.01, $this->mergeRatio));
    }
}
