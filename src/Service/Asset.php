<?php

namespace App\Service;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;

class Asset
{
    private array $bottles = [];
    private $cmykProfile;
    private $srgbProfile;

    public function __construct(Color $colorProfile)
    {
        $this->cmykProfile = $colorProfile->getCmykProfile();
        $this->srgbProfile = $colorProfile->getSrgbProfile();
    }

    public function getAssets(): array
    {
        return $this->bottles;
    }

    public function getCount(): int
    {
        return count($this->bottles);
    }

    public function addAsset(string $url)
    {
        $this->bottles[] = $url;
    }

    public function loadAsset(int $index, int $width, int $height, bool $cropOnPath = false): Imagick
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
            } else {
                $bottle->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }
        }

        // // path data
        // if ($cropOnPath)
        // {
        //     $svgPathData = $bottle->getImageProperty("8BIM:1999,2998:#2");
        //
        //     if ($svgPathData) {
        //         $mask = new Imagick();
        //
        //         $svgPathData = str_replace('fill:#000000', 'fill:#FFFFFF', $svgPathData);
        //
        //         $mask->setBackgroundColor(new ImagickPixel('black'));
        //         $mask->readImageBlob($svgPathData);
        //         $mask->setImageMatte(false);
        //
        //         $bottle->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
        //
        //         $bottle->compositeImage($mask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);
        //
        //         $mask->clear();
        //     }
        // }

        if ($cropOnPath) {
            $bestPathIndex = null;
            $maxBoxArea = 0.0;

            // 1. Loop to find the "mathematically" largest path (Lightweight)
            for ($i = 0; $i <= 15; $i++) {
                $key = "8BIM:1999,2998:#" . $i;
                $svgPathData = $bottle->getImageProperty($key);

                if (!$svgPathData) continue;

                if (preg_match('/d="([^"]+)"/', $svgPathData, $matches)) {
                    $pathContent = $matches[1];

                    // Extract all numbers (coordinates) from the path string
                    preg_match_all('/[-+]?[0-9]*\.?[0-9]+/', $pathContent, $coords);
                    $numbers = $coords[0];

                    if (count($numbers) < 4) continue; // Not enough points to make a shape

                    // Initialize min/max
                    $minX = $maxX = (float)$numbers[0];
                    $minY = $maxY = (float)$numbers[1];

                    // Photoshop paths (M, L, C) usually come in pairs of X, Y.
                    // We iterate through them to find the bounding box.
                    $count = count($numbers);
                    for ($j = 0; $j < $count; $j += 2) {
                        if (!isset($numbers[$j+1])) break;

                        $x = (float)$numbers[$j];
                        $y = (float)$numbers[$j+1];

                        if ($x < $minX) $minX = $x;
                        if ($x > $maxX) $maxX = $x;
                        if ($y < $minY) $minY = $y;
                        if ($y > $maxY) $maxY = $y;
                    }

                    // Calculate Bounding Box Area
                    $widthBox = $maxX - $minX;
                    $heightBox = $maxY - $minY;
                    $area = $widthBox * $heightBox;

                    if ($area > $maxBoxArea) {
                        $maxBoxArea = $area;
                        $bestPathIndex = $i;
                    }
                }
            }

            // 2. Render ONLY the winner (Heavy operation runs only once)
            if ($bestPathIndex !== null) {
                $key = "8BIM:1999,2998:#" . $bestPathIndex;
                $svgPathData = $bottle->getImageProperty($key);

                $mask = new Imagick();

                // Invert logic: Make the path white, background black
                $svgPathData = str_replace('fill:#000000', 'fill:#FFFFFF', $svgPathData);

                $mask->setBackgroundColor(new ImagickPixel('black'));
                $mask->readImageBlob($svgPathData);
                $mask->setImageMatte(false);

                // Ensure dimensions match (sometimes SVG loads differently)
                if ($mask->getImageWidth() !== $bottle->getImageWidth()) {
                    $mask->scaleImage($bottle->getImageWidth(), $bottle->getImageHeight());
                }

                $bottle->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $bottle->compositeImage($mask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);

                $mask->clear();
            }
        }

        $bottle->trimImage(false);

        $bottle->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);

        return $bottle;
    }
}
