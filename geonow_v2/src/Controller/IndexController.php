<?php
namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Location;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

class IndexController extends AbstractController
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'base')]
    public function index(TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
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
            'user' => $user,
            'here_app_id' => $this->getParameter('here_app_id'),
            'here_app_code' => $this->getParameter('here_app_code'),
        ]);
    }

    #[Route('/join')]
    public function join(): Response
    {
        $user = $this->getUser();

        return $this->render('geogroup/join.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/setlocale/{locale<.+>}')]
    public function setlocale(Request $request, string $locale): Response
    {
        $request->getSession()->set('_locale', $locale);
        return $this->redirectToRoute('base');
    }

    #[Route('/impressum')]
    public function impressum(): Response
    {
        $user = $this->getUser();

        return $this->render('index/impressum.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/info')]
    public function info(): Response
    {
        $user = $this->getUser();

        return $this->render('index/info.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/removewaste', name: 'removewaste')]
    public function removeWaste(): Response
    {
        $user = $this->getUser();

        return $this->render('index/removewaste.html.twig', [
            'user' => $user,
        ]);
    }
}
