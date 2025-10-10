<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/videogames', name: 'api_videogames_')]
class VideoGameApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_ADMIN')]
    public function adminTest(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'message' => 'Hello Admin! You have access to this admin endpoint.',
            'user' => $user->getUserIdentifier(),
        ]);
    }
}
