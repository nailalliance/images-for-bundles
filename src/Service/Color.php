<?php

namespace App\Service;

use Imagick;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class Color
{
    private ?string $srgbProfile;
    private ?string $cmykProfile;

    public function __construct(
        private readonly Filesystem $filesystem,
        ParameterBagInterface $params
    )
    {
        $srgbProfilePath = $params->get('srgb_profile_path');
        $cmykProfilePath = $params->get('cmyk_profile_path');

        $this->srgbProfile = $this->filesystem->exists($srgbProfilePath) ? file_get_contents($srgbProfilePath) : null;
        $this->cmykProfile = $this->filesystem->exists($cmykProfilePath) ? file_get_contents($cmykProfilePath) : null;
    }

    public function getSrgbProfile(): ?string
    {
        return $this->srgbProfile;
    }

    public function getCmykProfile(): ?string
    {
        return $this->cmykProfile;
    }

    /**
     * @param Imagick $swatch
     * @return ColorStruct
     * @throws \ImagickException
     */
    static public function getSwatchSolidColor(Imagick &$swatch): ColorStruct
    {
        $sample = $swatch->clone();
        $regionWidth = $sample->getImageWidth() * .05;
        $regionHeight = $regionWidth;
        $regionX = $sample->getImageWidth() * 0.475;
        $regionY = $sample->getImageHeight() * 0.475;
        $sample = $sample->getImageRegion($regionWidth, $regionHeight, $regionX, $regionY);

        $sample->scaleImage(1, 1);

        $pixel = $sample->getImagePixelColor(0, 0);

        $rgb =  $pixel->getColor();
        $hsl =  $pixel->getHSL();
        $name =  $pixel->getColorAsString();

        $pixel->clear();
        $sample->clear();

        return new ColorStruct(
            rgb: $rgb,
            hsl: $hsl,
            name: $name
        );
    }
}
