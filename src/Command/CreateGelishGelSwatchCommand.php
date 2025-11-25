<?php

namespace App\Command;

use App\Service\Asset;
use App\Service\Color;
use App\Service\GelishGelSwatch;
use App\Service\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function basename;
use function preg_replace;
use const DIRECTORY_SEPARATOR;

#[AsCommand("create:gelish:gel:swatch")]
class CreateGelishGelSwatchCommand extends Command
{
    public function __construct(private Color $colorProfiles)
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
        $bottlePaths = $input->getOption("assets");
        $outputPath = $input->getOption("output");

        foreach ($swatchesPath as $key => $swatchPath) {
            $bottlePath = $bottlePaths[$key];
            $image = new Image(2000, 2000);

            $swatches = new Asset($this->colorProfiles);
            $swatches->addAsset($swatchPath);

            $bottles = new Asset($this->colorProfiles);
            $bottles->addAsset($bottlePath);

            $swatch = new GelishGelSwatch($image, $swatches, $bottles);

            $swatch->draw();

            $basename = preg_replace("/(.jpg)|(.png)/", '', basename($bottlePath));

            $image->saveImage($outputPath . DIRECTORY_SEPARATOR . $basename . "-swatch.jpg");
        }


        return Command::SUCCESS;
    }
}
