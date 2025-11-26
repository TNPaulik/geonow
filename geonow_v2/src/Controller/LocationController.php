<?php

namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Location;
use App\Form\Location1Type;
use App\Repository\LocationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Geooption;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\MyTrait\MyReferer;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/location')]
class LocationController extends AbstractController
{
    use MyReferer;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'location_index', methods: ['GET'])]
    public function index(LocationRepository $locationRepository): Response
    {
        return $this->render('location/index.html.twig', ['locations' => $locationRepository->findAll()]);
    }

    #[Route('/getaviablelocations')]
    public function getAviableLocations(Request $request, TranslatorInterface $translator): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $lgt = $request->request->get('lgt', '');
        $ltd = $request->request->get('ltd', '');

        if (empty($lgt) || empty($ltd)) {
            $this->addFlash('notice', $translator->trans('Location not set'));
            return $this->redirectBack();
        }

        $locations = $this->doctrine->getRepository(Location::class)->findAllLocationsNear($ltd, $lgt, 50);

        foreach ($locations as $location) {
            $location->distance = $this->distance($location->getLtd(), $location->getLgt(), $ltd, $lgt);
        }

        return $this->render('index/showLocations.html.twig', [
            'user' => $user,
            'locations' => $locations
        ]);
    }

    public function distance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'K'): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return round($miles * 1.609344, 3) * 1000;
        } elseif ($unit == "N") {
            return $miles * 0.8684;
        } else {
            return $miles;
        }
    }

    #[Route('/getjson')]
    public function getJson(): JsonResponse
    {
        $locations = $this->doctrine->getRepository(Location::class)->findAll();
        $r = [];
        foreach ($locations as $location) {
            $r[] = $location->json();
        }
        return new JsonResponse($r);
    }

    #[Route('/resolve', methods: ['POST'])]
    public function resolveWasteSubmit(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);

        $postData = $request->request->all();
        unset($postData['ltd'], $postData['lgt']);
        $lid = key($postData);

        if (is_int($lid)) {
            if (!$request->files->get('image')) {
                $this->addFlash('notice', $translator->trans('No Image given!'));
                return $this->redirectBack();
            }

            $location = $em->getRepository(Location::class)->find($lid);
            $group = $location->getGeogroup();

            if ($group->getType() == Geogroup::$RESOLVE) {
                $location->setStatus(Location::$RESOLVED);
                $location->setSolver($user);
            }

            $em->persist($location);
            $em->flush();

            $imageFile = $request->files->get('image');
            $name = md5($location->getId()) . '_resolved.jpg';
            $imageFile->move($this->getRootDir(), $name);

            $this->addFlash('notice', $translator->trans('Location marked as resolved!'));
        }

        return $this->redirectToRoute('base');
    }

    #[Route('/new', name: 'location_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $location = new Location();
        $form = $this->createForm(Location1Type::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($location);
            $em->flush();
            return $this->redirectToRoute('location_index');
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'location_show', methods: ['GET'])]
    public function show(Location $location): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        return $this->render('location/show.html.twig', [
            'location' => $location,
            'user' => $user
        ]);
    }

    #[Route('/{id}/edit', name: 'location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());
        $form = $this->createForm(Location1Type::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute('location_edit', ['id' => $location->getId()]);
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/{id}', name: 'location_delete', methods: ['DELETE'])]
    public function delete(Request $request, Location $location): Response
    {
        if ($this->isCsrfTokenValid('delete' . $location->getId(), $request->request->get('_token'))) {
            $em = $this->doctrine->getManager();
            $em->remove($location);
            $em->flush();
        }
        return $this->redirectToRoute('location_index');
    }

    #[Route('/submit', methods: ['POST'])]
    public function submit(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);

        if ($request->request->count() > 0) {
            if (!$request->files->get('image')) {
                $this->addFlash('notice', $translator->trans('No Image given!'));
                return $this->redirectBack();
            }

            $location = new Location();
            $location->setLtd($request->request->get('ltd'));
            $location->setLgt($request->request->get('lgt'));
            $location->setUser($user);
            $location->setGeogroup($user->getActiveGroup());
            $location->setStatus(Location::$ACTIVE);

            $em->persist($location);
            $em->flush();

            $imageFile = $request->files->get('image');
            $name = md5($location->getId()) . '.jpg';
            $imageFile->move($this->getRootDir(), $name);

            $postData = $request->request->all();
            unset($postData['ltd'], $postData['lgt']);

            foreach ($postData as $optionid => $on) {
                $option = $em->getRepository(Geooption::class)->find($optionid);
                if ($option) {
                    $location->addGeooption($option);
                }
            }
            $em->persist($location);
            $em->flush();

            $this->addFlash('notice', $translator->trans('Report sent!'));
        }
        return $this->redirectBack();
    }

    public function getRootDir(): string
    {
        return $this->getParameter('kernel.project_dir') . '/public/img/l';
    }
}
