<?php
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {
	private EntityManagerInterface $em;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	 * Access denied route
	 * @return Response
	 */
	#[Route('/accessdenied', name: 'accessdenied', methods: ['GET'])]
    public function accessDenied(): Response {
        return $this->render(view: 'security/accessdenied.html.twig',
            parameters: array(
                'title' => 'Security Violation')
            );
    }
    
    /**
     * Home route
     * @return Response
     */
    #[Route('/', name: 'home', methods: 'get')]
    public function home(): Response {
		$this->logger->info('Home page accessed');

        return $this->render(view: 'home/index.html.twig',
            parameters: array (
                'title' => 'League Tracker Home')
            );
    }

    /**
     * Payment route
     * @return Response
     */
    #[Route('/payment', name: 'payment', methods: 'get')]
    public function payment(): Response {
        return $this->render(view: 'payment/payments.html.twig',
            parameters: array (
                'title' => 'League Payment')
            );
    }
    
    /**
     * Help route
     * @return Response
     */
    #[Route('/help', name: 'help', methods: 'get')]
    public function help(): Response {
        return $this->render(view: 'help/help.html.twig',
            parameters: array (
                'title' => 'League Tracker Help')
            );
    }
}