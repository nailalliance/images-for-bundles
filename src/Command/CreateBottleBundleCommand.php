<?php

namespace App\Command;

use App\Service\Asset;
use App\Service\BottlesBundle;
use App\Service\ColorProfiles;
use App\Service\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand("create:bottle:bundle")]
class CreateBottleBundleCommand extends Command
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

        $paths = [
            "public/test-images/21859-GEL-1110851-GrandJewels-FG.png",
            "public/test-images/22195-GEL-SG-Tips-1148010-TipAdhesive-15ml-FG.jpg",
            "public/test-images/22633-Gel-LaLaLoveYou-Bottle-FG.jpg",
            // "public/test-images/21859-GEL-1110851-GrandJewels-FG.png",
            // "public/test-images/22195-GEL-SG-Tips-1148010-TipAdhesive-15ml-FG.jpg",
            // "public/test-images/22633-Gel-LaLaLoveYou-Bottle-FG.jpg",
            // "public/test-images/21859-GEL-1110851-GrandJewels-FG.png",
            // "public/test-images/22195-GEL-SG-Tips-1148010-TipAdhesive-15ml-FG.jpg",
            // "public/test-images/22633-Gel-LaLaLoveYou-Bottle-FG.jpg",
            // "public/test-images/21859-GEL-1110851-GrandJewels-FG.png",
            // "public/test-images/22195-GEL-SG-Tips-1148010-TipAdhesive-15ml-FG.jpg",
            // "public/test-images/22633-Gel-LaLaLoveYou-Bottle-FG.jpg",
        ];

        $image = new Image(2000, 2000);

        $bottles = new Asset($this->colorProfiles);

        foreach($paths as $path) {
            $bottles->addAsset($path);
        }

        $bundle = new BottlesBundle($image, $bottles);

        $bundle->draw();

        $image->saveImage("public/test-images/test.jpg");

        return Command::SUCCESS;
    }
}