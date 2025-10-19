<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    // Codes d'erreur personnalisés
    private const ERROR_CODES = [
        'VALIDATION_ERROR' => 'VAL001',
        'NOT_FOUND' => 'NOT001',
        'UNAUTHORIZED' => 'AUTH001',
        'FORBIDDEN' => 'AUTH002',
        'CONFLICT' => 'CONF001',
        'METHOD_NOT_ALLOWED' => 'METH001',
        'INTERNAL_ERROR' => 'INT001',
        'DATABASE_ERROR' => 'DB001',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private string $environment
    ) {
    }

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

        // Logger l'erreur
        $this->logException($exception, $request);

        // Traiter l'exception selon son type
        $responseData = $this->handleException($exception);

        // Ajouter des métadonnées
        $responseData['timestamp'] = (new \DateTime())->format(\DateTime::ISO8601);
        $responseData['path'] = $request->getPathInfo();

        // En mode développement, ajouter la stack trace
        if ($this->environment === 'dev' && !isset($responseData['trace'])) {
            $responseData['trace'] = $exception->getTraceAsString();
            $responseData['file'] = $exception->getFile();
            $responseData['line'] = $exception->getLine();
        }

        // Créer la réponse JSON
        $response = new JsonResponse($responseData, $responseData['status']);

        // Remplacer la réponse par défaut
        $event->setResponse($response);
    }

    private function handleException(\Throwable $exception): array
    {
        // 1. Erreurs de validation
        if ($exception instanceof ValidationFailedException) {
            return $this->handleValidationException($exception);
        }

        // 2. Erreurs 404 - Entity non trouvée (ParamConverter)
        if ($exception instanceof EntityNotFoundException) {
            return [
                'error' => true,
                'message' => 'Ressource non trouvée',
                'code' => self::ERROR_CODES['NOT_FOUND'],
                'status' => 404,
            ];
        }

        // 3. Erreurs 404 - Route non trouvée
        if ($exception instanceof NotFoundHttpException) {
            return [
                'error' => true,
                'message' => 'Endpoint non trouvé',
                'code' => self::ERROR_CODES['NOT_FOUND'],
                'status' => 404,
            ];
        }

        // 4. Erreurs de méthode HTTP non autorisée
        if ($exception instanceof MethodNotAllowedHttpException) {
            return [
                'error' => true,
                'message' => 'Méthode HTTP non autorisée pour cet endpoint',
                'code' => self::ERROR_CODES['METHOD_NOT_ALLOWED'],
                'allowed_methods' => $exception->getHeaders()['Allow'] ?? null,
                'status' => 405,
            ];
        }

        // 5. Erreurs JWT
        if ($exception instanceof JWTDecodeFailureException) {
            return [
                'error' => true,
                'message' => 'Token JWT invalide ou expiré',
                'code' => self::ERROR_CODES['UNAUTHORIZED'],
                'status' => 401,
            ];
        }

        // 6. Erreurs d'authentification
        if ($exception instanceof AuthenticationException) {
            return [
                'error' => true,
                'message' => 'Authentification requise',
                'code' => self::ERROR_CODES['UNAUTHORIZED'],
                'status' => 401,
            ];
        }

        // 7. Erreurs d'accès refusé
        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            return [
                'error' => true,
                'message' => 'Accès refusé : permissions insuffisantes',
                'code' => self::ERROR_CODES['FORBIDDEN'],
                'status' => 403,
            ];
        }

        // 8. Contraintes de base de données - Clé unique
        if ($exception instanceof UniqueConstraintViolationException) {
            return [
                'error' => true,
                'message' => 'Cette ressource existe déjà (violation de contrainte d\'unicité)',
                'code' => self::ERROR_CODES['CONFLICT'],
                'status' => 409,
            ];
        }

        // 9. Contraintes de base de données - Clé étrangère
        if ($exception instanceof ForeignKeyConstraintViolationException) {
            return [
                'error' => true,
                'message' => 'Impossible de supprimer : cette ressource est utilisée par d\'autres entités',
                'code' => self::ERROR_CODES['DATABASE_ERROR'],
                'status' => 409,
            ];
        }

        // 10. Contraintes de base de données - Général
        if ($exception instanceof ConstraintViolationException) {
            return [
                'error' => true,
                'message' => 'Erreur de contrainte de base de données',
                'code' => self::ERROR_CODES['DATABASE_ERROR'],
                'status' => 400,
            ];
        }

        // 11. Exceptions HTTP génériques
        if ($exception instanceof HttpExceptionInterface) {
            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'status' => $exception->getStatusCode(),
            ];
        }

        // 12. Erreur générique (500)
        return [
            'error' => true,
            'message' => $this->environment === 'prod' 
                ? 'Une erreur interne est survenue' 
                : $exception->getMessage(),
            'code' => self::ERROR_CODES['INTERNAL_ERROR'],
            'status' => 500,
        ];
    }

    private function handleValidationException(ValidationFailedException $exception): array
    {
        $violations = $exception->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath] = $violation->getMessage();
        }

        return [
            'error' => true,
            'message' => 'Erreur de validation',
            'code' => self::ERROR_CODES['VALIDATION_ERROR'],
            'errors' => $errors,
            'status' => 400,
        ];
    }

    private function logException(\Throwable $exception, $request): void
    {
        $logContext = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
        ];

        // Logger selon la gravité
        if ($exception instanceof NotFoundHttpException) {
            $this->logger->info('API 404 Error', $logContext);
        } elseif ($exception instanceof ValidationFailedException) {
            $this->logger->warning('API Validation Error', $logContext);
        } elseif ($exception instanceof AccessDeniedException) {
            $this->logger->warning('API Access Denied', $logContext);
        } else {
            $this->logger->error('API Exception', $logContext);
        }
    }

    private function isApiRequest($request): bool
    {
        $contentType = $request->headers->get('Content-Type');
        $acceptHeader = $request->headers->get('Accept');
        $pathInfo = $request->getPathInfo();

        // Vérifier si c'est une requête API
        return str_starts_with($pathInfo, '/api')
            || str_starts_with($pathInfo, '/auth')
            || str_contains($contentType ?? '', 'application/json')
            || str_contains($acceptHeader ?? '', 'application/json');
    }
}
