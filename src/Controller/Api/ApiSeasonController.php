<?php
namespace App\Controller\Api;

use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiSeasonController extends AbstractController {
    #[Route('/api/season/list', name: 'api_season_list', methods: ['GET'])]
    public function list(SeasonRepository $seasonRepository): JsonResponse {
        $leagueId = $this->getUser()->getLeague()->getId();
        $seasons = $seasonRepository->findSeasonsByLeagueId($leagueId);

        $data = array_map(fn($s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'startDate' => $s->getStartdate() ? $s->getStartdate()->format('Y-m-d') : null,
            'endDate' => $s->getEnddate() ? $s->getEnddate()->format('Y-m-d') : null,
        ], $seasons);

        return new JsonResponse($data);
    }

    #[Route('/api/season/view/{id}', name: 'api_season_view', methods: ['GET'])]
    public function view(int $id, SeasonRepository $seasonRepository): JsonResponse {
        $season = $seasonRepository->findById($id);
        if (!$season || $season->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Season not found'], 404);
        }

        $sessions = array_map(fn($sess) => [
            'id' => $sess->getId(),
            'name' => $sess->getName(),
            'startDate' => $sess->getStartdate() ? $sess->getStartdate()->format('Y-m-d') : null,
        ], $season->getSessions()->toArray());

        return new JsonResponse([
            'id' => $season->getId(),
            'name' => $season->getName(),
            'startDate' => $season->getStartdate() ? $season->getStartdate()->format('Y-m-d') : null,
            'endDate' => $season->getEnddate() ? $season->getEnddate()->format('Y-m-d') : null,
            'sessions' => $sessions,
        ]);
    }
}
