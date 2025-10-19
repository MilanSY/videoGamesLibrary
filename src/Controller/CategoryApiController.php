<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/categories',
        summary: 'Récupérer toutes les catégories',
        security: [['Bearer' => []]],
        tags: ['Categories']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des catégories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string')
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[IsGranted('ROLE_USER')]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        $data = array_map(function(Category $category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        }, $categories);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/categories/{id}',
        summary: 'Récupérer une catégorie par son ID',
        security: [['Bearer' => []]],
        tags: ['Categories']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de la catégorie',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails de la catégorie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 404, description: 'Catégorie non trouvée')]
    #[IsGranted('ROLE_USER')]
    public function show(Category $category): JsonResponse
    {
        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/categories',
        summary: 'Créer une nouvelle catégorie',
        security: [['Bearer' => []]],
        tags: ['Categories']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Action')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Catégorie créée avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string')
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
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Le champ "name" est requis'], 400);
        }

        $category = new Category();
        $category->setName($data['name']);

        // Validation
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->persist($category);
        $em->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Mettre à jour une catégorie',
        security: [['Bearer' => []]],
        tags: ['Categories']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de la catégorie',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'RPG')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Catégorie mise à jour avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Catégorie non trouvée')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Category $category,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        // Validation
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Supprimer une catégorie',
        security: [['Bearer' => []]],
        tags: ['Categories']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de la catégorie',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Catégorie supprimée avec succès'
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Catégorie non trouvée')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();

        return $this->json(null, 204);
    }
}
