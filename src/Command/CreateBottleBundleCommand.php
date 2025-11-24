<?php

namespace App\Command;

use App\Service\Asset;
use App\Service\BottlesBundle;
use App\Service\ColorProfiles;
use App\Service\Image;
use DateTime;
use DateTimeZone;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption("assets", "a", InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY);
        $this->addOption("output", "o", InputOption::VALUE_REQUIRED);
        $this->addOption("combined", null, InputOption::VALUE_NEGATABLE, "Bundled", true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $paths = $input->getOption("assets");
        $outputPath = $input->getOption("output");
        $combined = $input->getOption("combined");

        if ($combined) {
            $image = new Image(2000, 2000);

            $bottles = new Asset($this->colorProfiles);

            foreach($paths as $path) {
                $bottles->addAsset($path);
            }

            $bundle = new BottlesBundle($image, $bottles);

            $bundle->draw();

            $time = new DateTime("now", new DateTimeZone("America/Los_Angeles"));
            $image->saveImage($outputPath . DIRECTORY_SEPARATOR . "bundle-" . $time->format('Y-m-d_H-i-s') . ".jpg");
            return Command::SUCCESS;
        }

        foreach($paths as $path) {
            $image = new Image(2000, 2000);

            $bottles = new Asset($this->colorProfiles);
            $bottles->addAsset($path);

            $bundle = new BottlesBundle($image, $bottles);

            $bundle->draw();

            $image->saveImage($outputPath . DIRECTORY_SEPARATOR . basename($path) . ".jpg");
        }

        return Command::SUCCESS;
    }
}