<?php
namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Location;
use App\Entity\User;
use App\MyTrait\MyReferer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

class IndexController extends AbstractController
{
    use MyReferer;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'base')]
    public function index(TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $group = $user->getActiveGroup();
        if ($group === null) {
            $group = $this->doctrine->getRepository(Geogroup::class)->findAll()[0];
            $user->setActiveGroup($group);
            $em->persist($user);
            $em->flush();
        }

        $options = $group->getOptions();

        return $this->render('index/index.html.twig', [
            'group' => $group,
            'options' => $options,
            'user' => $user
        ]);
    }

    #[Route('/join')]
    public function join(): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $user->addPoints(50, $this->doctrine->getManager());

        return $this->render('geogroup/join.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/setlocale/{locale<.+>}')]
    public function setlocale(Request $request, string $locale): Response
    {
        $request->getSession()->set('_locale', $locale);
        return $this->redirectBack();
    }

    #[Route('/importmundraub')]
    public function importmundraub(): void
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $em = $this->doctrine->getManager();
        $json = file_get_contents("https://mundraub.org/cluster/plant?bbox=1.0107421875000002,49.15296965617042,22.104492187500004,52.6030475337285&zoom=20&cat=1,2,3,4,5,6,7,8,9,10,11,12,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37");
        $data = json_decode($json, true);

        $repository = $this->doctrine->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['name' => 'Mundraub']);

        foreach ($data['features'] as $loc) {
            $location = new Location();
            $location->setLtd($loc['pos'][0]);
            $location->setLgt($loc['pos'][1]);
            $location->setGeogroup($group);
            $location->setUser($user);
            $location->setStatus(1);
            $em->persist($location);
        }
        $em->flush();
    }

    #[Route('/impressum')]
    public function impressum(): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $user->addPoints(1, $this->doctrine->getManager());

        return $this->render('index/impressum.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/info')]
    public function info(): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $user->addPoints(1, $this->doctrine->getManager());

        return $this->render('index/info.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/removewaste', name: 'removewaste')]
    public function removeWaste(): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $user->addPoints(10, $this->doctrine->getManager());

        return $this->render('index/removewaste.html.twig', [
            'user' => $user,
        ]);
    }

    public function getRootDir(string $dir = ''): string
    {
        return $this->getParameter('kernel.project_dir') . '/' . $dir;
    }

    #[Route('/convert123')]
    public function convertDbImagesToFiles(): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $locations = $this->doctrine->getRepository(Location::class)->findAll();

        foreach ($locations as $location) {
            $location->convertDBimageToFile($this->getRootDir('public/images/'));
        }

        return $this->render('index/showLocations.html.twig', [
            'user' => $user,
            'locations' => $locations
        ]);
    }

    #[Route('/exportcsv')]
    public function exportcsv(): void
    {
        $locations = $this->doctrine->getRepository(Location::class)->findAll();
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

        echo "<pre>";
        echo $csv;
        exit;
    }
}
