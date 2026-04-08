<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
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

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'error' => 'Invalid JSON'], 400);
        }

        $firstName = trim((string) ($payload['FIRSTNAME'] ?? ''));
        $lastName = trim((string) ($payload['LASTNAME'] ?? ''));
        $email = trim((string) ($payload['EMAIL'] ?? ''));
        $phone = trim((string) ($payload['SMS'] ?? ''));
        $subject = trim((string) ($payload['SUBJECT'] ?? ''));
        $message = trim((string) ($payload['MESSAGE'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $subject === '' || $message === '') {
            return $this->json(['ok' => false, 'error' => 'Missing required fields'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['ok' => false, 'error' => 'Invalid email'], 400);
        }

        $apiKey = $_ENV['BREVO_API_KEY'] ?? '';
        $senderEmail = $_ENV['BREVO_SENDER_EMAIL'] ?? '';
        $senderName = $_ENV['BREVO_SENDER_NAME'] ?? 'QwePic Contact Form';
        $toEmail = $_ENV['BREVO_TO_EMAIL'] ?? '';
        $toName = $_ENV['BREVO_TO_NAME'] ?? 'QwePic Team';

        if ($apiKey === '' || $senderEmail === '' || $toEmail === '') {
            return $this->json(['ok' => false, 'error' => 'Contact form not configured'], 500);
        }

        $client = HttpClient::create();

        try {
            $res = $client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'sender' => ['name' => $senderName, 'email' => $senderEmail],
                    'to' => [['email' => $toEmail, 'name' => $toName]],
                    'replyTo' => ['email' => $email, 'name' => $firstName . ' ' . $lastName],
                    'subject' => '[' . $subject . '] New message from ' . $firstName . ' ' . $lastName,
                    'htmlContent' =>
                        '<h2 style="font-family:sans-serif;">New contact form submission</h2>' .
                        '<table style="font-family:sans-serif;font-size:14px;border-collapse:collapse;">' .
                        '<tr><td style="padding:4px 12px 4px 0;color:#777;">Name</td><td>' . htmlspecialchars($firstName . ' ' . $lastName) . '</td></tr>' .
                        '<tr><td style="padding:4px 12px 4px 0;color:#777;">Email</td><td>' . htmlspecialchars($email) . '</td></tr>' .
                        '<tr><td style="padding:4px 12px 4px 0;color:#777;">Phone</td><td>' . htmlspecialchars($phone !== '' ? $phone : '—') . '</td></tr>' .
                        '<tr><td style="padding:4px 12px 4px 0;color:#777;">Inquiry</td><td>' . htmlspecialchars($subject) . '</td></tr>' .
                        '</table>' .
                        '<p style="font-family:sans-serif;font-size:14px;margin-top:16px;white-space:pre-wrap;">' . htmlspecialchars($message) . '</p>',
                ],
            ]);

            $status = $res->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $body = $res->getContent(false);
                return $this->json([
                    'ok' => false,
                    'error' => 'Email send failed',
                    'brevo_status' => $status,
                    'brevo_body' => $body,
                ], 502);
            }

            return $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            return $this->json([
                'ok' => false,
                'error' => 'Request failed',
                'exception' => $e->getMessage(),
            ], 502);
        }
    }
}
