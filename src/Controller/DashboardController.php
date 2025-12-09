<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        PhotoRepository $photoRepository,
        AlbumRepository $albumRepository,
        ActivityLogRepository $activityLogRepository,
    ): Response
    {
        $totalUsers = $userRepository->count([]);
        $totalPhotos = $photoRepository->count([]);
        $totalAlbums = $albumRepository->count([]);

        $recentLogs = $activityLogRepository->findBy([], ['created_at' => 'DESC'], 5);

        return $this->render('dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalPhotos' => $totalPhotos,
            'totalAlbums' => $totalAlbums,
            'recentLogs' => $recentLogs,
        ]);
    }
}
