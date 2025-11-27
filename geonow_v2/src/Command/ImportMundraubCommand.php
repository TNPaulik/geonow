<?php

namespace App\Command;

use App\Entity\Geogroup;
use App\Entity\Location;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsCommand(
    name: 'app:import-mundraub',
    description: 'Imports data from mundraub.org',
)]
class ImportMundraubCommand extends Command
{
    private $em;
    private $tokenStorage;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user instanceof User) {
            $io->error('You must be logged in to run this command.');
            return 1;
        }

        $json = file_get_contents("https://mundraub.org/cluster/plant?bbox=1.0107421875000002,49.15296965617042,22.104492187500004,52.6030475337285&zoom=20&cat=1,2,3,4,5,6,7,8,9,10,11,12,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37");
        $data = json_decode($json, true);

        $repository = $this->em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['name' => 'Mundraub']);

        foreach ($data['features'] as $loc) {
            $location = new Location();
            $location->setLtd($loc['pos'][0]);
            $location->setLgt($loc['pos'][1]);
            $location->setGeogroup($group);
            $location->setUser($user);
            $location->setStatus(1);
            $this->em->persist($location);
        }
        $this->em->flush();

        $io->success('Data imported successfully.');

        return 0;
    }
}
