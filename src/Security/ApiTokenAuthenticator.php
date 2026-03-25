<?php
namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiTokenAuthenticator extends AbstractAuthenticator {
    public function __construct(private UserRepository $userRepository) {}

    public function supports(Request $request): ?bool {
        return $request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport {
        $authorizationHeader = $request->headers->get('Authorization');
        error_log('API Auth header: ' . $authorizationHeader);
        $apiToken = substr($authorizationHeader, 7);
        if (false === $apiToken || '' === $apiToken) {
            error_log('API Auth: No token provided');
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(new UserBadge($apiToken, function($token) {
            $user = $this->userRepository->findOneBy(['apiToken' => $token]);
            if (!$user) {
                error_log('API Auth: Invalid token ' . substr($token, 0, 10) . '...');
                throw new CustomUserMessageAuthenticationException('Invalid API token');
            }
            error_log('API Auth: Success for user ' . $user->getUsername());
            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response {
        return new JsonResponse(['message' => strtr($exception->getMessageKey(), $exception->getMessageData())], Response::HTTP_UNAUTHORIZED);
    }
}
