<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiDocController extends AbstractController
{
    #[Route('/api/doc', name: 'api_doc_ui', methods: ['GET'])]
    public function ui(): Response
    {
        return $this->render('api_doc/swagger_ui.html.twig');
    }
}
