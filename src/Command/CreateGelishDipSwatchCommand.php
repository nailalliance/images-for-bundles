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
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption("swatches", "s", InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY);
        $this->addOption("assets", "a", InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY);
        $this->addOption("output", "o", InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $swatchesPath = $input->getOption("swatches");
        $jarsPath = $input->getOption("assets");
        $outputPath = $input->getOption("output");

        foreach ($swatchesPath as $key => $swatchPath) {
            $jarPath = $jarsPath[$key];

            $image = new Image(2000, 2000);

            $swatches = new Asset($this->colorProfiles);
            $swatches->addAsset($swatchPath);

            $jars = new Asset($this->colorProfiles);
            $jars->addAsset($jarPath);

            $swatch = new GelishDipSwatch($image, $swatches, $jars);

            $swatch->draw();

            $image->saveImage($outputPath . DIRECTORY_SEPARATOR . basename($jarPath) . ".jpg");
        }

        return Command::SUCCESS;
    }
}