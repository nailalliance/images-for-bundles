<?php

namespace App\Service;

use App\Service\DrawerInterface;
use Imagick;

class GelishDipSwatch implements DrawerInterface
{

    public function __construct(
        private Image $image,
        private Asset $swatches,
        private Asset $jars,
    )
    {
    }

    public function draw()
    {
        $description = new Imagick('public/images/gelish-swatch-dip-info.png');

        $this->image->addImage($description);

        // add swatch
        $swatchWidth = $this->image->getWidth() * 1.02;
        $swatchHeight = $this->image->getHeight() * 1.02;
        $swatch = $this->swatches->loadAsset(0, $swatchWidth, $swatchHeight);

        $this->image->compositeImage(
            $swatch,
            Imagick::COMPOSITE_DEFAULT,
            $this->image->getWidth() / 2,
            (($swatchHeight - $this->image->getHeight()) / 2) * -1);

        // add jar

        $jarHeight = $this->image->getHeight() * .62;
        $jar = $this->jars->loadAsset(0, 2000, $jarHeight, true);
        $this->image->compositeImage(
            $jar,
            Imagick::COMPOSITE_DEFAULT,
            $this->image->getWidth() * .45,
            ($swatchHeight - $jarHeight) / 2,
        );
    }
}