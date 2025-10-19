<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\VideoGame;
use App\Repository\CategoryRepository;
use App\Repository\EditorRepository;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/videogames', name: 'api_videogames_')]
class VideoGameApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/videogames',
        summary: 'Récupérer tous les jeux vidéo',
        security: [['Bearer' => []]],
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
                    new OA\Property(property: 'coverImage', type: 'string'),
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
    public function index(VideoGameRepository $videoGameRepository, TagAwareCacheInterface $cache): JsonResponse
    {
        $cacheKey = "videogames_all";

        $data = $cache->get($cacheKey, function (ItemInterface $item) use ($videoGameRepository) {
            $item->tag("videogamesCache");
            
            $videoGames = $videoGameRepository->findAll();
            $result = [];
            
            foreach ($videoGames as $game) {
                $result[] = [
                    'id' => $game->getId(),
                    'title' => $game->getTitle(),
                    'description' => $game->getDescription(),
                    'releaseDate' => $game->getReleaseDate()->format('Y-m-d'),
                    'coverImage' => $game->getCoverImage(),
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
            
            return $result;
        });

        return $this->json($data);
    }

    #[Route('/paginated', name: 'paginated', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/videogames/paginated',
        summary: 'Récupérer les jeux vidéo avec pagination',
        security: [['Bearer' => []]],
        tags: ['Video Games']
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Nombre de résultats par page (défaut: 10)',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Parameter(
        name: 'offset',
        in: 'query',
        description: 'Nombre de résultats à ignorer (défaut: 0)',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 0)
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste paginée des jeux vidéo avec métadonnées',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'total', type: 'integer', description: 'Nombre total de jeux vidéo'),
                new OA\Property(property: 'limit', type: 'integer', description: 'Limite appliquée'),
                new OA\Property(property: 'offset', type: 'integer', description: 'Offset appliqué'),
                new OA\Property(property: 'count', type: 'integer', description: 'Nombre de résultats retournés'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'description', type: 'string'),
                            new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                            new OA\Property(property: 'coverImage', type: 'string'),
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
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    public function paginated(Request $request, VideoGameRepository $videoGameRepository, TagAwareCacheInterface $cache): JsonResponse
    {
        // Récupération des paramètres avec valeurs par défaut
        $limit = max(1, (int) $request->query->get('limit', 10));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $cacheKey = "videogames_paginated_" . $limit . "_" . $offset;

        $result = $cache->get($cacheKey, function (ItemInterface $item) use ($videoGameRepository, $limit, $offset) {
            $item->tag("videogamesCache");
            
            // Récupération du nombre total
            $total = $videoGameRepository->count([]);

            // Calcul de l'offset réel (offset * limit)
            $realOffset = $offset * $limit;

            // Récupération des jeux vidéo paginés
            $videoGames = $videoGameRepository->findBy([], null, $limit, $realOffset);

            $data = [];
            foreach ($videoGames as $game) {
                $data[] = [
                    'id' => $game->getId(),
                    'title' => $game->getTitle(),
                    'description' => $game->getDescription(),
                    'releaseDate' => $game->getReleaseDate()->format('Y-m-d'),
                    'coverImage' => $game->getCoverImage(),
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

            return [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($data),
                'data' => $data,
            ];
        });

        return $this->json($result);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/videogames/{id}',
        summary: 'Récupérer un jeu vidéo par son ID',
        security: [['Bearer' => []]],
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
                new OA\Property(property: 'coverImage', type: 'string'),
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
    public function show(VideoGame $videoGame, TagAwareCacheInterface $cache): JsonResponse
    {
        $cacheKey = "videogame_" . $videoGame->getId();

        $data = $cache->get($cacheKey, function (ItemInterface $item) use ($videoGame) {
            $item->tag("videogamesCache");
            
            return [
                'id' => $videoGame->getId(),
                'title' => $videoGame->getTitle(),
                'description' => $videoGame->getDescription(),
                'releaseDate' => $videoGame->getReleaseDate()->format('Y-m-d'),
                'coverImage' => $videoGame->getCoverImage(),
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
            ];
        });

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/videogames',
        summary: 'Créer un nouveau jeu vidéo avec support FormData pour upload d\'image',
        security: [['Bearer' => []]],
        tags: ['Video Games']
    )]
    #[OA\RequestBody(
        required: true,
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['title', 'editorId', 'categoryIds'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', example: 'Elden Ring'),
                        new OA\Property(property: 'description', type: 'string', example: 'Un action-RPG en monde ouvert'),
                        new OA\Property(property: 'releaseDate', type: 'string', format: 'date', example: '2022-02-25'),
                        new OA\Property(property: 'editorId', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'categoryIds',
                            type: 'array',
                            items: new OA\Items(type: 'integer'),
                            example: [1, 2]
                        )
                    ]
                )
            ),
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['title', 'editorId', 'categoryIds'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                        new OA\Property(property: 'editorId', type: 'integer'),
                        new OA\Property(property: 'categoryIds', type: 'string', description: 'JSON array of category IDs'),
                        new OA\Property(property: 'coverImage', type: 'string', format: 'binary', description: 'Cover image file')
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 201,
        description: 'Jeu vidéo créé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'releaseDate', type: 'string'),
                new OA\Property(property: 'coverImage', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        EditorRepository $editorRepository,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Détection du Content-Type (JSON ou FormData)
        $contentType = $request->headers->get('Content-Type');
        $isFormData = str_contains($contentType, 'multipart/form-data');

        if ($isFormData) {
            // Traitement FormData
            $data = $request->request->all();
            
            // Convertir categoryIds depuis JSON string
            if (isset($data['categoryIds']) && is_string($data['categoryIds'])) {
                $data['categoryIds'] = json_decode($data['categoryIds'], true);
            }
        } else {
            // Traitement JSON classique
            $data = json_decode($request->getContent(), true);
        }

        // Validation des champs requis
        if (!isset($data['title']) || !isset($data['editorId']) || !isset($data['categoryIds'])) {
            return $this->json([
                'error' => 'Les champs "title", "editorId" et "categoryIds" sont requis'
            ], 400);
        }

        // Récupérer l'éditeur
        $editor = $editorRepository->find($data['editorId']);
        if (!$editor) {
            return $this->json(['error' => 'Éditeur non trouvé'], 404);
        }

        $videoGame = new VideoGame();
        $videoGame->setTitle($data['title']);
        $videoGame->setEditor($editor);

        if (isset($data['description'])) {
            $videoGame->setDescription($data['description']);
        }

        if (isset($data['releaseDate'])) {
            try {
                $releaseDate = new \DateTime($data['releaseDate']);
                $videoGame->setReleaseDate($releaseDate);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format de date invalide'], 400);
            }
        }

        // Gestion de l'upload d'image (FormData uniquement)
        if ($isFormData && $request->files->has('coverImage')) {
            $coverImageFile = $request->files->get('coverImage');
            
            try {
                // Générer un nom de fichier unique
                $filename = uniqid() . '.' . $coverImageFile->guessExtension();
                
                // Déplacer le fichier vers le dossier public/uploads/covers
                $coverImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/covers',
                    $filename
                );
                
                // Stocker le chemin relatif dans la BDD
                $videoGame->setCoverImage('/uploads/covers/' . $filename);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()], 500);
            }
        }

        // Ajouter les catégories
        foreach ($data['categoryIds'] as $categoryId) {
            $category = $categoryRepository->find($categoryId);
            if (!$category) {
                return $this->json(['error' => "Catégorie avec l'ID $categoryId non trouvée"], 404);
            }
            $videoGame->addCategory($category);
        }

        // Validation
        $errors = $validator->validate($videoGame);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Invalidation du cache avant la persistance
        $cache->invalidateTags(["videogamesCache"]);

        $em->persist($videoGame);
        $em->flush();

        return $this->json([
            'id' => $videoGame->getId(),
            'title' => $videoGame->getTitle(),
            'description' => $videoGame->getDescription(),
            'releaseDate' => $videoGame->getReleaseDate()?->format('Y-m-d'),
            'coverImage' => $videoGame->getCoverImage(),
            'editor' => [
                'id' => $videoGame->getEditor()->getId(),
                'name' => $videoGame->getEditor()->getName(),
            ],
            'categories' => array_map(function($category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }, $videoGame->getCategories()->toArray()),
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: '/api/videogames/{id}',
        summary: 'Mettre à jour un jeu vidéo sans support FormData',
        security: [['Bearer' => []]],
        tags: ['Video Games']
    )]
    #[OA\Post(
        path: '/api/videogames/{id}',
        summary: 'Mettre à jour un jeu vidéo avec support FormData (utiliser postman pour l\'ajout de fichiers)',
        security: [['Bearer' => []]],
        tags: ['Video Games']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du jeu vidéo',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                        new OA\Property(property: 'editorId', type: 'integer'),
                        new OA\Property(
                            property: 'categoryIds',
                            type: 'array',
                            items: new OA\Items(type: 'integer')
                        )
                    ]
                )
            ),
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'releaseDate', type: 'string', format: 'date'),
                        new OA\Property(property: 'editorId', type: 'integer'),
                        new OA\Property(property: 'categoryIds', type: 'string', description: 'JSON array of category IDs'),
                        new OA\Property(property: 'coverImage', type: 'string', format: 'binary', description: 'Cover image file')
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Jeu vidéo mis à jour avec succès'
    )]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Jeu vidéo non trouvé')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        VideoGame $videoGame,
        Request $request,
        EntityManagerInterface $em,
        EditorRepository $editorRepository,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Détection du Content-Type (JSON ou FormData)
        $contentType = $request->headers->get('Content-Type');
        $isFormData = str_contains($contentType, 'multipart/form-data');

        if ($isFormData) {
            // Traitement FormData
            $data = $request->request->all();
            
            // Convertir categoryIds depuis JSON string
            if (isset($data['categoryIds']) && is_string($data['categoryIds'])) {
                $data['categoryIds'] = json_decode($data['categoryIds'], true);
            }
        } else {
            // Traitement JSON classique
            $data = json_decode($request->getContent(), true);
        }

        if (isset($data['title'])) {
            $videoGame->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $videoGame->setDescription($data['description']);
        }

        if (isset($data['releaseDate'])) {
            try {
                $releaseDate = new \DateTime($data['releaseDate']);
                $videoGame->setReleaseDate($releaseDate);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format de date invalide'], 400);
            }
        }

        if (isset($data['editorId'])) {
            $editor = $editorRepository->find($data['editorId']);
            if (!$editor) {
                return $this->json(['error' => 'Éditeur non trouvé'], 404);
            }
            $videoGame->setEditor($editor);
        }

        // Gestion de l'upload d'image (FormData uniquement)
        if ($isFormData && $request->files->has('coverImage')) {
            $coverImageFile = $request->files->get('coverImage');
            
            try {
                // Supprimer l'ancienne image si elle existe
                if ($videoGame->getCoverImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $videoGame->getCoverImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                // Générer un nom de fichier unique
                $filename = uniqid() . '.' . $coverImageFile->guessExtension();
                
                // Déplacer le fichier vers le dossier public/uploads/covers
                $coverImageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/covers',
                    $filename
                );
                
                // Stocker le chemin relatif dans la BDD
                $videoGame->setCoverImage('/uploads/covers/' . $filename);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()], 500);
            }
        }

        if (isset($data['categoryIds'])) {
            // Supprimer toutes les catégories existantes
            foreach ($videoGame->getCategories() as $category) {
                $videoGame->removeCategory($category);
            }
            
            // Ajouter les nouvelles catégories
            foreach ($data['categoryIds'] as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if (!$category) {
                    return $this->json(['error' => "Catégorie avec l'ID $categoryId non trouvée"], 404);
                }
                $videoGame->addCategory($category);
            }
        }

        // Validation
        $errors = $validator->validate($videoGame);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Invalidation du cache avant le flush
        $cache->invalidateTags(["videogamesCache"]);

        $em->flush();

        return $this->json([
            'id' => $videoGame->getId(),
            'title' => $videoGame->getTitle(),
            'description' => $videoGame->getDescription(),
            'releaseDate' => $videoGame->getReleaseDate()?->format('Y-m-d'),
            'coverImage' => $videoGame->getCoverImage(),
            'editor' => [
                'id' => $videoGame->getEditor()->getId(),
                'name' => $videoGame->getEditor()->getName(),
            ],
            'categories' => array_map(function($category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }, $videoGame->getCategories()->toArray()),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/videogames/{id}',
        summary: 'Supprimer un jeu vidéo',
        security: [['Bearer' => []]],
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
        response: 204,
        description: 'Jeu vidéo supprimé avec succès'
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Jeu vidéo non trouvé')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(VideoGame $videoGame, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        // Invalidation du cache avant la suppression
        $cache->invalidateTags(["videogamesCache"]);
        
        $em->remove($videoGame);
        $em->flush();

        return $this->json(null, 204);
    }
}
