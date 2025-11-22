<?php

namespace App\Service;

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use function basename;
use function dump;
use function intval;
use function var_dump;

class Image
{
    const CANVAS_WIDTH = 2412;
    const CANVAS_HEIGHT = 2208;

    const MARGIN = 210;
    const HORIZONTAL_GAP = 52;
    const VERTICAL_GAP = 60;

    private array $bottlesAttr = [
        'width' => 0,
        'height' => 0,
        'heightNew' => 0,
        'horizontalCount' => 0,
        'verticalCount' => 0,
    ];

    private $imagick;
    private array $bottles = [];
    private array $swatches = [];

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

    public function saveImage(string $path)
    {
        $this->imagick->writeImage($path);
        $this->imagick->clear();
    }

    public function createBase(): self
    {
        $this->imagick = new Imagick();
        $this->imagick->newImage(self::CANVAS_WIDTH, self::CANVAS_HEIGHT, new ImagickPixel("white"), "jpeg");
        return $this;
    }

    public function initBottles(int $count): self
    {
        $horizontalCount = 6;
        $verticalCount = $count / $horizontalCount;

        if ($verticalCount > 3) {
            $horizontalCount = 8;
            $verticalCount = $count / $horizontalCount;
        } else {
            $verticalCount = 2; // limits heights as if there were 2 rows
        }
        // $horizontalCount = 4;
        // $verticalCount = 2;

        $width = abs(((self::CANVAS_WIDTH - (self::MARGIN * 2)) / $horizontalCount) - self::HORIZONTAL_GAP/2); // - (self::HORIZONTAL_GAP/2));
        $height = abs(((self::CANVAS_HEIGHT - (self::MARGIN * 2)) / $verticalCount) - self::VERTICAL_GAP/2); // - (self::VERTICAL_GAP/2));

        $this->bottlesAttr['horizontalCount'] = $horizontalCount;
        $this->bottlesAttr['verticalCount'] = $verticalCount;
        $this->bottlesAttr['width'] = $width;
        $this->bottlesAttr['height'] = $height;
        $this->bottlesAttr['widthNew'] = $width; //intval($width * 1.75);

        // dump($count, $this->bottlesAttr);

        return $this;
    }

    public function addDecoration(): self
    {
        $decoration = new Imagick("public/images/2.jpg");
        $this->imagick->addImage($decoration);

        return $this;
    }

    public function addBottle(string $url, bool $new = false): self
    {
        echo basename($url) . PHP_EOL;

        if ($this->bottlesAttr['width'] === 0) {
            return $this;
        }

        $imageData = file_get_contents($url);

        $bottle = new Imagick();

        try {
            $bottle->readImageBlob($imageData);
        } catch (ImagickException $e) {
            error_log("Failed to decode image from URL: " . $url . " - Error: " . $e->getMessage());
            $bottle->clear();
            return $this; // Skip
        }

        if ($bottle->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            if ($this->cmykProfile && $this->srgbProfile) {
                $bottle->profileImage('icc', $this->cmykProfile);
                $bottle->profileImage('icc', $this->srgbProfile);
            } else {
                $bottle->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }
        }

        // $svgPathData = $bottle->getImageArtifact('clipping-path');
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

        if ($new)
        {
            // $bottle->resizeImage(self::CANVAS_WIDTH, 765, Imagick::FILTER_LANCZOS, 1, true);
            $bottle->resizeImage($this->bottlesAttr['widthNew'], $this->bottlesAttr['height'], Imagick::FILTER_LANCZOS, 1, true);
        } else {
            // $bottle->resizeImage(290, 760, Imagick::FILTER_LANCZOS, 1, true);
            $bottle->resizeImage($this->bottlesAttr['width'], $this->bottlesAttr['height'], Imagick::FILTER_LANCZOS, 1, true);
        }

        $this->bottles[] = [
            "new" => $new,
            "image" => $bottle
        ];

        return $this;
    }

    public function addSwatch(string $url, string $name): self
    {
        echo basename($url) . PHP_EOL;

        $imageData = file_get_contents($url);

        if ($imageData === false) {
            error_log("Failed to fetch image data from URL: " . $url);
            return $this; // Skip this swatch
        }

        // 2. Create and load the swatch
        $swatch = new Imagick();
        try {
            $swatch->readImageBlob($imageData);
        } catch (\ImagickException $e) {
            error_log("Failed to decode image from URL: " . $url . " - Error: " . $e->getMessage());
            $swatch->clear();
            return $this; // Skip
        }

        if ($swatch->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            if ($this->cmykProfile && $this->srgbProfile) {
                $swatch->profileImage('icc', $this->cmykProfile);
                $swatch->profileImage('icc', $this->srgbProfile);
            } else {
                $swatch->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }
        }

        $swatch->trimImage(0);
        $swatch->resizeImage(400, 400, Imagick::FILTER_LANCZOS, 1, true);

        $hexColor = '#000000';

        try {
            $sample = $swatch->clone();

            $sample = $sample->getImageRegion(20, 20, 190, 190);

            $sample->resizeImage(1,1, Imagick::FILTER_LANCZOS, 1);

            $pixel = $sample->getImagePixelColor(0,0);

            $hsl = $pixel->getHSL();
            $lightness = $hsl['luminosity'];

            if ($lightness > 0.85) {
                $hexColor = '#000000';
            } else {
                $hexColor = $pixel->getColorAsString();
            }

            $pixel->clear();
            $sample->clear();
        } catch (\ImagickException $e) {
            error_log("Failed to get center color for swatch: " . $name . " - Error: " . $e->getMessage());
        }

        $this->swatches[] = [
            'image' => $swatch,
            'name' => $name,
            'color' => $hexColor,
        ];

        return $this;
    }

