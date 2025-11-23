<?php

namespace App\Service;

use Imagick;
use ImagickPixel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class Image
{
    private $imagick;

    public function __construct(private int $width, private int $height)
    {
        $this->imagick = new Imagick();
        $this->imagick->newImage($width, $height, new ImagickPixel("white"), "jpeg");
    }

    public function saveImage(string $path)
    {
        $this->imagick->writeImage($path);
        $this->imagick->clear();
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function compositeImage(Imagick $composite_object, $composite, $x, $y, $channel = Imagick::CHANNEL_ALL): bool
    {
        return $this->imagick->compositeImage($composite_object, $composite, $x, $y, $channel);
    }
}
