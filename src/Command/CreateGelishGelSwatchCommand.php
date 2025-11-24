<?php

namespace App\Command;

use App\Service\Asset;
use App\Service\ColorProfiles;
use App\Service\GelishGelSwatch;
use App\Service\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand("create:gelish:gel:swatch")]
class CreateGelishGelSwatchCommand extends Command
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

        $swatchesPath = [
            "public/test-images/collection/GELMT-FL-25-20781-01-NIGHTAFTERNIGHT-FG-SWATCH.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-05-GIVEMEABUBBLY-FG-SWATCH.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-06-MOONLITMOMENTS-FG-SWATCH.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-01-NIGHTAFTERNIGHT-FG-SWATCH.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-05-GIVEMEABUBBLY-FG-SWATCH.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-06-MOONLITMOMENTS-FG-SWATCH.jpg",
        ];
        $bottlePaths = [
            "public/test-images/collection/GELMT-FL-25-20781-1110576-NIGHTAFTERNIGHT-FG-BOTTLE.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-1110580-GIVEMEABUBBLY-FG-BOTTLE.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-1110581-MOONLITMOMENTS-FG-BOTTLE.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-1250576-NIGHTAFTERNIGHT-FG-BOTTLE.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-1250580-GIVEMEABUBBLY-FG-BOTTLE.jpg",
            "public/test-images/collection/GELMT-FL-25-20781-1250581-MOONLITMOMENTS-FG-BOTTLE.jpg",
        ];

        foreach ($swatchesPath as $key => $swatchPath) {
            $bottlePath = $bottlePaths[$key];
            $image = new Image(2000, 2000);

            $swatches = new Asset($this->colorProfiles);
            $swatches->addAsset($swatchPath);

            $bottles = new Asset($this->colorProfiles);
            $bottles->addAsset($bottlePath);

            $swatch = new GelishGelSwatch($image, $swatches, $bottles);

            $swatch->draw();

            $image->saveImage("public/test-images/test" . basename($bottlePath) . ".jpg");
        }


        return Command::SUCCESS;
    }
}