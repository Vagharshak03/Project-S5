<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeoRootsController extends AbstractController
{
    #[Route('/georoots', name: 'georoots_index')]
    public function index(): Response
    {
        return $this->render('georoots/index.html.twig');
    }

    #[Route('/georoots/login', name: 'georoots_login')]
    public function login(): Response
    {
        return $this->render('georoots/login.html.twig');
    }

    #[Route('/georoots/register', name: 'georoots_register')]
    public function register(): Response
    {
        return $this->render('georoots/register.html.twig');
    }

    #[Route('/georoots/profile', name: 'georoots_profile')]
    public function profile(): Response
    {
        return $this->render('georoots/profile.html.twig');
    }

    #[Route('/georoots/donate', name: 'georoots_donate')]
    public function donate(): Response
    {
        return $this->render('georoots/donate.html.twig');
    }

    #[Route('/georoots/card', name: 'georoots_card')]
    public function card(): Response
    {
        return $this->render('georoots/card.html.twig.twig');
    }
}
