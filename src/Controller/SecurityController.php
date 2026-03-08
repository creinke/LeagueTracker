<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController {

	/**
	 * @param AuthenticationUtils $authenticationUtils
	 *
	 * @return Response
	 */
	#[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig',
            [
                'title' => 'Log in Page',
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	#[Route('/logout', name: 'app_logout')]
    public function logout() {
        throw new \Exception('Will be intercepted before getting here');
    }
}
