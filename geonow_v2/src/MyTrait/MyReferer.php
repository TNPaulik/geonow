<?php

namespace App\MyTrait;

use Symfony\Component\HttpFoundation\Request;
use App\MyTrait\MyString;

trait MyReferer {
    use MyString;

    private function getRefererParams() {
        if (!isset($_SERVER['HTTP_REFERER']))
            return false;
        $referer = $_SERVER['HTTP_REFERER'];
        $lastPath = substr($referer, self::strposX($referer, '/', 3));
        return $this->get('router')->getMatcher()->match($lastPath);
    }

    private function redirectBack() {
        $params = $this->getRefererParams();
        if ($params === false) {
            return $this->redirectToRoute('base');
        }
        $route = $params['_route'];
        unset($params['_route'], $params['_controller']);
        return $this->redirectToRoute($route, $params);
    }
}