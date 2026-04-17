<?php
namespace App\Controller\Api;

use App\Entity\PlayerDE;
use App\Entity\FullnameDE;
use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

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
            'type' => $player->getType(),
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

    #[Route('/api/player/create', name: 'api_player_create', methods: ['POST'])]
    public function create(Request $request, PlayerRepository $playerRepository, EntityManagerInterface $em): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $league = $this->getUser()->getLeague();

        // Check for duplicate name
        $nameData = [
            'firstName' => $data['firstname'] ?? '',
            'lastName' => $data['lastname'] ?? '',
            'middleNameOrInitial' => $data['middlenameOrInitial'] ?? '',
            'generation' => $data['generation'] ?? ''
        ];
        
        $duplicate = $playerRepository->findPlayerByName($league->getId(), $nameData);
        if (!empty($duplicate)) {
            return new JsonResponse(['error' => 'A player with the same name already exists in this league'], 400);
        }

        $player = new PlayerDE($em);
        $player->setLeague($league);
        $this->mapDataToPlayer($player, $data);

        $playerRepository->savePlayer($player);

        return new JsonResponse(['id' => $player->getId(), 'success' => true]);
    }

    #[Route('/api/player/update/{id}', name: 'api_player_update', methods: ['PUT'])]
    public function update(int $id, Request $request, PlayerRepository $playerRepository): JsonResponse {
        $player = $playerRepository->findById($id);
        if (!$player || $player->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Player not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $this->mapDataToPlayer($player, $data);
        
        if (isset($data['isDefunct'])) {
            $player->setDefunct($data['isDefunct']);
        }

        $playerRepository->savePlayer($player);

        return new JsonResponse(['success' => true]);
    }

    private function mapDataToPlayer(PlayerDE $player, array $data): void {
        if (isset($data['firstname'])) $player->setFirstname($data['firstname']);
        if (isset($data['lastname'])) $player->setLastname($data['lastname']);
        if (isset($data['middlenameOrInitial'])) $player->setMiddlenameorinitial($data['middlenameOrInitial']);
        if (isset($data['generation'])) $player->setGeneration($data['generation']);
        if (isset($data['seedHandicapIndex'])) $player->setSeedhandicapindex((float)$data['seedHandicapIndex']);
        if (isset($data['type'])) $player->setType($data['type']);
        if (isset($data['email'])) $player->setPersonalemailaddress($data['email']);
        if (isset($data['phone'])) $player->setCellphonenumber($data['phone']);

        $fullname = $player->getName() ?? new FullnameDE();
        $fullname->setFirstname($player->getFirstname());
        $fullname->setLastname($player->getLastname());
        $fullname->setMiddlenameorinitial($player->getMiddlenameorinitial());
        $fullname->setGeneration($player->getGeneration());
        $player->setName($fullname);
    }
}
