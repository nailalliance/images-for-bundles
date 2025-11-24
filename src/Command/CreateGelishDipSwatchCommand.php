<?php

namespace App\Command;

use App\Service\Asset;
use App\Service\BottlesBundle;
use App\Service\ColorProfiles;
use App\Service\GelishDipSwatch;
use App\Service\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand("create:gelish:dip:swatch")]
class CreateGelishDipSwatchCommand extends Command
{
    public function __construct(private ColorProfiles $colorProfiles)
    {
        parent::__construct();
    }

    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $swatchPath = "public/test-images/collection/GELMT-FL-25-20781-05-GIVEMEABUBBLY-FG-SWATCH.jpg";
        $jarPath = "public/test-images/collection/GELMT-FL-25-20781-1620580-GIVEMEABUBBLY-FG-JAR.jpg";

        $image = new Image(2000, 2000);

        $swatches = new Asset($this->colorProfiles);
        $swatches->addAsset($swatchPath);

        $jars = new Asset($this->colorProfiles);
        $jars->addAsset($jarPath);

        $swatch = new GelishDipSwatch($image, $swatches, $jars);

        $swatch->draw();

        $image->saveImage("public/test-images/test".basename($swatchPath).".jpg");

        return Command::SUCCESS;
    }
}