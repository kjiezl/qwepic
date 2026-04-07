<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PhotoRepository $photoRepository): Response
    {
        // Show landing page for non-logged-in users
        if (!$this->getUser()) {
            return $this->render('home/landing.html.twig');
        }

        $photos = $photoRepository->findBy(
            ['is_public' => true],
            ['created_at' => 'DESC'],
            30
        );

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}
