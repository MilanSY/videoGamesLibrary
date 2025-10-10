<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Vérifier si c'est une requête API
        if (!$this->isApiRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();

        // Récupération du code de statut HTTP
        $statusCode = 500;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        // Création de la réponse JSON
        $response = new JsonResponse([
            'error' => true,
            'message' => $exception->getMessage(),
            'status' => $statusCode,
        ], $statusCode);

        // Remplacer la réponse par défaut par notre réponse JSON
        $event->setResponse($response);
    }

    private function isApiRequest($request): bool
    {
        $contentType = $request->headers->get('Content-Type');
        $acceptHeader = $request->headers->get('Accept');
        $pathInfo = $request->getPathInfo();

        // Vérifier si c'est une requête API basée sur :
        // - Le chemin commence par /api ou /auth
        // - Le Content-Type contient application/json
        // - L'header Accept contient application/json
        return str_starts_with($pathInfo, '/api')
            || str_starts_with($pathInfo, '/auth')
            || str_contains($contentType, 'application/json')
            || str_contains($acceptHeader, 'application/json');
    }
}
