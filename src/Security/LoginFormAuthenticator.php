<?php

namespace App\Security;

use App\Entity\UserDE;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {
	use TargetPathTrait;

	public function __construct(
		private UserRepository $userRepository, // Injected instead of manual creation
		private UrlGeneratorInterface $urlGenerator
	) {}

	public function authenticate( Request $request ): Passport {
		$username = $request->request->get( 'username' );
		$password = $request->request->get( 'password' );

		return new Passport(
			new UserBadge( $username, function ( $userIdentifier ) {
				// Use injected repository to load user
				$user = $this->userRepository->findOneBy( [ 'username' => $userIdentifier ] );

				if ( ! $user ) {
					throw new UserNotFoundException();
				}

				return $user;
			} ),
			new PasswordCredentials( $password ),
			[
				new CsrfTokenBadge(
					'authenticate',
					$request->request->get( '_csrf_token' )
				),
				( new RememberMeBadge() )->enable(),
			]
		);
	}

	protected function getLoginUrl( Request $request ): string {
		return $this->urlGenerator->generate( 'app_login' );
	}

	public function onAuthenticationFailure( Request $request, AuthenticationException $exception ): Response {
		// Optional: Add custom failure handling, e.g., flash message
		return parent::onAuthenticationFailure( $request, $exception );
	}

	public function onAuthenticationSuccess( Request $request, TokenInterface $token, string $firewallName ): Response {
		if ( $targetPath = $this->getTargetPath( $request->getSession(), $firewallName ) ) {
			return new RedirectResponse( $targetPath );
		}

		return new RedirectResponse( $this->urlGenerator->generate( 'home' ) );
	}

	public function supports( Request $request ): bool {
		return $request->isMethod( 'POST' ) && $this->getLoginUrl( $request ) === $request->getRequestUri();
	}
}