    public function clearSwatches(): self
    {
        $this->swatches = [];

        return $this;
    }

    public function placeBottles(): self
    {
        $bottleCount = count($this->bottles);
        if ($bottleCount === 0) {
            return $this; // Nothing to do
        }

        // --- Define Layout Constants ---

        // Canvas dimensions (from createBase)
        $canvasHeight = self::CANVAS_HEIGHT;

        // Bottle dimensions (from addBottle)
        // $bottleWidth = 290;
        // $bottleHeight = 760;
        $bottleWidth = $this->bottlesAttr['width'];
        $bottleHeight = $this->bottlesAttr['height'];

        // Layout grid
        // $columns = 6;
        $columns = $this->bottlesAttr['horizontalCount'];
        $horizontalGap = self::HORIZONTAL_GAP; // Horizontal space between bottles
        $horizontalGap = (((self::CANVAS_WIDTH - (self::MARGIN*2)) - ($this->bottlesAttr['width'] * $this->bottlesAttr['horizontalCount'])) / ($this->bottlesAttr['horizontalCount'] - 1));

        // dump("horizontalGap: " . $horizontalGap);

        // Starting X position for the first column
        $startX = self::MARGIN;

        // --- Determine Y Positions ---

        // Check if we have a single row (6 bottles) or double row (12 bottles)
        $isSingleRow = ($bottleCount <= $columns);

        $currentY = self::MARGIN;

        if ($isSingleRow) {
            // 6-BOTTLE LAYOUT: Center the single row vertically
            // (CanvasHeight - BottleHeight) / 2
            $currentY = (int) (($canvasHeight - $bottleHeight) / 2);
        }

        $currentX = $startX;
        $bottleIndex = 0;

        // --- Loop and Place Bottles ---
        foreach ($this->bottles as $_bottle) {
            $bottle = $_bottle['image'];
            $new = $_bottle['new'];

            $xPlacement = $currentX;

            if ($new)
            {
                // $xPlacement = $xPlacement - 53;
                $xPlacement = $xPlacement - $this->bottlesAttr['width'] * .20;
            }

            // Place the bottle
            $this->imagick->compositeImage($bottle, Imagick::COMPOSITE_DEFAULT, (int)$xPlacement, (int)$currentY);

            $bottleIndex++;

            // Increment X for the next bottle
            $currentX += $bottleWidth + $horizontalGap;

            // Check if we need to wrap to the next row
            // This happens *only* on a 12-bottle layout, after the 6th bottle
            if (!$isSingleRow && $bottleIndex === $columns) {
                $bottleIndex = 0;
                $currentX = $startX;     // Reset X to the start
                $currentY += $bottleHeight + self::VERTICAL_GAP; // Move Y to the bottom row
            }
        }

        return $this;
    }

