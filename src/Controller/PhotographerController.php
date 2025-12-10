<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/photographers')]
final class PhotographerController extends AbstractController
{
    #[Route('', name: 'app_photographer_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, AlbumRepository $albumRepository, PhotoRepository $photoRepository): Response
    {
        $allUsers = $userRepository->findAll();

        $photographers = array_filter($allUsers, static function ($user): bool {
            if (!$user instanceof User) {
                return false;
            }

            $roles = $user->getRoles();

            return in_array('ROLE_PHOTOGRAPHER', $roles, true) && $user->isActive();
        });

        $photographersData = [];

        foreach ($photographers as $photographer) {
            $albums = $albumRepository->findBy(['photographer' => $photographer]);
            $albumsCount = count($albums);

            $photosCount = 0;
            if ($albumsCount > 0) {
                foreach ($albums as $album) {
                    $photosCount += $photoRepository->count(['album' => $album]);
                }
            }

            $photographersData[] = [
                'user' => $photographer,
                'albumsCount' => $albumsCount,
                'photosCount' => $photosCount,
            ];
        }

        return $this->render('photographer/index.html.twig', [
            'photographers' => $photographersData,
        ]);
    }
}
