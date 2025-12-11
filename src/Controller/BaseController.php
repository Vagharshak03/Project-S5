<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BaseController extends AbstractController
{
    protected function getUserSession(SessionInterface $session): ?array
    {
        if ($session->has('user_id')) {
            return [
                'id' => $session->get('user_id'),
                'fullname' => $session->get('user_fullname'),
                'email' => $session->get('user_email'),
            ];
        }
        return null;
    }
}
