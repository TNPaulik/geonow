<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Geogroup;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\MyTrait\MyReferer;

#[Route('/user')]
class UserController extends AbstractController
{
    use MyReferer;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);
        $locations = $user->getLocations();

        return $this->render('index/user.html.twig', [
            'user' => $user,
            'locations' => $locations
        ]);
    }

    #[Route('/setgroup/{groupid<\d+>}')]
    public function setGroup(int $groupid, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['id' => $groupid]);

        $user->setActiveGroup($group);
        $em->persist($user);
        $em->flush();

        $this->addFlash('notice', $group->getName() . ' ' . $translator->trans('set as active Group'));

        return $this->redirectBack();
    }

    #[Route('/addpoints', name: 'user_addp', methods: ['GET', 'POST'])]
    public function addPoints(): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(2, $em);

        return $this->redirectToRoute('user_index');
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $cuser = User::getCurrentUser($this->doctrine->getManager());
        return $this->render('user/show.html.twig', [
            'usertoshow' => $user,
            'user' => $cuser
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $cuser = User::getCurrentUser($this->doctrine->getManager());
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'usertoshow' => $user,
            'user' => $cuser,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em = $this->doctrine->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}
