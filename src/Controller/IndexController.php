<?php

namespace App\Controller;

use JetBrains\PhpStorm\NoReturn;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    private HttpClientInterface $http;

    public function __construct(HttpClientInterface $http)
    {
        $this->http = $http;
    }


    #[Route('/', name: 'home')]
    public function index(SessionInterface $session): Response
    {
        return $this->render('georoots/index.html.twig', $session->all()
        );    }

    #[NoReturn] #[Route('/map', name: 'georoots_map')]
    public function map(): Response
    {
        $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        if (!$apiKey) {
            throw new RuntimeException('Set GOOGLE_MAPS_API_KEY in your .env file.');
        }

        return $this->render('georoots/map.html.twig', [
            'apiKey' => $_ENV['GOOGLE_MAPS_API_KEY'],
        ]);
    }

    #[Route('/profile', name: 'georoots_profile')]
    public function profile(): Response
    {
        return $this->render('georoots/profile.html.twig');
    }

    #[Route('/donate', name: 'georoots_donate')]
    public function donate(): Response
    {
        return $this->render('georoots/donate.html.twig');
    }

    #[Route('/card', name: 'georoots_card')]
    public function card(): Response
    {
        return $this->render('georoots/card.html.twig');
    }

    #[Route('/treechoice', name: 'georoots_treechoice')]
    public function treeChoice(): Response
    {
        return $this->render('georoots/treechoice.html.twig');
    }

}

