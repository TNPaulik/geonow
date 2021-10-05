<?php
/**
 * Created by PhpStorm.
 * User: tnpau
 * Date: 31.10.2018
 * Time: 03:00 UserListener
 */

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserListener
{
    private $router;
    private $container;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function onKernelRequest($event,$event1,$event2)
    {
        //$event->getObjectManager();
        //$em = $this->getDoctrine()->getManager();
        //$user = User::getCurrentUser($em);
        //dd($user);
        //return $event->setResponse(new RedirectResponse('yipppie'));

        //$entityManager = $this->getDoctrine()->getManager();


        //dd($this->router, $event,$event1,$event2);
    }
}