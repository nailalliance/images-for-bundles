<?php

namespace App\Service;

use App\Service\DrawerInterface;
use Imagick;

class GelishGelSwatch implements DrawerInterface
{

    public function __construct(
        private Image $image,
        private Asset $swatches,
        private Asset $bottles,
    )
    {
    }

    public function draw()
    {
        $description = new Imagick(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public/images/gelish-swatch-gel-info.png');

        $this->image->addImage($description);

        // add swatch
        $swatchWidth = $this->image->getWidth()   * .78;
        $swatchHeight = $this->image->getHeight() * .78;
        $swatch = $this->swatches->loadAsset(0, $swatchWidth, $swatchHeight, true);

        $this->image->compositeImage(
            $swatch,
            Imagick::COMPOSITE_DEFAULT,
            $this->image->getWidth() * .425,
            $this->image->getHeight() * .11
        );

        $handBase = new Imagick(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public/images/hand/hand-base.png');
        $this->image->compositeImage($handBase, Imagick::COMPOSITE_DEFAULT, 0, 0);

        // add the bottle
        $bottleWidth = $this->image->getWidth() * 0.2505;
        $bottleHeight = $this->image->getHeight() * 0.6905;
        $bottle = $this->bottles->loadAsset(0, $bottleWidth, $bottleHeight, true);

        $bottleX = $this->image->getWidth() * 0.621;
        $bottleY = $this->image->getHeight() * 0.09;
        $this->image->compositeImage(
            $bottle,
            Imagick::COMPOSITE_DEFAULT,
            $bottleX,
            $bottleY
        );

        $handBase = new Imagick(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public/images/hand/hand-fingers.png');
        $this->image->compositeImage($handBase, Imagick::COMPOSITE_DEFAULT, 0, 0);

        // add nails
        $mask = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public/images/hand/nailshape-grey.png';
        $clipper = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public/images/hand/nailshape.png';
        $texture = $this->swatches->getAssets()[0];
        $masked = (new TexturedImage($texture, $mask, $clipper))->draw()->getResult();

        $this->image->compositeImage($masked, Imagick::COMPOSITE_DEFAULT, 0,0);
    }
}