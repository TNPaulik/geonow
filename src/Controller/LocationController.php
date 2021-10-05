<?php

namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Location;
use App\Form\Location1Type;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Geooption;
use Symfony\Component\Translation\TranslatorInterface;
use App\MyTrait\MyReferer;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route("/location")
 */
class LocationController extends Controller
{
    use MyReferer;

    /**
     * @Route("/", name="location_index", methods="GET")
     */
    public function index(LocationRepository $locationRepository): Response
    {
        return $this->render('location/index.html.twig', ['locations' => $locationRepository->findAll()]);
    }

    /**
     * @Route("/getaviablelocations")
     */
    public function getAviableLocations(TranslatorInterface $translator) {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $request = Request::createFromGlobals();

        $lgt = $request->request->get('lgt', '');
        $ltd = $request->request->get('ltd', '');

        if (empty($lgt) || empty($ltd)) {
            $this->addFlash(
                'notice',
                $translator->trans('Location not set')
            );
            return $this->redirectBack();
        }

        $locations = $this->getDoctrine()
            ->getRepository(Location::class)
            ->findAllLocationsNear($ltd, $lgt, 50);

        $r = [];
        foreach ($locations AS $location) {
            $location->distance = $this->distance(
                $location->getLtd(),
                $location->getLgt(),
                $ltd,
                $lgt
            );
            $r[] = $location->json();
        }

        return $this->render('index/showLocations.html.twig', array(
            'user' => $user,
            'locations' => $locations
        ));

    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit = 'K') {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return (round($miles * 1.609344, 3)*1000);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    /**
     * @Route("/getjson")
     */
    public function getJson(TranslatorInterface $translator)
    {
        $locations = $this->getDoctrine()
            ->getRepository(Location::class)
            ->findAll();

        $r = [];
        foreach ($locations AS $location) {
            $r[] = $location->json();
        }

        return new JsonResponse($r);

    }

    /**
     * @Route("/resolve")
     */
    public function resolveWasteSubmit(TranslatorInterface $translator)
    {

        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($this->getDoctrine()->getManager());

        $ltd = $_POST['ltd'];
        $lgt = $_POST['lgt'];
        unset($_POST['ltd']);
        unset($_POST['lgt']);
        $lid = key($_POST);

        if (is_int($lid)) {

            if (!is_file($_FILES['image']['tmp_name'])) {

                $this->addFlash(
                    'notice',
                    $translator->trans('No Image given!')
                );

                return $this->redirectBack();
            }

            $location = $em->getRepository(Location::class)->find($lid);
            $group = $location->getGeogroup();

            if ($group->getType() == Geogroup::$RESOLVE) {
                $location->setStatus(Location::$RESOLVED);
                $location->setSolver($user);
            } else if ($group->getType() == Geogroup::$CONFIRM)  {

            }

            $em->persist($location);
            $em->flush();

            $uploads_dir = $this->getRootDir();

            $tmp_name = $_FILES['image']['tmp_name'];
            $name = md5($location->getId()) . '_resolved.jpg';
            move_uploaded_file($tmp_name, "$uploads_dir/$name");

            $this->addFlash(
                'notice',
                $translator->trans('Location marked as resolved!')
            );
        }

        return $this->redirectToRoute('base');

    }

    /**
     * @Route("/new", name="location_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $location = new Location();
        $form = $this->createForm(Location1Type::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($location);
            $em->flush();

            return $this->redirectToRoute('location_index');
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="location_show", methods="GET")
     */
    public function show(Location $location): Response
    {
        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        return $this->render('location/show.html.twig', [
            'location' => $location,
            'user' => $user
        ]);
    }

    /**
     * @Route("/{id}/edit", name="location_edit", methods="GET|POST")
     */
    public function edit(Request $request, Location $location): Response
    {
        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $form = $this->createForm(Location1Type::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('location_edit', ['id' => $location->getId()]);
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/{id}", name="location_delete", methods="DELETE")
     */
    public function delete(Request $request, Location $location): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($location);
            $em->flush();
        }

        return $this->redirectToRoute('location_index');
    }

    /**
     * @Route("/submit")
     */
    public function submit(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($em);

        if(!empty($_POST)) {

            if (!is_file($_FILES['image']['tmp_name'])) {

                $this->addFlash(
                    'notice',
                    $translator->trans('No Image given!')
                );

                return $this->redirectBack();
            }


            $location = new Location();
            $location->setLtd($_POST['ltd']);
            $location->setLgt($_POST['lgt']);
            $location->setUser($user);
            $location->setGeogroup($user->getActiveGroup());
            $location->setStatus(Location::$ACTIVE);

            $em->persist($location);
            $em->flush();

            unset($_POST['ltd']);
            unset($_POST['lgt']);

            $uploads_dir = $this->getRootDir();

            $tmp_name = $_FILES['image']['tmp_name'];
            $name = md5($location->getId()) . '.jpg';
            move_uploaded_file($tmp_name, "$uploads_dir/$name");

            if (false) {

                $em->remove($location);
                $em->flush();

                $this->addFlash(
                    'notice',
                    $translator->trans('Filetype not supported!')
                );

                return $this->redirectBack();

            }

            foreach ($_POST AS $optionid => $on) {
                $option = $em->getRepository(Geooption::class)->find($optionid);
                if ($option) {
                    $location->addGeooption($option);
                }
            }

            $em->persist($location);
            $em->flush();

            $this->addFlash(
                'notice',
                $translator->trans('Report sent!')
            );
        }

        return $this->redirectBack();
    }

    public function getRootDir() {
        return (__DIR__ . '/../../public/img/l');
    }
}
