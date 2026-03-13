<?php
namespace App\Controller\Api;

use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiPlayerController extends AbstractController {
    #[Route('/api/player/list', name: 'api_player_list', methods: ['GET'])]
    public function list(PlayerRepository $playerRepository): JsonResponse {
        $user = $this->getUser();
        $leagueId = $user->getLeague()->getId();
        $players = $playerRepository->findAllPlayers($leagueId);
        
        $data = [];
        foreach ($players as $p) {
            if ($p instanceof \App\Entity\PlayerDE) {
                $data[] = [
                    'id' => $p->getId(),
                    'firstname' => $p->getFirstname(),
                    'lastname' => $p->getLastname(),
                    'isDefunct' => $p->isDefunct(),
                    'seedHandicapIndex' => $p->getSeedhandicapindex(),
                ];
            }
        }

        return new JsonResponse($data);
    }

    #[Route('/api/player/view/{id}', name: 'api_player_view', methods: ['GET'])]
    public function view(int $id, PlayerRepository $playerRepository): JsonResponse {
        $player = $playerRepository->findById($id);
        if (!$player || $player->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $address = $player->getAddress();
        return new JsonResponse([
            'id' => $player->getId(),
            'firstname' => $player->getFirstname(),
            'lastname' => $player->getLastname(),
            'middlenameOrInitial' => $player->getMiddlenameorinitial(),
            'generation' => $player->getGeneration(),
            'isDefunct' => $player->isDefunct(),
            'seedHandicapIndex' => $player->getSeedhandicapindex(),
            'email' => $player->getPersonalemailaddress(),
            'phone' => $player->getCellphonenumber(),
            'address' => $address ? [
                'line1' => $address->getAddressline1(),
                'line2' => $address->getAddressline2(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalcode(),
                'region' => $address->getRegion() ? $address->getRegion()->getName() : null,
            ] : null,
        ]);
    }
}
