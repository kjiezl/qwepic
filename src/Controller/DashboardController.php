<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        PhotoRepository $photoRepository,
        AlbumRepository $albumRepository,
        ActivityLogRepository $activityLogRepository,
    ): Response {
        $user = $this->getUser();

        // Admin dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            $totalUsers = $userRepository->count([]);

            $allUsers = $userRepository->findAll();
            $totalAdmins = 0;
            $totalPhotographers = 0;
            $totalRegularUsers = 0;

            foreach ($allUsers as $u) {
                if (!$u instanceof \App\Entity\User) {
                    continue;
                }

                $roles = $u->getRoles();

                if (in_array('ROLE_ADMIN', $roles, true)) {
                    $totalAdmins++;
                }

                if (in_array('ROLE_PHOTOGRAPHER', $roles, true)) {
                    $totalPhotographers++;
                }

                if (in_array('ROLE_USER', $roles, true) || (!in_array('ROLE_ADMIN', $roles, true) && !in_array('ROLE_PHOTOGRAPHER', $roles, true))) {
                    $totalRegularUsers++;
                }
            }

            $totalPhotos = $photoRepository->count([]);
            $totalAlbums = $albumRepository->count([]);

            $recentLogs = $activityLogRepository->findBy([], ['created_at' => 'DESC'], 5);

            return $this->render('dashboard/index.html.twig', [
                'totalUsers' => $totalUsers,
                'totalAdmins' => $totalAdmins,
                'totalPhotographers' => $totalPhotographers,
                'totalRegularUsers' => $totalRegularUsers,
                'totalPhotos' => $totalPhotos,
                'totalAlbums' => $totalAlbums,
                'recentLogs' => $recentLogs,
            ]);
        }

        // Photographer dashboard
        if ($this->isGranted('ROLE_PHOTOGRAPHER')) {
            // Albums and photos owned by this photographer
            $albums = $albumRepository->findBy(['photographer' => $user]);
            $photos = $photoRepository->findByPhotographer($user);

            return $this->render('dashboard/photographer.html.twig', [
                'albums' => $albums,
                'photos' => $photos,
            ]);
        }

        // Other roles are not allowed to access the dashboard
        throw $this->createAccessDeniedException();
    }
}
