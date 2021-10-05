<?php

namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Geooption;
use App\Entity\Location;
use App\Entity\User;
use App\Form\GeogroupType;
use App\MyTrait\MyReferer;
use App\Repository\GeogroupsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/geogroup")
 */
class GeogroupController extends Controller
{
    use MyReferer;

    /**
     * @Route("/", name="geogroup_index", methods="GET")
     */
    public function index(GeogroupsRepository $geogroupsRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);
        return $this->render('geogroup/index.html.twig', [
            'geogroups' => $geogroupsRepository->findAll(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/s")
     */
    public function geoGroups(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        if (isset($_POST['search'])) {
            $groups = $repository->createQueryBuilder('g')
                ->where('g.name LIKE :name')
                ->orWhere('g.text LIKE :name')
                ->setParameter('name', '%'.$_POST['search'].'%')
                ->getQuery()
                ->getResult();
        } else {
            $groups = $repository->findAllPublic(1);
        }

        $locations = $em->getRepository(Location::class)->findAll();
        $a = [];
        foreach ($locations AS $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/geogroups.twig', array(
            'user' => $user,
            'groups' => $groups,
            'locations' => $locations,
            'locationsjsons' => $a,
            'search' => isset($_POST['search']) ? $_POST['search'] : ''
        ));
    }

    /**
     * @Route("/addgroup")
     */
    public function addGroup(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(50, $em);

        if(!empty($_POST)) {
            $group = new Geogroup();
            $group->setName($_POST['name']);
            $group->setText($_POST['text']);
            $group->setType(Geogroup::$RESOLVE);
            if (!empty($_POST['password']))
                $group->setPassword(md5($_POST['password']));
            $group->setUser($user);

            $em->persist($group);
            $em->flush();

            $uploads_dir = $this->getRootDir();

            $tmp_name = $_FILES['image']['tmp_name'];
            $name = md5($group->getId()) . '.jpg';
            move_uploaded_file($tmp_name, "$uploads_dir/$name");

            $this->addFlash(
                'notice',
                $translator->trans('Group added')
            );

            return $this->redirectBack();
        }
    }

    /**
     * @Route("/show/{groupid<\d+>}", name="app_geogroup_showgroup")
     */
    public function showGroup($groupid, TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy([
            'id' => $groupid
        ]);

        if (!empty($group->getPassword())) {
            if (!$user->hasJoinedGroup($group)) {
                if (empty($_POST['password']) || $group->getPassword() != md5($_POST['password'])) {
                    $this->addFlash(
                        'notice',
                        $translator->trans('Access denied')
                    );
                    return $this->redirectBack();
                } else {
                    $user->addJoinedGroup($group);
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash(
                        'notice',
                        $translator->trans('Group joined')
                    );
                }
            }
        } else {
            if (!$user->hasJoinedGroup($group)) {
                $user->addJoinedGroup($group);
                $em->persist($user);
                $em->flush();
                $this->addFlash(
                    'notice',
                    $translator->trans('Group joined')
                );
            }
        }

        if ($user->hasAccessTo($group) === false) {
            $this->addFlash(
                'notice',
                $translator->trans('Access denied')
            );
            return $this->redirectBack();
        }

        $a = [];
        $locations = $group->getLocations();
        foreach ($locations AS $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/group.twig', array(
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll(),
            'locations' => $locations,
            'locationsjsons' => $a
        ));
    }

    /**
     * @Route("/showpost", name="showpost")
     */
    public function showGroupPost(TranslatorInterface $translator) {

        $groupid = $_POST['name'];

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy([
            'name' => $groupid
        ]);

        if (!empty($group->getPassword())) {
            if (!$user->hasJoinedGroup($group)) {
                if (empty($_POST['password']) || $group->getPassword() != md5($_POST['password'])) {
                    return $this->render('index/geogroups.twig', array(
                        'user' => $user,
                        'groups' => $em->getRepository(Geogroup::class)->findAll()
                    ));
                } else {
                    $user->addJoinedGroup($group);
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash(
                        'notice',
                        $translator->trans('Group joined')
                    );
                }
            }
        }

        $a = [];
        $locations = $group->getLocations();
        foreach ($locations AS $location) {
            $a[] = json_encode($location->json());
        }

        return $this->render('index/group.twig', array(
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll(),
            'locations' => $locations,
            'locationsjsons' => $a
        ));
    }

    /**
     * @Route("/addoptions")
     */
    public function addOptions(TranslatorInterface $translator) {

        $request = Request::createFromGlobals();
        $groupid = $request->request->get('id');

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(10, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy([
            'id' => $groupid
        ]);

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        for ($i = 0; $i <= 10; $i++) {

            if (!empty($request->request->get('name'.$i)) && !empty($request->request->get('text'.$i))) {
                $option = new Geooption();
                $option->setName($request->request->get('name'.$i));
                $option->setText($request->request->get('text'.$i));
                $option->setColor($request->request->get('color'.$i));
                $option->setGeogroup($group);
                $em->persist($option);
                $em->flush();
            }

        }

        return $this->render('index/group.twig', array(
            'user' => $user,
            'group' => $group,
            'groups' => $em->getRepository(Geogroup::class)->findAll()
        ));
    }

    /**
     * @Route("/addadmin")
     */
    public function addAdmin(TranslatorInterface $translator) {

        $request = Request::createFromGlobals();
        $groupid = $request->request->get('groupid');
        $name = $request->request->get('name');

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(10, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy([
            'id' => $groupid
        ]);

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $repositoryu = $em->getRepository(User::class);
        $userta = $repositoryu->findOneBy([
            'name' => $name
        ]);

        if (!$userta) {
            $this->addFlash(
                'notice',
                $translator->trans('User not found')
            );
            return $this->redirectBack();
        }

        $group->addAdmin($userta);

        $this->addFlash(
            'notice',
            $translator->trans('Admin added')
        );

        $em->persist($group);
        $em->flush();

        return $this->redirectBack();
    }

    /**
     * @Route("/adduser")
     */
    public function addUser(TranslatorInterface $translator) {

        $request = Request::createFromGlobals();
        $groupid = $request->request->get('groupid');
        $name = $request->request->get('name');

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(5, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy([
            'id' => $groupid
        ]);

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $repositoryu = $em->getRepository(User::class);
        $userta = $repositoryu->findOneBy([
            'name' => $name
        ]);

        if (!$userta) {
            $this->addFlash(
                'notice',
                $translator->trans('User not found')
            );
            return $this->redirectBack();
        }

        $group->addUser($userta);

        $this->addFlash(
            'notice',
            $translator->trans('User added')
        );

        $em->persist($group);
        $em->flush();

        return $this->redirectBack();
    }

    /**
     * @Route("/removeadmin/{groupid<\d+>}/{adminid<\d+>}")
     */
    public function removeAdmin($groupid, $adminid, TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(3, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy([
            'id' => $groupid
        ]);

        $repositoryu = $em->getRepository(User::class);
        $usertd = $repositoryu->findOneBy([
            'id' => $adminid
        ]);

        if (!$usertd) {
            $this->addFlash(
                'notice',
                $translator->trans('User not found')
            );
            return $this->redirectBack();
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $group->removeAdmin($usertd);
        $this->addFlash(
            'notice',
            $translator->trans('Admin removed')
        );

        $em->persist($group);
        $em->flush();

        return $this->redirectBack();
    }

    /**
     * @Route("/removeuser/{groupid<\d+>}/{userid<\d+>}")
     */
    public function removeUser($groupid, $userid, TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        $repositoryg = $em->getRepository(Geogroup::class);
        $group = $repositoryg->findOneBy([
            'id' => $groupid
        ]);

        $repositoryu = $em->getRepository(User::class);
        $usertd = $repositoryu->findOneBy([
            'id' => $userid
        ]);

        if (!$usertd) {
            $this->addFlash(
                'notice',
                $translator->trans('User not found')
            );
            return $this->redirectBack();
        }

        if ($user->hasAccessTo($group) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $group->removeUser($usertd);
        $this->addFlash(
            'notice',
            $translator->trans('User removed')
        );

        $em->persist($group);
        $em->flush();

        return $this->redirectBack();
    }

    /**
     * @Route("/joinaction")
     */
    public function joinAction(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy([
            'name' => $_POST['name']
        ]);
        $user = User::getCurrentUser($em);
        $user->addPoints(1, $em);

        if (empty($_POST['password'])) {
            $_POST['password'] = '';
        }

        if (is_object($group)) {

            $a = array(
                'groupid' => $group->getId()
            );

            if (!empty($group->getPassword())) {
                if (!$user->hasJoinedGroup($group)) {
                    if (empty($_POST['password']) || $group->getPassword() != md5($_POST['password'])) {
                        return $this->render('index/geogroups.twig', array(
                            'user' => $user,
                            'groups' => $em->getRepository(Geogroup::class)->findAll()
                        ));
                    } else {
                        $user->addJoinedGroup($group);
                        $em->persist($user);
                        $em->flush();
                        $this->addFlash(
                            'notice',
                            $translator->trans('Group joined')
                        );
                    }
                }
            }

            return $this->redirectToRoute(
                'app_geogroup_showgroup',
                $a
            );
        } else {
            return $this->redirectToRoute(
                '/join'
            );
        }

        return $response;
    }

    public function getRootDir() {
        return (__DIR__ . '/../../public/img/g');
    }

    /**
     * @Route("/new", name="geogroup_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $geogroup = new Geogroup();
        $form = $this->createForm(GeogroupType::class, $geogroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($geogroup);
            $em->flush();

            return $this->redirectToRoute('geogroup_index');
        }

        return $this->render('geogroup/new.html.twig', [
            'geogroup' => $geogroup,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="geogroup_show", methods="GET")
     */
    public function show(Geogroup $geogroup): Response
    {
        return $this->render('geogroup/show.html.twig', ['geogroup' => $geogroup]);
    }

    /**
     * @Route("/{id}/edit", name="geogroup_edit", methods="GET|POST")
     */
    public function edit(Request $request, Geogroup $geogroup, TranslatorInterface $translator): Response
    {
        $user = User::getCurrentUser($this->getDoctrine()->getManager());

        if ($user->hasAccessTo($geogroup) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $form = $this->createForm(GeogroupType::class, $geogroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('geogroup_edit', ['id' => $geogroup->getId()]);
        }

        return $this->render('geogroup/edit.html.twig', [
            'user' => $user,
            'geogroup' => $geogroup,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="geogroup_delete")
     */
    public function delete(Request $request, Geogroup $geogroup, TranslatorInterface $translator): Response
    {
        $user = User::getCurrentUser($this->getDoctrine()->getEntityManager());

        if ($user->hasAccessTo($geogroup) !== 'w') {
            $this->addFlash(
                'notice',
                $translator->trans('No access')
            );
            return $this->redirectBack();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($geogroup);
        $em->flush();

        $this->addFlash(
            'notice',
            $translator->trans('Group deleted')
        );

        return $this->redirectBack();
    }
}
