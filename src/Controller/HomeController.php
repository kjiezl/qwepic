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
        $photos = $photoRepository->findBy(
            ['is_public' => true],
            ['created_at' => 'DESC'],
            30
        );

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
        ]);
    }
}
