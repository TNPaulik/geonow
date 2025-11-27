<?php

namespace App\Command;

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:convert-db-images-to-files',
    description: 'Converts all database images to files',
)]
class ConvertDbImagesToFilesCommand extends Command
{
    private $em;
    private $kernel;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel)
    {
        $this->em = $em;
        $this->kernel = $kernel;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $locations = $this->em->getRepository(Location::class)->findAll();

        foreach ($locations as $location) {
            $location->convertDBimageToFile($this->kernel->getProjectDir() . '/public/images/');
        }

        $io->success('All database images converted to files successfully.');

        return 0;
    }
}
