<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MobileController extends AbstractController {
    #[Route('/mobile', name: 'app_mobile')]
    public function index(): Response {
        return $this->render('mobile/index.html.twig', [
            'title' => 'League Tracker Mobile',
        ]);
    }
}
