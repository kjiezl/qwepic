<?php

namespace App\Controller;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/albums')]
final class AlbumController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        #[Autowire('%album_cover_upload_dir%')] private string $albumCoverUploadDir,
        #[Autowire('%photo_upload_dir%')] private string $photoUploadDir,
        #[Autowire('%photo_thumbnail_dir%')] private string $photoThumbnailDir,
    ) {
    }

    #[Route(name: 'app_album_index', methods: ['GET'])]
    public function index(AlbumRepository $albumRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $albums = $albumRepository->findAll();
        } elseif ($this->isGranted('ROLE_PHOTOGRAPHER')) {
            $albums = $albumRepository->findBy(['photographer' => $this->getUser()]);
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('album/index.html.twig', [
            'albums' => $albums,
        ]);
    }

    #[Route('/new', name: 'app_album_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PHOTOGRAPHER')) {
            throw $this->createAccessDeniedException();
        }

        $album = new Album();
        $formOptions = [
            'show_photographer_field' => $this->isGranted('ROLE_ADMIN'),
            'photographer_choices' => $this->isGranted('ROLE_ADMIN') ? $userRepository->findPhotographers() : [],
        ];

        $form = $this->createForm(AlbumType::class, $album, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Photographers can only create albums for themselves
            if ($this->isGranted('ROLE_PHOTOGRAPHER') && !$this->isGranted('ROLE_ADMIN')) {
                $album->setPhotographer($this->getUser());
            }

            $coverFile = $form->get('coverImageFile')->getData();
            if ($coverFile !== null) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug((string) $originalFilename)->lower();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();

                try {
                    $coverFile->move($this->albumCoverUploadDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading the cover image.');

                    return $this->redirectToRoute('app_album_new');
                }

                $album->setCoverImagePath('uploads/albums/'.$newFilename);
            }

            $photoFiles = $form->get('photoFiles')->getData();
            if (is_iterable($photoFiles)) {
                foreach ($photoFiles as $imageFile) {
                    if ($imageFile === null) {
                        continue;
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug((string) $originalFilename)->lower();
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move($this->photoUploadDir, $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'There was a problem uploading one of the album photos.');
                        continue;
                    }

                    $photo = new Photo();
                    $photo->setAlbum($album);
                    $photo->setTitle($originalFilename ?: 'Photo');
                    $photo->setStoragePath($newFilename);

                    if (!is_dir($this->photoThumbnailDir)) {
                        @mkdir($this->photoThumbnailDir, 0777, true);
                    }

                    @copy(
                        rtrim($this->photoUploadDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$newFilename,
                        rtrim($this->photoThumbnailDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$newFilename
                    );

                    $photo->setThumbnailPath($newFilename);

                    $entityManager->persist($photo);
                }
            }

            $entityManager->persist($album);
            $entityManager->flush();

            return $this->redirectToRoute('app_album_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('album/new.html.twig', [
            'album' => $album,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_album_show', methods: ['GET'])]
    public function show(Album $album, PhotoRepository $photoRepository): Response
    {
        $this->denyAccessUnlessAlbumOwnerOrAdmin($album);

        $photos = $photoRepository->findBy(['album' => $album]);

        return $this->render('album/show.html.twig', [
            'album' => $album,
            'photos' => $photos,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_album_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Album $album, EntityManagerInterface $entityManager, UserRepository $userRepository, PhotoRepository $photoRepository): Response
    {
        $this->denyAccessUnlessAlbumOwnerOrAdmin($album);

        $photos = $photoRepository->findBy(['album' => $album]);

        $formOptions = [
            'show_photographer_field' => $this->isGranted('ROLE_ADMIN'),
            'photographer_choices' => $this->isGranted('ROLE_ADMIN') ? $userRepository->findPhotographers() : [],
        ];

        $form = $this->createForm(AlbumType::class, $album, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure photographer ownership is not changed by non-admins
            if ($this->isGranted('ROLE_PHOTOGRAPHER') && !$this->isGranted('ROLE_ADMIN')) {
                $album->setPhotographer($this->getUser());
            }

            $coverFile = $form->get('coverImageFile')->getData();
            if ($coverFile !== null) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug((string) $originalFilename)->lower();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();

                try {
                    $coverFile->move($this->albumCoverUploadDir, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading the cover image.');

                    return $this->redirectToRoute('app_album_edit', ['id' => $album->getId()]);
                }

                $album->setCoverImagePath('uploads/albums/'.$newFilename);
            }

            $photoFiles = $form->get('photoFiles')->getData();
            if (is_iterable($photoFiles)) {
                foreach ($photoFiles as $imageFile) {
                    if ($imageFile === null) {
                        continue;
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug((string) $originalFilename)->lower();
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move($this->photoUploadDir, $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'There was a problem uploading one of the album photos.');
                        continue;
                    }

                    $photo = new Photo();
                    $photo->setAlbum($album);
                    $photo->setTitle($originalFilename ?: 'Photo');
                    $photo->setStoragePath($newFilename);

                    if (!is_dir($this->photoThumbnailDir)) {
                        @mkdir($this->photoThumbnailDir, 0777, true);
                    }

                    @copy(
                        rtrim($this->photoUploadDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$newFilename,
                        rtrim($this->photoThumbnailDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$newFilename
                    );

                    $photo->setThumbnailPath($newFilename);

                    $entityManager->persist($photo);
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_album_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('album/edit.html.twig', [
            'album' => $album,
            'form' => $form,
            'photos' => $photos,
        ]);
    }

    #[Route('/{id}', name: 'app_album_delete', methods: ['POST'])]
    public function delete(Request $request, Album $album, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessAlbumOwnerOrAdmin($album);

        if ($this->isCsrfTokenValid('delete'.$album->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($album);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_album_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessAlbumOwnerOrAdmin(Album $album): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($this->isGranted('ROLE_PHOTOGRAPHER') && $album->getPhotographer() === $this->getUser()) {
            return;
        }

        throw $this->createAccessDeniedException();
    }
}
