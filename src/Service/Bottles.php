<?php

namespace App\Service;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;

class Bottles implements Asset
{
    private array $bottles = [];
    private $cmykProfile;
    private $srgbProfile;

    public function __construct(ColorProfiles $colorProfile)
    {
        $this->cmykProfile = $colorProfile->getCmykProfile();
        $this->srgbProfile = $colorProfile->getSrgbProfile();
    }

    public function getBottles(): array
    {
        return $this->bottles;
    }

    public function getCount(): int
    {
        return count($this->bottles);
    }

    public function addBottle(string $url)
    {
        $this->bottles[] = $url;
    }

    public function loadBottle(int $index, int $width, int $height): Imagick
    {
        $url = $this->bottles[$index];
        echo basename($url) . PHP_EOL;

        $imageData = file_get_contents($url);

        $bottle = new Imagick();

        try {
            $bottle->readImageBlob($imageData);
        } catch (ImagickException $e) {
            $bottle->clear();
            throw new Exception ("Failed to decode image from URL: " . $url . " - Error: " . $e->getMessage());
        }

        if ($bottle->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            if ($this->cmykProfile && $this->srgbProfile) {
                $bottle->profileImage('icc', $this->cmykProfile);
                $bottle->profileImage('icc', $this->srgbProfile);
                echo "profiled";
            } else {
                $bottle->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                echo "fake profile";
            }
        }

        // path data
        $svgPathData = $bottle->getImageProperty("8BIM:1999,2998:#1");;

        if ($svgPathData) {
            $mask = new Imagick();

            $svgPathData = str_replace('fill:#000000', 'fill:#FFFFFF', $svgPathData);

            $mask->setBackgroundColor(new ImagickPixel('black'));
            $mask->readImageBlob($svgPathData);
            $mask->setImageMatte(false);

            $bottle->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            $bottle->compositeImage($mask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);

            $mask->clear();
        }


        // $imageInfo = $bottle->identifyImage(true);
        //
        // $pathName = $bottle->getImageProperty("8BIM:1999,2998:#1");
        //
        // if ($pathName) {
        //     $bottle->clipPathImage("$pathName", true);
        // }

        $bottle->trimImage(false);

        // if ($new)
        // {
        //     // $bottle->resizeImage(self::CANVAS_WIDTH, 765, Imagick::FILTER_LANCZOS, 1, true);
        //     $bottle->resizeImage($this->bottlesAttr['widthNew'], $this->bottlesAttr['height'], Imagick::FILTER_LANCZOS, 1, true);
        // } else {
        //     // $bottle->resizeImage(290, 760, Imagick::FILTER_LANCZOS, 1, true);
        //     $bottle->resizeImage($this->bottlesAttr['width'], $this->bottlesAttr['height'], Imagick::FILTER_LANCZOS, 1, true);
        // }

        $bottle->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);

        return $bottle;

        // $this->bottles[] = $bottle;
        //
        // return $this;
    }
}
