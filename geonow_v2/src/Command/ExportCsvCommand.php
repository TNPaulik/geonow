<?php

namespace App\Command;

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-csv',
    description: 'Exports all locations to a CSV file',
)]
class ExportCsvCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $locations = $this->em->getRepository(Location::class)->findAll();

        $csv = "id,ltd,lgt,group,username/-hash\n";
        foreach ($locations as $location) {
            $as = [
                'id' => $location->getId(),
                'ltd' => $location->getLtd(),
                'lgt' => $location->getLgt(),
                'group' => $location->getGeogroup()->getName(),
                'user' => !empty($location->getUser()->getName()) ? $location->getUser()->getName() : $location->getUser()->getHash(),
            ];
            $csv .= implode(',', $as) . "\n";
        }

        $io->writeln($csv);

        return 0;
    }
}
