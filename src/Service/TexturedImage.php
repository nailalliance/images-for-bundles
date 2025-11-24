<?php

namespace App\Service;

use Exception;
use Imagick;
use ImagickPixel;

class TexturedImage
{
    private Imagick $texture;
    private Imagick $mask;
    private Imagick $clipper;

    public function __construct(
        string $texture,
        string $mask,
        string $clipper
    )
    {
        $this->texture = new Imagick($texture);
        $this->mask = new Imagick($mask);
        $this->clipper = new Imagick($clipper);
    }

    public function __destruct()
    {
        $this->mask->clear();
        $this->clipper->clear();
        $this->texture->clear();
    }

    public function draw(): self
    {

        try {
            // Resize texture to match mask if needed
            // if ($this->texture->getImageWidth() != $this->mask->getImageWidth() ||
            //     $this->texture->getImageHeight() != $this->mask->getImageHeight()) {
            //     $this->texture->resizeImage(
            //         $this->mask->getImageWidth(),
            //         $this->mask->getImageHeight(),
            //         Imagick::FILTER_LANCZOS,
            //         1
            //     );
            // }

            // --- Step 1: The Shading (Hard Light) ---
            // We blend the mask onto the texture to get the shadows and highlights

            $x = 0; // ($this->texture->getImageWidth() / 2);
            $y = -1 * ($this->texture->getImageHeight() / 3);

            $width = $this->mask->getImageWidth();
            $height = $this->mask->getImageHeight();

            $canvas = new Imagick();
            $canvas->newImage($width, $height, new ImagickPixel('transparent'));

            $canvas->compositeImage($this->texture, Imagick::COMPOSITE_DEFAULT, $x, $y);

            $this->texture = $canvas;

            $this->texture->compositeImage($this->mask, Imagick::COMPOSITE_HARDLIGHT, 0,0);

            $glareLayter = clone $this->mask;
            $glareLayter->blackThresholdImage("gray(98%)");

            $this->texture->compositeImage($glareLayter, Imagick::COMPOSITE_SCREEN, 0,0);

            // --- Step 2: The Cropping (Clipping) ---

            // We need a "Silhoutte" of the mask to cut the shape out.
            // If we use the shading mask directly, grey shadows might become semi-transparent.
            // So, we create a temporary "clipper" where anything NOT black becomes pure white.
            // $clipper = new Imagick('public/images/hand/nailshape.png');

            // Turn on the alpha channel for the clipper
            $this->clipper->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            // Apply a threshold: Pixels darker than 5% become black, everything else becomes white.
            // Adjust '5%' if your black background isn't perfectly black.
            $this->clipper->blackThresholdImage("gray(5%)");
            // Convert non-black pixels to pure white (creating a solid cookie cutter)
            $this->clipper->whiteThresholdImage("gray(5%)");

            // $this->clipper->writeImage('/Users/fabiannino/Developer/images-for-bundles/public/test-images/clipper.png');

            // Enable alpha channel on the main texture so it can hold transparency
            $this->texture->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            // Apply the clipper to the texture's opacity channel
            // This keeps pixels where the clipper is white, and removes them where it is black.
            $this->texture->compositeImage($this->clipper, Imagick::COMPOSITE_COPYOPACITY, 0, 0);

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }



        // if ($this->texture->getImageWidth() != $this->mask->getImageWidth() ||
        //     $this->texture->getImageHeight() != $this->mask->getImageHeight()) {
        //     $this->texture->resizeImage(
        //         $this->mask->getImageWidth(),
        //         $this->mask->getImageHeight(),
        //         Imagick::FILTER_LANCZOS,
        //         1
        //     );
        // }
        //
        // $maskX = -1 * ($this->texture->getImageWidth() / 4);
        // $maskY = ($this->mask->getImageHeight() / 4);
        // $this->texture->compositeImage($this->mask, Imagick::COMPOSITE_HARDLIGHT, $maskX, $maskY);
        //
        // $glareLayter = clone $this->mask;
        // $glareLayter->blackThresholdImage("gray(98%)");
        //
        // $this->texture->compositeImage($glareLayter, Imagick::COMPOSITE_SCREEN, 0,0);
        //
        return $this;
    }

    public function getResult(): Imagick
    {
        return clone $this->texture;
    }
}