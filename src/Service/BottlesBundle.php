<?php

namespace App\Service;

use Imagick;

class BottlesBundle implements DrawerInterface
{
    private float $paddingPercent = 0.01;
    private float $gapXPercent = 0.07;
    private float $gapYPercent = 0.07;
    private int $maxBottlesPerRow = 6;

    private array $bottlesImages = [];

    public function __construct(
        private Image $image,
        private Asset $bottles
    )
    {}

    private function getRowCount (int $n): int
    {
        if ($n === 0) {
            return 0;
        }
        if ($n === 25)
        {
            return 4;
        }
        return intval(ceil($n / $this->maxBottlesPerRow));
    }

    private function distributeBottles(int $n, int $rows): array
    {
        if ($rows === 0) return [];
        $baseCount = floor($n / $rows);
        $reminder = $n % $rows;
        $distribution = [];
        for ($i = 0; $i < $rows; $i++)
        {
            $count = $baseCount + ($reminder > 0 ? 1 : 0);
            $distribution[] = intval($count);
            $reminder--;
        }
        return $distribution;
    }

    public function draw()
    {
        $pixelPaddingX = $this->image->getWidth() * $this->paddingPercent;
        $pixelPaddingY = $this->image->getHeight() * $this->paddingPercent;
        $usableWidth = $this->image->getWidth() - ($pixelPaddingX * 2);
        $usableHeight = $this->image->getHeight() - ($pixelPaddingY * 2);

        $rowCount = $this->getRowCount($this->bottles->getCount());
        $rows = $this->distributeBottles($this->bottles->getCount(), $rowCount);
        $maxBottlesPerRow = empty($rows) ? 0 : max($rows);

        $cols = $maxBottlesPerRow;
        $widthDenominator = $cols + (($cols -1) * $this->gapXPercent);
        $maxWidthByX = ($widthDenominator > 0) ? $usableWidth / $widthDenominator : 0;

        $heightDenominator = $rowCount + (($rowCount - 1) * $this->gapYPercent);
        $maxHeightByY = ($heightDenominator > 0) ? $usableHeight / $heightDenominator : 0;

        $bottleWidth = $maxWidthByX;
        $bottleHeight = $maxHeightByY;

        $loadedBottleMaxHeight = 0;
        $loadedBottleMaxWidth = 0;

        // Load bottles to get correct dimensions
        for ($i = 0; $i < $this->bottles->getCount(); $i++)
        {
            $this->bottlesImages[] = $this->bottles->loadAsset($i, $bottleWidth, $bottleHeight, true);
            $loadedBottleMaxHeight = max($loadedBottleMaxHeight, $this->bottlesImages[$i]->getImageHeight());
            $loadedBottleMaxWidth = max($loadedBottleMaxWidth, $this->bottlesImages[$i]->getImageWidth());
        }

        $bottleWidth = $loadedBottleMaxWidth;
        $bottleHeight = $loadedBottleMaxHeight;

        $gapX = $bottleWidth * $this->gapXPercent;
        $gapY = $bottleHeight * $this->gapYPercent;

        $totalGroupHeight = ($rowCount * $bottleHeight) + (($rowCount - 1) * $gapY);
        $groupOffsetY = $pixelPaddingY + ($usableHeight - $totalGroupHeight) / 2;

        $currentY = $groupOffsetY;

        $bottleIndex = 0;
        foreach ($rows as $countInRow)
        {
            $rowWidth = ($countInRow * $bottleWidth) + (($countInRow -1) * $gapX);
            $rowOffsetX = $pixelPaddingX + (($usableWidth - $rowWidth) / 2);

            for ($i = 0; $i < $countInRow; $i++)
            {
                $x = $rowOffsetX + ($i * ($bottleWidth + $gapX));
                $y = $currentY;

                $bottle = $this->bottlesImages[$bottleIndex++];

                $this->image->compositeImage($bottle, Imagick::COMPOSITE_DEFAULT, $x, $y);
            }

            $currentY += $bottleHeight + $gapY;
        }
    }
}