<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-auth', name: 'test_auth', methods: ['POST'])]
    public function testAuth(): JsonResponse
    {
        return $this->json([
            'message' => 'Route de test fonctionne',
            'endpoint' => '/test-auth'
        ]);
    }

    #[Route('/api/test-error', name: 'test_error', methods: ['GET'])]
    public function testError(): JsonResponse
    {
        // Cette route va générer une erreur 500 pour tester le subscriber
        throw new \Exception('Ceci est une erreur de test pour vérifier le subscriber');
    }

    #[Route('/api/test-404', name: 'test_404', methods: ['GET'])]
    public function test404(): JsonResponse
    {
        // Cette route va générer une erreur 404
        throw $this->createNotFoundException('Resource non trouvée');
    }
}
