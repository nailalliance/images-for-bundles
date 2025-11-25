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
            // --- Step 1: Texture Tiling ---
            $geo = $this->texture->getImageGeometry();
            $w = $geo['width'];
            $h = $geo['height'];

            // Crop Center 40%
            $cropW = $w * 0.40;
            $cropH = $h * 0.40;
            $startX = ($w - $cropW) / 2;
            $startY = ($h - $cropH) / 2;
            $this->texture->cropImage($cropW, $cropH, $startX, $startY);
            $this->texture->setImagePage(0, 0, 0, 0);

            // Scale Down (50%)
            $this->texture->resizeImage($cropW * 0.5, $cropH * 0.5, Imagick::FILTER_LANCZOS, 1);

            // Tile across mask
            $maskW = $this->mask->getImageWidth();
            $maskH = $this->mask->getImageHeight();
            $canvas = new Imagick();
            $canvas->newImage($maskW, $maskH, new ImagickPixel('transparent'));
            $canvas = $canvas->textureImage($this->texture);
            $this->texture = $canvas;

            // --- Step 2: Base Prep ---
            // Keep base mostly bright (92%)
            // $this->texture->modulateImage(100, 100, 92);
            // $this->texture->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            // --- Step 2: Adaptive Base Darkening (NEW) ---

            // 1. Analyze the brightness of the swatch
            $stats = clone $this->texture;
            $stats->scaleImage(1, 1); // Shrink to 1px to get average color
            $pixel = $stats->getImagePixelColor(0,0);
            $color = $pixel->getColor();
            $stats->clear();
            // Calculate perceived brightness (0.0 to 1.0)
            $brightness = (($color['r'] * 0.299) + ($color['g'] * 0.587) + ($color['b'] * 0.114)) / 255;

            // Calculate Saturation (Vibrancy)
            // 0.0 (Grey/White) to 1.0 (Pure Color)
            $max = max($color['r'], $color['g'], $color['b']);
            $min = min($color['r'], $color['g'], $color['b']);
            $chroma = $max - $min;
            $saturation = ($max == 0) ? 0 : ($chroma / $max);

            $isVibrant = ($saturation > 0.15 || $brightness < 0.2);

            if ($isVibrant) {
                // glitter mode
                $this->texture->modulateImage(100, 105, 100);
            } else {
                // white / cream
                $target = ($brightness > 0.8) ? 82 : 92;
                $this->texture->modulateImage(100, 100, $target);
            }

            $this->texture->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            $shadowLayer = clone $this->mask;
            $shadowLayer->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);

            if ($isVibrant)
            {
                $shadowLayer->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $shadowLayer->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.4, Imagick::CHANNEL_ALPHA);

                $this->texture->compositeImage($shadowLayer, Imagick::COMPOSITE_HARDLIGHT, 0, 0);
            } else {
                // Keep Leveling: Cleans the middle of the nail so colors stay true
                $quantum = $shadowLayer->getQuantumRange();
                $whitePoint = $quantum['quantumRangeLong'] * 0.55;
                $shadowLayer->levelImage(0, 1.0, $whitePoint);

                // Shadow Opacity: 0.5 is subtle but visible.
                $shadowLayer->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $shadowLayer->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.6, Imagick::CHANNEL_ALPHA);

                $this->texture->compositeImage($shadowLayer, Imagick::COMPOSITE_MULTIPLY, 0, 0);
            }
            $shadowLayer->clear();


            // --- Step 4: High Gloss (The "Wet Look") ---
            $glareLayer = clone $this->mask;
            $glareLayer->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);

            // 1. TIGHTEN: Increase threshold to 85% (was 60%).
            // This restricts the glare to just the sharpest reflection line.
            $glareLayer->blackThresholdImage("gray(85%)");

            // 2. SOFTEN: Reduce opacity to 70% (0.7).
            // We removed the "1.5x brightness boost".
            // This makes the glare transparent enough to see the red color underneath.
            $glareLayer->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            $glareLayer->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.7, Imagick::CHANNEL_ALPHA);

            $this->texture->compositeImage($glareLayer, Imagick::COMPOSITE_SCREEN, 0, 0);
            $glareLayer->clear();


            // --- Step 5: Clipping (unchanged) ---
            $this->clipper->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $this->clipper->blackThresholdImage("gray(5%)");
            $this->clipper->whiteThresholdImage("gray(5%)");
            $this->texture->compositeImage($this->clipper, Imagick::COMPOSITE_COPYOPACITY, 0, 0);

        } catch (Exception $e) {
            error_log("Nail generation error: " . $e->getMessage());
        }

        return $this;
    }

    public function getResult(): Imagick
    {
        return clone $this->texture;
    }
}
