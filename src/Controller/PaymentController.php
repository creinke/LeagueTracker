<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController {
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger) {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Payment route
     * @return Response
     */
    #[Route('/payments', name: 'payments', methods: 'get')]
    public function payments(): Response
    {
        $payments = [
            [
                'team' => '1A',
                'lastName' => 'Grywacheski',
                'firstName' => 'Scott',
                'owes' => 550.00,
                'carryOverPaid' => 43.20,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 506.80,
            ],
            [
                'team' => '1A',
                'lastName' => 'Simonte',
                'firstName' => 'Phill',
                'owes' => 550.00,
                'carryOverPaid' => 43.20,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 506.80,
            ],
            [
                'team' => '2A',
                'lastName' => 'Alex',
                'firstName' => 'Trimpe',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
            [
                'team' => '2B',
                'lastName' => 'Robert',
                'firstName' => 'Wesson',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
            [
                'team' => '3A',
                'lastName' => 'Mianowski',
                'firstName' => 'Peter',
                'owes' => 550.00,
                'carryOverPaid' => 72.00,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 478.00,
            ],
            [
                'team' => '3B',
                'lastName' => 'Wagner',
                'firstName' => 'Robert',
                'owes' => 550.00,
                'carryOverPaid' => 72.00,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 478.00,
            ],
            [
                'team' => '4A',
                'lastName' => 'Kuziel',
                'firstName' => 'Mike',
                'owes' => 550.00,
                'carryOverPaid' => 124.80,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 425.20,
            ],
            [
                'team' => '4B',
                'lastName' => 'Woods',
                'firstName' => 'Tom',
                'owes' => 550.00,
                'carryOverPaid' => 124.80,
                'carryOverDate' => '23-Mar-26',
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 425.20,
            ],
            [
                'team' => '5A',
                'lastName' => 'Coutts',
                'firstName' => 'Jon',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
            [
                'team' => '5B',
                'lastName' => 'Coutts',
                'firstName' => 'James',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
            [
                'team' => '6A',
                'lastName' => 'Harworth',
                'firstName' => 'Paul',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
            [
                'team' => '6B',
                'lastName' => 'Winter',
                'firstName' => 'Nathan',
                'owes' => 550.00,
                'carryOverPaid' => null,
                'carryOverDate' => null,
                'paymentPaid' => null,
                'paymentDate' => null,
                'balance' => 550.00,
            ],
        ];

        return $this->render('payment/payments.html.twig', [
            'title' => 'Payments',
            'payments' => $payments,
        ]);
    }
}