    public function placeSwatches(): self
    {
        $swatchCount = count($this->swatches);
        if ($swatchCount === 0) {
            return $this; // Nothing to do
        }

        // --- Define Layout Constants ---
        $swatchWidth = 400;  // As set in addSwatch
        $swatchHeight = 400; // As set in addSwatch
        $paddingLeft = 230;
        $paddingRight = 230;
        $canvasWidth = self::CANVAS_WIDTH;
        $canvasHeight = self::CANVAS_HEIGHT;
        $verticalGap = 30;

        $textSpace = 220;

        $itemHeight = $swatchHeight + $textSpace;

        // --- Determine Grid Shape ---
        // Content area is the canvas width minus the side paddings
        $contentWidth = $canvasWidth - $paddingLeft - $paddingRight; // self::CANVAS_WIDTH - 460 = 1952

        // Max columns that can fit in the content area
        // floor(1952 / 400) = 4 columns
        $columns = (int) floor($contentWidth / $swatchWidth);
        if ($swatchCount === 6)
        {
            $columns = 3;
        }

        // Total rows needed
        $rows = (int) ceil($swatchCount / $columns);

        // --- Vertical Centering ---
        // Calculate the total height of the grid
        $gridHeight = ($rows * $itemHeight) + (($rows - 1) * $verticalGap);

        // Calculate the starting Y to center the grid
        $currentY = (int) (($canvasHeight - $gridHeight) / 2);

        $draw = new ImagickDraw();

        $fontPath = '/Users/art3/Library/Application Support/Adobe/CoreSync/plugins/livetype/.r/.50175.otf';

        // $draw->setFontFamily($fontPath);
        $draw->setFont($fontPath);
        $draw->setFontSize(70);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        // --- Loop and Place Swatches (Row by Row) ---
        $swatchIndex = 0;
        for ($r = 0; $r < $rows; $r++) {

            // Find how many swatches are in this specific row
            $swatchesInThisRow = min($columns, $swatchCount - $swatchIndex);

            // Calculate total width of swatches in *this* row
            $rowSwatchWidth = $swatchesInThisRow * $swatchWidth;

            // Calculate the "space-evenly" gap for this row
            // (Total space - swatch space) / (gaps + 1)
            $totalGapSpace = $contentWidth - $rowSwatchWidth;
            $gapCount = $swatchesInThisRow + 1;
            $horizontalGap = (int) ($totalGapSpace / $gapCount);

            // Set the starting X for this row
            $currentX = $paddingLeft + $horizontalGap;

            // Inner loop to place swatches for this row
            for ($c = 0; $c < $swatchesInThisRow; $c++) {
                if ($swatchIndex >= $swatchCount) {
                    break; // Should not happen, but safe
                }

                $swatchData = $this->swatches[$swatchIndex];
                $swatchImage = $swatchData['image'];
                $swatchName = $swatchData['name'];
                $swatchColor = $swatchData['color'];

                $this->imagick->compositeImage($swatchImage, Imagick::COMPOSITE_DEFAULT, (int)$currentX, (int)$currentY);

                $draw->setFillColor($swatchColor);

                $textCenterX = $currentX + ($swatchWidth / 2);
                $textStartY = $currentY + $swatchHeight + 70;

                $this->wordWrapAnnotate(
                    $draw,
                    $swatchName,
                    $swatchWidth,
                    $textCenterX,
                    $textStartY
                );

                // Move X for the next swatch in this row
                $currentX += $swatchWidth + $horizontalGap;
                $swatchIndex++;
            }

            // Move Y for the next row
            $currentY += $itemHeight + $verticalGap;
        }

        $draw->clear();

        return $this;
    }

    public function placeHandGlam(string $url, bool $new = false, bool $atOrigin = false): self
    {
        echo basename($url) . PHP_EOL;
        $imageData = file_get_contents($url);

        $hand = new Imagick();

        try {
            $hand->readImageBlob($imageData);
        } catch (ImagickException $e) {
            error_log("Failed to decode image from URL: " . $url . " - Error: " . $e->getMessage());
            $hand->clear();
            return $this; // Skip
        }

        if ($hand->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            if ($this->cmykProfile && $this->srgbProfile) {
                $hand->profileImage('icc', $this->cmykProfile);
                $hand->profileImage('icc', $this->srgbProfile);
            } else {
                $hand->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }
        }

        $hand->trimImage(false);


        if ($new)
        {
            $hand->resizeImage(self::CANVAS_WIDTH * 1.2, self::CANVAS_HEIGHT * 1.2, Imagick::FILTER_LANCZOS, 1, true);
            $this->imagick->compositeImage($hand, Imagick::COMPOSITE_DEFAULT, 500, 150);
        } elseif ($atOrigin) {
            $hand->resizeImage(self::CANVAS_WIDTH, self::CANVAS_HEIGHT*2, Imagick::FILTER_LANCZOS, 1, true);
            $this->imagick->compositeImage($hand, Imagick::COMPOSITE_DEFAULT, 0, 0);
        }else {
            $hand->resizeImage(self::CANVAS_WIDTH * 1.2, self::CANVAS_HEIGHT * 1.2, Imagick::FILTER_LANCZOS, 1, true);
            $this->imagick->compositeImage($hand, Imagick::COMPOSITE_DEFAULT, -500, -225);
        }


        $decoration = new Imagick("public/images/3.png");
        $this->imagick->compositeImage($decoration, Imagick::COMPOSITE_OVER, 0, 0);

        return $this;
    }

    private function wordWrapAnnotate(ImagickDraw $draw, string $text, int $maxWidth, int $startX, int $startY): void
    {
        $canvas = $this->imagick;
        $words = preg_split('/(?<=\S)\s+(?=\S)/', $text);
        if (!$words) {
            return;
        }

        $line = '';
        $lineHeight = 0;
        $currentY = $startY;

        foreach ($words as $word) {
            $testLine = $line . ($line === '' ? '' : ' ') . $word;

            $metrics = $canvas->queryFontMetrics($draw, $testLine);

            if ($lineHeight === 0)
            {
                $lineHeight = $metrics['textHeight'] * .65;
            }

            if ($metrics['textWidth'] <= $maxWidth || $line === '') {
                $line = $testLine;
            } else {
                $canvas->annotateImage($draw, $startX, $currentY, 0, $line);

                $line = $word;

                $currentY += $lineHeight;
            }
        }

        if ($line !== '') {
            $canvas->annotateImage($draw, $startX, $currentY, 0, $line);
        }
    }
}
