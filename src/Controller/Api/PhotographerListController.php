<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/customer/photographers')]
class PhotographerListController extends AbstractController
{
    #[Route('', name: 'api_customer_photographers_list', methods: ['GET'])]
    public function list(
        UserRepository $userRepository,
        AlbumRepository $albumRepository,
        PhotoRepository $photoRepository,
    ): JsonResponse {
        $photographers = $userRepository->findPhotographers();

        $data = array_map(function (User $photographer) use ($albumRepository, $photoRepository) {
            $albums = $albumRepository->findBy(['photographer' => $photographer]);
            $albumsCount = count($albums);

            $photosCount = 0;
            foreach ($albums as $album) {
                $photosCount += $photoRepository->count(['album' => $album]);
            }

            return [
                'id' => $photographer->getId(),
                'username' => $photographer->getUsername(),
                'email' => $photographer->getEmail(),
                'albums_count' => $albumsCount,
                'photos_count' => $photosCount,
                'created_at' => $photographer->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $photographers);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_customer_photographers_show', methods: ['GET'])]
    public function show(
        User $photographer,
        AlbumRepository $albumRepository,
        PhotoRepository $photoRepository,
    ): JsonResponse {
        if (!in_array('ROLE_PHOTOGRAPHER', $photographer->getRoles(), true)) {
            return $this->json(['message' => 'Photographer not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$photographer->isActive()) {
            return $this->json(['message' => 'Photographer not found'], Response::HTTP_NOT_FOUND);
        }

        $albums = $albumRepository->findBy(['photographer' => $photographer, 'is_public' => true]);

        $albumsData = array_map(function ($album) use ($photoRepository) {
            $photos = $photoRepository->findBy(['album' => $album, 'is_public' => true]);

            $photosData = array_map(fn($photo) => [
                'id' => $photo->getId(),
                'title' => $photo->getTitle(),
                'caption' => $photo->getCaption(),
                'storage_path' => $photo->getStoragePath(),
                'thumbnail_path' => $photo->getThumbnailPath(),
                'created_at' => $photo->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $photos);

            return [
                'id' => $album->getId(),
                'title' => $album->getTitle(),
                'description' => $album->getDescription(),
                'cover_image_path' => $album->getCoverImagePath(),
                'photos_count' => count($photos),
                'photos' => $photosData,
                'created_at' => $album->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $albums);

        return $this->json([
            'id' => $photographer->getId(),
            'username' => $photographer->getUsername(),
            'email' => $photographer->getEmail(),
            'albums_count' => count($albums),
            'albums' => $albumsData,
            'created_at' => $photographer->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
