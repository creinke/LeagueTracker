### Phase 1: Foundation Implementation Plan (Detailed Diffs)

This document contains the exact diffs and file structure for Phase 1 (Auth & Foundation) of the React Native add-on.

---

### 1. Symfony API Foundation

#### **Files to Create**

**`src/Security/ApiTokenAuthenticator.php`**
This handles the `Authorization: Bearer <token>` header.
```php
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
        $apiToken = substr($request->headers->get('Authorization'), 7);
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(new UserBadge($apiToken, function($token) {
            $user = $this->userRepository->findOneBy(['apiToken' => $token]);
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('Invalid API token');
            }
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
```

**`src/Controller/Api/ApiSecurityController.php`**
Handles `/api/login`.
```php
<?php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiSecurityController extends AbstractController {
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse {
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
```

**`src/Controller/Api/ApiUserController.php`**
Handles `/api/user/me`.
```php
<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
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
```

#### **Files to Modify**

**`src/Entity/UserDE.php`**
```php
// ... after existing properties
    #[ORM\Column(type: "string", length: 255, nullable: true, unique: true)]
    private ?string $apiToken = null;

    public function getApiToken(): ?string {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self {
        $this->apiToken = $apiToken;
        return $this;
    }
```

**`config/packages/security.yaml`**
```yaml
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\ApiTokenAuthenticator
        main:
            # ... existing config
```

---

### 2. React Native Foundation

Proposed directory structure in `mobile/`:

*   `mobile/src/api/client.ts`: Configures Axios with base URL and `Authorization` interceptor.
*   `mobile/src/context/AuthContext.tsx`: React Context for storing `user`, `token`, and `league`.
*   `mobile/src/navigation/AppNavigator.tsx`: Root navigator switching between Login and Home.
*   `mobile/src/screens/Login/LoginScreen.tsx`: Login form.
*   `mobile/src/screens/Home/HomeScreen.tsx`: Landing page.
