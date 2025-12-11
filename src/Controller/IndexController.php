<?php

namespace App\Controller;

use JetBrains\PhpStorm\NoReturn;
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


    #[Route('/')]
    public function index(SessionInterface $session): Response
    {
        $user = $this->getUserSession($session);
        return $this->render('index.html.twig', [
            'user' => $user
        ]);    }

    #[NoReturn] #[Route('/map')]
    public function map(): Response
    {
        $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        if (!$apiKey) {
            throw new RuntimeException('Set GOOGLE_MAPS_API_KEY in your .env file.');
        }

        return $this->render('map/index.html.twig', [
            'apiKey' => $_ENV['GOOGLE_MAPS_API_KEY'],
        ]);
    }

}

