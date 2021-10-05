<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Geogroup;
use Symfony\Component\Translation\TranslatorInterface;
use App\MyTrait\MyReferer;

/**
 * @Route("/user")
 */
class UserController extends Controller
{
    use MyReferer;
    /**
     * @Route("/", name="user_index", methods="GET")
     */
    public function index(UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $em);
        $locations = $user->getLocations();

        return $this->render('index/user.html.twig', array(
            'user' => $user,
            'locations' => $locations
        ));
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(TranslatorInterface $translator, Request $request) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $em);

        $repository = $em->getRepository(User::class);
        $usertl = $repository->findOneBy([
            'name' => $_POST['name'],
            'password' => md5($_POST['password'])
        ]);

        $host_names = explode(".", $request->getHost());
        $bottom_host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];

        if ($usertl) {

            setcookie('userhash', $usertl->getHash(), (int) (time() + 3600 * 24 * 30 * 12 * 15), '/', '.'.$bottom_host_name);

            $this->addFlash(
                'notice',
                $translator->trans('loged in')
            );

        } else {
            $this->addFlash(
                'notice',
                $translator->trans('wrong name or password')
            );
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/update", name="update")
     */
    public function update(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $em);

        $user->setName($_POST['name']);
        if (!empty($_POST['password']))
            $user->setName(md5($_POST['password']));
        $user->setEmail($_POST['email']);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/setgroup/{groupid<\d+>}")
     */
    public function setGroup($groupid, TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $em);

        $repository = $em->getRepository(Geogroup::class);
        $group = $repository->findOneBy([
            'id' => $groupid
        ]);

        $user->setActiveGroup($group);
        $em->persist($user);
        $em->flush();

        $this->addFlash(
            'notice',
            $group->getName() . ' ' . $translator->trans('set as active Group')
        );

        return $this->redirectBack();
    }

    /**
     * @Route("/addpoints", name="user_addp", methods="GET|POST")
     */
    public function addPoints(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($em);
        $user->addPoints(2, $em);


        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/new", name="user_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods="GET")
     */
    public function show(User $user): Response
    {
        $cuser = User::getCurrentUser($this->getDoctrine()->getManager());
        return $this->render('user/show.html.twig', [
            'usertoshow' => $user,
            'user' => $cuser
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods="GET|POST")
     */
    public function edit(Request $request, User $user): Response
    {
        $cuser = User::getCurrentUser($this->getDoctrine()->getManager());
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'usertoshow' => $user,
            'user' => $cuser,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods="DELETE")
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}
