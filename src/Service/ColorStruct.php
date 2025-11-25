<?php

namespace App\Service;

use JetBrains\PhpStorm\ArrayShape;

class ColorStruct
{
    public function __construct(
        #[ArrayShape(["r" => "int|float", "g" => "int|float", "b" => "int|float", "a" => "int|float"])]
        readonly public array $rgb,
        #[ArrayShape(["hue" => "float", "saturation" => "float", "luminosity" => "float"])]
        readonly public array $hsl,
        readonly public string $name
    )
    {}

    public function luminosity(): float
    {
        return $this->hsl['luminosity'];
    }
}
