<?php

namespace App\Command;

use App\Service\ImageLegacy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function file_get_contents;
use function json_decode;

#[AsCommand("images:create")]
class CreateImageCommand extends Command
{
    public function __construct(private ImageLegacy $image)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption("path", null, InputOption::VALUE_REQUIRED, "ImageLegacy path");
        $this->addOption("folder", null, InputOption::VALUE_REQUIRED, "Folder path");
        $this->addOption('swatches', null, InputOption::VALUE_NEGATABLE, "No Swatches", true);
        $this->addOption('bottles', null, InputOption::VALUE_NEGATABLE, "No Swatches", true);
        $this->addOption('hands', null, InputOption::VALUE_NEGATABLE, "No Swatches", true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getOption("path");
        $folder = $input->getOption("folder");
        $swatches = $input->getOption("swatches");
        $hands = $input->getOption("hands");
        $bottles = $input->getOption("bottles");

        $sources = json_decode(file_get_contents($path), true);

        foreach ($sources as $source) {
            $io->title("Creating image");
            $name = preg_replace('/[^a-z0-9]/i', '-', $source['name']);
            $fileName = $source['sku'] . "-" . $name;

            if ($bottles){
                $io->title("Bottles");

                $bottle = $this->image->createBase()->initBottles(count($source['products']));

                foreach ($source['products'] as $product) {
                    if (empty($product['bottle'])) {
                        continue;
                    }
                    $bottle->addBottle($product['bottle'], isset($product['new']));
                }

                $bottle->placeBottles()
                    ->saveImage($folder . "/{$fileName}-bottle.jpg");
            }

            if ($swatches) {
                $io->title("Swatches");

                $swatch = $this->image->createBase()->addDecoration();

                $maxSwatches = 12;
                $swatchCount = 0;
                $swatchImage = 1;
                foreach ($source['products'] as $product) {
                    if (empty($product['swatch'])) {
                        continue;
                    }
                    $swatch->addSwatch($product['swatch'], $product['name']);
                    $swatchCount++;
                    if ($swatchCount >= $maxSwatches) {
                        $swatch->placeSwatches()
                            ->saveImage($folder . "/{$fileName}-swatch-{$swatchImage}.jpg");
                        $swatch->clearSwatches();
                        $swatchCount = 0;
                        $swatchImage++;
                        $swatch = $this->image->createBase()->addDecoration();
                    }
                }

                $swatch->placeSwatches()
                    ->saveImage($folder . "/{$fileName}-swatch-{$swatchImage}.jpg");
            }

            if ($hands) {
                $io->title("Hands");

                $x = 1;
                foreach ($source['products'] as $product) {
                    if (empty($product['hand'])) {
                        continue;
                    }
                    $hand = $this->image->createBase();
                    $hand->placeHandGlam($product['hand'], isset($product['new']), isset($product['atOrigin']))
                        ->saveImage($folder . "/{$fileName}-hand-{$x}.jpg");
                    $x++;
                    // break;
                }
            }

            $io->info("Creating image $name");
        }


        return Command::SUCCESS;
    }
}
