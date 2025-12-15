<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/users')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword !== null && $plainPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $primaryRole = $form->get('primaryRole')->getData() ?: 'ROLE_USER';
            $user->setRoles([$primaryRole]);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword !== null && $plainPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $primaryRole = $form->get('primaryRole')->getData() ?: 'ROLE_USER';
            $user->setRoles([$primaryRole]);

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/disable', name: 'app_user_disable', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function disable(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('disable'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->setIsActive(false);
            $entityManager->flush();

            $actor = $this->getUser();
            if ($actor instanceof User) {
                $description = sprintf(
                    'Admin %s disabled user %s (ID %d).',
                    $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                    $user->getUsername() ?: ($user->getEmail() ?? 'unknown'),
                    $user->getId(),
                );
                $activityLogger->log('DISABLE', $actor, 'USER', $user->getId(), $description);
            }

            $this->addFlash('success', 'User has been disabled successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/enable', name: 'app_user_enable', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function enable(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('enable'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->setIsActive(true);
            $entityManager->flush();

            $actor = $this->getUser();
            if ($actor instanceof User) {
                $description = sprintf(
                    'Admin %s enabled user %s (ID %d).',
                    $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                    $user->getUsername() ?: ($user->getEmail() ?? 'unknown'),
                    $user->getId(),
                );
                $activityLogger->log('ENABLE', $actor, 'USER', $user->getId(), $description);
            }

            $this->addFlash('success', 'User has been enabled successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
