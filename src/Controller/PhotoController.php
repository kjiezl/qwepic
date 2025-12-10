<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Form\PhotoType;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('dashboard/photos')]
final class PhotoController extends AbstractController
{
    #[Route(name: 'app_photo_index', methods: ['GET'])]
    public function index(PhotoRepository $photoRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $photos = $photoRepository->findAll();
        } elseif ($this->isGranted('ROLE_PHOTOGRAPHER')) {
            $photos = $photoRepository->findByPhotographer($this->getUser());
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('photo/index.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/new', name: 'app_photo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AlbumRepository $albumRepository): Response
    {
        $photo = new Photo();

        if ($this->isGranted('ROLE_ADMIN')) {
            $form = $this->createForm(PhotoType::class, $photo);
        } elseif ($this->isGranted('ROLE_PHOTOGRAPHER')) {
            $albums = $albumRepository->findBy(['photographer' => $this->getUser()]);
            $form = $this->createForm(PhotoType::class, $photo, [
                'allowed_albums' => $albums,
            ]);
        } else {
            throw $this->createAccessDeniedException();
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure photographers can only assign photos to their own albums
            if ($this->isGranted('ROLE_PHOTOGRAPHER') && !$this->isGranted('ROLE_ADMIN')) {
                $album = $photo->getAlbum();
                if (!$album || $album->getPhotographer() !== $this->getUser()) {
                    throw $this->createAccessDeniedException();
                }
            }

            $entityManager->persist($photo);
            $entityManager->flush();

            return $this->redirectToRoute('app_photo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('photo/new.html.twig', [
            'photo' => $photo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_photo_show', methods: ['GET'])]
    public function show(Photo $photo): Response
    {
        $this->denyAccessUnlessPhotoOwnerOrAdmin($photo);

        return $this->render('photo/show.html.twig', [
            'photo' => $photo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_photo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Photo $photo, EntityManagerInterface $entityManager, AlbumRepository $albumRepository): Response
    {
        $this->denyAccessUnlessPhotoOwnerOrAdmin($photo);

        if ($this->isGranted('ROLE_ADMIN')) {
            $form = $this->createForm(PhotoType::class, $photo);
        } else {
            // Photographer: limit selectable albums to their own
            $albums = $albumRepository->findBy(['photographer' => $this->getUser()]);
            $form = $this->createForm(PhotoType::class, $photo, [
                'allowed_albums' => $albums,
            ]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_PHOTOGRAPHER') && !$this->isGranted('ROLE_ADMIN')) {
                $album = $photo->getAlbum();
                if (!$album || $album->getPhotographer() !== $this->getUser()) {
                    throw $this->createAccessDeniedException();
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_photo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('photo/edit.html.twig', [
            'photo' => $photo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_photo_delete', methods: ['POST'])]
    public function delete(Request $request, Photo $photo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessPhotoOwnerOrAdmin($photo);

        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($photo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_photo_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessPhotoOwnerOrAdmin(Photo $photo): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($this->isGranted('ROLE_PHOTOGRAPHER') && $photo->getAlbum() && $photo->getAlbum()->getPhotographer() === $this->getUser()) {
            return;
        }

        throw $this->createAccessDeniedException();
    }
}
