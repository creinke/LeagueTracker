<?php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiSecurityController extends AbstractController {
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse {
        error_log('API Login attempt: ' . $request->getContent());
        $data = json_decode($request->getContent(), true);
        $user = $userRepository->findOneBy(['username' => $data['username'] ?? '']);

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'] ?? '')) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $token = bin2hex(random_bytes(32));
        $user->setApiToken($token);
        $userRepository->saveUser($user);

        return new JsonResponse([
            'apiToken' => $token,
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
