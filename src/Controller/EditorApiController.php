<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/editors', name: 'api_editors_')]
class EditorApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/editors',
        summary: 'Récupérer tous les éditeurs',
        security: [['Bearer' => []]],
        tags: ['Editors']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des éditeurs',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'country', type: 'string')
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[IsGranted('ROLE_USER')]
    public function index(EditorRepository $editorRepository): JsonResponse
    {
        $editors = $editorRepository->findAll();

        $data = array_map(function(Editor $editor) {
            return [
                'id' => $editor->getId(),
                'name' => $editor->getName(),
                'country' => $editor->getCountry(),
            ];
        }, $editors);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/editors/{id}',
        summary: 'Récupérer un éditeur par son ID',
        security: [['Bearer' => []]],
        tags: ['Editors']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'éditeur',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails de l\'éditeur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'country', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 404, description: 'Éditeur non trouvé')]
    #[IsGranted('ROLE_USER')]
    public function show(Editor $editor): JsonResponse
    {
        return $this->json([
            'id' => $editor->getId(),
            'name' => $editor->getName(),
            'country' => $editor->getCountry(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/editors',
        summary: 'Créer un nouvel éditeur',
        security: [['Bearer' => []]],
        tags: ['Editors']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['name', 'country'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nintendo'),
                new OA\Property(property: 'country', type: 'string', example: 'Japon')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Éditeur créé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'country', type: 'string')
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

        if (!isset($data['name']) || !isset($data['country'])) {
            return $this->json([
                'error' => 'Les champs "name" et "country" sont requis'
            ], 400);
        }

        $editor = new Editor();
        $editor->setName($data['name']);
        $editor->setCountry($data['country']);

        // Validation
        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->persist($editor);
        $em->flush();

        return $this->json([
            'id' => $editor->getId(),
            'name' => $editor->getName(),
            'country' => $editor->getCountry(),
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[OA\Put(
        path: '/api/editors/{id}',
        summary: 'Mettre à jour un éditeur',
        security: [['Bearer' => []]],
        tags: ['Editors']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'éditeur',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Ubisoft'),
                new OA\Property(property: 'country', type: 'string', example: 'France')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Éditeur mis à jour avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'country', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Éditeur non trouvé')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Editor $editor,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $editor->setName($data['name']);
        }
        if (isset($data['country'])) {
            $editor->setCountry($data['country']);
        }

        // Validation
        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->flush();

        return $this->json([
            'id' => $editor->getId(),
            'name' => $editor->getName(),
            'country' => $editor->getCountry(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/editors/{id}',
        summary: 'Supprimer un éditeur',
        security: [['Bearer' => []]],
        tags: ['Editors']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'éditeur',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Éditeur supprimé avec succès'
    )]
    #[OA\Response(response: 401, description: 'Non autorisé')]
    #[OA\Response(response: 403, description: 'Accès interdit (rôle admin requis)')]
    #[OA\Response(response: 404, description: 'Éditeur non trouvé')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Editor $editor, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($editor);
        $em->flush();

        return $this->json(null, 204);
    }
}
