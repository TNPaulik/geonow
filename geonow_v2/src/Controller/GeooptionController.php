<?php

namespace App\Controller;

use App\Entity\Geooption;
use App\Form\GeooptionType;
use App\Repository\GeooptionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/geooption')]
class GeooptionController extends AbstractController
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'geooption_index', methods: ['GET'])]
    public function index(GeooptionRepository $geooptionRepository): Response
    {
        return $this->render('geooption/index.html.twig', ['geooptions' => $geooptionRepository->findAll()]);
    }

    #[Route('/new', name: 'geooption_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $geooption = new Geooption();
        $form = $this->createForm(GeooptionType::class, $geooption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($geooption);
            $em->flush();

            return $this->redirectToRoute('geooption_index');
        }

        return $this->render('geooption/new.html.twig', [
            'geooption' => $geooption,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'geooption_show', methods: ['GET'])]
    public function show(Geooption $geooption): Response
    {
        return $this->render('geooption/show.html.twig', ['geooption' => $geooption]);
    }

    #[Route('/{id}/edit', name: 'geooption_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Geooption $geooption): Response
    {
        $form = $this->createForm(GeooptionType::class, $geooption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            return $this->redirectToRoute('geooption_edit', ['id' => $geooption->getId()]);
        }

        return $this->render('geooption/edit.html.twig', [
            'geooption' => $geooption,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'geooption_delete', methods: ['DELETE'])]
    public function delete(Request $request, Geooption $geooption): Response
    {
        if ($this->isCsrfTokenValid('delete'.$geooption->getId(), $request->request->get('_token'))) {
            $em = $this->doctrine->getManager();
            $em->remove($geooption);
            $em->flush();
        }

        return $this->redirectToRoute('geooption_index');
    }
}
