<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class ColorProfiles
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
}