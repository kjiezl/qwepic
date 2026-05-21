<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getMessage($exception, $statusCode);

        $response = new JsonResponse([
            'code' => $statusCode,
            'message' => $message,
        ], $statusCode);

        $response->headers->set('Content-Type', 'application/json');
        $event->setResponse($response);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($exception instanceof AccessDeniedException) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getMessage(\Throwable $exception, int $statusCode): string
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'Error';
        }

        if ($exception instanceof AuthenticationException) {
            return 'Authentication required';
        }

        if ($exception instanceof AccessDeniedException) {
            return 'Access denied';
        }

        return 'An internal server error occurred';
    }
}
