<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ApiUserController extends AbstractController {
    #[Route('/api/user/me', name: 'api_user_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse {
        $user = $this->getUser();
        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ],
            'league' => [
                'id' => $user->getLeague()->getId(),
                'name' => $user->getLeague()->getName(),
            ]
        ]);
    }
}
