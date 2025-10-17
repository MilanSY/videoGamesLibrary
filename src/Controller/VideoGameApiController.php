<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/videogames', name: 'api_videogames_')]
class VideoGameApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/videogames',
        summary: 'Récupérer tous les jeux vidéo',
        tags: ['Video Games']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des jeux vidéo',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                    new OA\Property(
                        property: 'editor',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'country', type: 'string')
                        ]
                    ),
                    new OA\Property(
                        property: 'categories',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string')
                            ]
                        )
                    )
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    public function index(VideoGameRepository $videoGameRepository): JsonResponse
    {
        $videoGames = $videoGameRepository->findAll();

        $data = [];
        foreach ($videoGames as $game) {
            $data[] = [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'releaseDate' => $game->getReleaseDate()->format('Y-m-d'),
                'editor' => [
                    'id' => $game->getEditor()->getId(),
                    'name' => $game->getEditor()->getName(),
                    'country' => $game->getEditor()->getCountry(),
                ],
                'categories' => array_map(function($category) {
                    return [
                        'id' => $category->getId(),
                        'name' => $category->getName(),
                    ];
                }, $game->getCategories()->toArray()),
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/videogames/{id}',
        summary: 'Récupérer un jeu vidéo par son ID',
        tags: ['Video Games']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du jeu vidéo',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails du jeu vidéo',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                new OA\Property(
                    property: 'editor',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'country', type: 'string')
                    ]
                ),
                new OA\Property(
                    property: 'categories',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 404, description: 'Jeu vidéo non trouvé')]
    public function show(VideoGame $videoGame): JsonResponse
    {

        return $this->json([
            'id' => $videoGame->getId(),
            'title' => $videoGame->getTitle(),
            'description' => $videoGame->getDescription(),
            'releaseDate' => $videoGame->getReleaseDate()->format('Y-m-d'),
            'editor' => [
                'id' => $videoGame->getEditor()->getId(),
                'name' => $videoGame->getEditor()->getName(),
                'country' => $videoGame->getEditor()->getCountry(),
            ],
            'categories' => array_map(function($category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }, $videoGame->getCategories()->toArray()),
        ]);
    }

    #[Route('/admin/test', name: 'admin_test', methods: ['GET'])]
    #[OA\Get(
        path: '/api/videogames/admin/test',
        summary: 'Endpoint de test pour les administrateurs',
        tags: ['Admin']
    )]
    #[OA\Response(
        response: 200,
        description: 'Message de bienvenue pour l\'administrateur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'user', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    public function adminTest(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Authentication required'], 401);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Admin access required'], 403);
        }

        return $this->json([
            'message' => 'Hello Admin! You have access to this admin endpoint.',
            'user' => $user->getUserIdentifier(),
        ]);
    }
}
