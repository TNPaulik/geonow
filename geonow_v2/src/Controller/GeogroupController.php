<?php

namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Geooption;
use App\Entity\Location;
use App\Entity\User;
use App\Form\GeogroupType;
use App\MyTrait\MyReferer;
use App\Repository\GeogroupsRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/geogroup')]
class GeogroupController extends AbstractController
{
    use MyReferer;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'geogroup_index', methods: ['GET'])]
    public function index(GeogroupsRepository $geogroupsRepository): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);
        return $this->render('geogroup/index.html.twig', [
            'geogroups' => $geogroupsRepository->findAll(),
            'user' => $user
        ]);
    }

    #[Route('/s')]
    public function geoGroups(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($this->doctrine->getManager());
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $search = $request->request->get('search');
        if ($search) {
            $groups = $repository->createQueryBuilder('g')
                ->where('g.name LIKE :name')
                ->orWhere('g.text LIKE :name')
                ->setParameter('name', '%' . $search . '%')
                ->getQuery()
                ->getResult();
        } else {
            $groups = $repository->findAllPublic(1);
        }

        $locations = $em->getRepository(Location::class)->findAll();
        $a = [];
        foreach ($locations as $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/geogroups.twig', [
            'user' => $user,
            'groups' => $groups,
            'locations' => $locations,
            'locationsjsons' => $a,
            'search' => $search ?? ''
        ]);
    }

    #[Route('/addgroup')]
    public function addGroup(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(50, $em);

        if (!empty($request->request->all())) {
            $group = new Geogroup();
            $group->setName($request->request->get('name'));
            $group->setText($request->request->get('text'));
            $group->setType(Geogroup::$RESOLVE);
            $password = $request->request->get('password');
            if (!empty($password)) {
                $group->setPassword(md5($password));
            }
            $group->setUser($user);

            $em->persist($group);
            $em->flush();

            $uploads_dir = $this->getRootDir();

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $name = md5($group->getId()) . '.jpg';
                $imageFile->move($uploads_dir, $name);
            }

            $this->addFlash(
                'notice',
                $translator->trans('Group added')
            );

            return $this->redirectBack();
        }
        return $this->redirectBack();
    }

    #[Route('/show/{groupid<\d+>}', name: 'app_geogroup_showgroup')]
    public function showGroup(Request $request, int $groupid, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['id' => $groupid]);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        if (!empty($group->getPassword())) {
            if (!$user->hasJoinedGroup($group)) {
                $submittedPassword = $request->request->get('password');
                if (empty($submittedPassword) || $group->getPassword() != md5($submittedPassword)) {
                    $this->addFlash('notice', $translator->trans('Access denied'));
                    return $this->redirectBack();
                } else {
                    $user->addJoinedGroup($group);
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('notice', $translator->trans('Group joined'));
                }
            }
        } else {
            if (!$user->hasJoinedGroup($group)) {
                $user->addJoinedGroup($group);
                $em->persist($user);
                $em->flush();
                $this->addFlash('notice', $translator->trans('Group joined'));
            }
        }

        if ($user->hasAccessTo($group) === false) {
            $this->addFlash('notice', $translator->trans('Access denied'));
            return $this->redirectBack();
        }

        $a = [];
        $locations = $group->getLocations();
        foreach ($locations as $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/group.twig', [
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll(),
            'locations' => $locations,
            'locationsjsons' => $a
        ]);
    }

    #[Route('/showpost', name: 'showpost', methods: ['POST'])]
    public function showGroupPost(Request $request, TranslatorInterface $translator): Response
    {
        $groupName = $request->request->get('name');
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['name' => $groupName]);

        if (!$group) {
             $this->addFlash('notice', 'Group not found');
             return $this->generateUrl('geogroup_index');
        }

        if (!empty($group->getPassword())) {
            if (!$user->hasJoinedGroup($group)) {
                $password = $request->request->get('password');
                if (empty($password) || $group->getPassword() != md5($password)) {
                    return $this->render('index/geogroups.twig', [
                        'user' => $user,
                        'groups' => $em->getRepository(Geogroup::class)->findAll()
                    ]);
                } else {
                    $user->addJoinedGroup($group);
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('notice', $translator->trans('Group joined'));
                }
            }
        }

        $a = [];
        $locations = $group->getLocations();
        foreach ($locations as $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/group.twig', [
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll(),
            'locations' => $locations,
            'locationsjsons' => $a
        ]);
    }

    #[Route('/addoptions', methods: ['POST'])]
    public function addOptions(Request $request, TranslatorInterface $translator): Response
    {
        $groupid = $request->request->get('id');
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(10, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['id' => $groupid]);

        if (!$group) {
             throw $this->createNotFoundException('Group not found');
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        for ($i = 0; $i <= 10; $i++) {
            if (!empty($request->request->get('name' . $i)) && !empty($request->request->get('text' . $i))) {
                $option = new Geooption();
                $option->setName($request->request->get('name' . $i));
                $option->setText($request->request->get('text' . $i));
                $option->setColor($request->request->get('color' . $i));
                $option->setGeogroup($group);
                $em->persist($option);
            }
        }
        $em->flush();

        return $this->render('index/group.twig', [
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll()
        ]);
    }

    #[Route('/addadmin', methods: ['POST'])]
    public function addAdmin(Request $request, TranslatorInterface $translator): Response
    {
        $groupid = $request->request->get('groupid');
        $name = $request->request->get('name');

        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(10, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy(['id' => $groupid]);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $repositoryu = $em->getRepository(User::class);
        $userta = $repositoryu->findOneBy(['name' => $name]);

        if (!$userta) {
            $this->addFlash('notice', $translator->trans('User not found'));
            return $this->redirectBack();
        }

        $group->addAdmin($userta);
        $em->persist($group);
        $em->flush();

        $this->addFlash('notice', $translator->trans('Admin added'));
        return $this->redirectBack();
    }

    #[Route('/adduser', methods: ['POST'])]
    public function addUser(Request $request, TranslatorInterface $translator): Response
    {
        $groupid = $request->request->get('groupid');
        $name = $request->request->get('name');

        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(5, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy(['id' => $groupid]);

        if (!$group) { throw $this->createNotFoundException('Group not found'); }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $repositoryu = $em->getRepository(User::class);
        $userta = $repositoryu->findOneBy(['name' => $name]);

        if (!$userta) {
            $this->addFlash('notice', $translator->trans('User not found'));
            return $this->redirectBack();
        }

        $group->addUser($userta);
        $em->persist($group);
        $em->flush();

        $this->addFlash('notice', $translator->trans('User added'));
        return $this->redirectBack();
    }

    #[Route('/removeadmin/{groupid<\d+>}/{adminid<\d+>}')]
    public function removeAdmin(int $groupid, int $adminid, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(3, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy(['id' => $groupid]);
        if (!$group) { throw $this->createNotFoundException('Group not found'); }

        $repositoryu = $em->getRepository(User::class);
        $usertd = $repositoryu->findOneBy(['id' => $adminid]);
        if (!$usertd) {
            $this->addFlash('notice', $translator->trans('User not found'));
            return $this->redirectBack();
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $group->removeAdmin($usertd);
        $em->persist($group);
        $em->flush();

        $this->addFlash('notice', $translator->trans('Admin removed'));
        return $this->redirectBack();
    }

    #[Route('/removeuser/{groupid<\d+>}/{userid<\d+>}')]
    public function removeUser(int $groupid, int $userid, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy(['id' => $groupid]);
        if (!$group) { throw $this->createNotFoundException('Group not found'); }

        $repositoryu = $em->getRepository(User::class);
        $usertd = $repositoryu->findOneBy(['id' => $userid]);

        if (!$usertd) {
            $this->addFlash('notice', $translator->trans('User not found'));
            return $this->redirectBack();
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $group->removeUser($usertd);
        $em->persist($group);
        $em->flush();

        $this->addFlash('notice', $translator->trans('User removed'));
        return $this->redirectBack();
    }

    #[Route('/joinaction', methods: ['POST'])]
    public function joinAction(Request $request, TranslatorInterface $translator): Response
    {
        $em = $this->doctrine->getManager();
        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy(['name' => $request->request->get('name')]);
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $password = $request->request->get('password', '');

        if (is_object($group)) {
            if (!empty($group->getPassword())) {
                if (!$user->hasJoinedGroup($group)) {
                    if (empty($password) || $group->getPassword() != md5($password)) {
                        return $this->render('index/geogroups.twig', [
                            'user' => $user,
                            'groups' => $em->getRepository(Geogroup::class)->findAll()
                        ]);
                    } else {
                        $user->addJoinedGroup($group);
                        $em->persist($user);
                        $em->flush();
                        $this->addFlash('notice', $translator->trans('Group joined'));
                    }
                }
            }

            return $this->redirectToRoute('app_geogroup_showgroup', ['groupid' => $group->getId()]);
        } else {
            return $this->redirectToRoute('geogroup_index');
        }
    }

    public function getRootDir(): string
    {
        return $this->getParameter('kernel.project_dir') . '/public/img/g';
    }

    #[Route('/new', name: 'geogroup_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $geogroup = new Geogroup();
        $form = $this->createForm(GeogroupType::class, $geogroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($geogroup);
            $em->flush();

            return $this->redirectToRoute('geogroup_index');
        }

        return $this->render('geogroup/new.html.twig', [
            'geogroup' => $geogroup,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'geogroup_show', methods: ['GET'])]
    public function show(Geogroup $geogroup): Response
    {
        return $this->render('geogroup/show.html.twig', ['geogroup' => $geogroup]);
    }

    #[Route('/{id}/edit', name: 'geogroup_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Geogroup $geogroup, TranslatorInterface $translator): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());

        if ($user->hasAccessTo($geogroup) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $form = $this->createForm(GeogroupType::class, $geogroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute('geogroup_edit', ['id' => $geogroup->getId()]);
        }

        return $this->render('geogroup/edit.html.twig', [
            'user' => $user,
            'geogroup' => $geogroup,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'geogroup_delete')]
    public function delete(Request $request, Geogroup $geogroup, TranslatorInterface $translator): Response
    {
        $user = User::getCurrentUser($this->doctrine->getManager());

        if ($user->hasAccessTo($geogroup) !== 'w') {
            $this->addFlash('notice', $translator->trans('No access'));
            return $this->redirectBack();
        }

        $em = $this->doctrine->getManager();
        $em->remove($geogroup);
        $em->flush();

        $this->addFlash('notice', $translator->trans('Group deleted'));
        return $this->redirectBack();
    }
}
