<?php
namespace App\Controller\Api;

use App\Entity\EventDE;
use App\Repository\EventRepository;
use App\Repository\SeasonRepository;
use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiEventController extends AbstractController {
    #[Route('/api/event/list/{seasonId}', name: 'api_event_list', methods: ['GET'])]
    public function list(int $seasonId, SeasonRepository $seasonRepository): JsonResponse {
        $season = $seasonRepository->findById($seasonId);
        if (!$season || $season->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Season not found'], 404);
        }

        $data = [];
        foreach ($season->getSessions() as $session) {
            $events = [];
            foreach ($session->getEvents() as $event) {
                $events[] = [
                    'id' => $event->getId(),
                    'eventNumber' => $event->getEventnumber(),
                    'startDateTime' => $event->getStartdateandtime()?->format(\DateTime::ATOM),
                    'description' => $event->getDescription(),
                    'format' => $event->getFormatString(),
                ];
            }
            $data[] = [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'events' => $events
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/event/view/{id}', name: 'api_event_view', methods: ['GET'])]
    public function view(int $id, EventRepository $eventRepository): JsonResponse {
        $event = $eventRepository->find($id);
        if (!$event || $event->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $user = $this->getUser();
        $isRegistered = false;
        foreach ($event->getRegistrants() as $registrant) {
            if ($registrant->getFirstname() === $user->getUsername()) {
                $isRegistered = true;
                break;
            }
        }

        return new JsonResponse([
            'id' => $event->getId(),
            'eventNumber' => $event->getEventnumber(),
            'startDateTime' => $event->getStartdateandtime()?->format(\DateTime::ATOM),
            'description' => $event->getDescription(),
            'course' => $event->getCourse()?->getName(),
            'nine' => $event->getNine()?->getName(),
            'format' => $event->getFormatString(),
            'isWithHandicapping' => $event->isWithhandicapping(),
            'isRegistered' => $isRegistered,
        ]);
    }

    #[Route('/api/event/register/{id}', name: 'api_event_register', methods: ['POST'])]
    public function register(int $id, EventRepository $eventRepository, PlayerRepository $playerRepository): JsonResponse {
        $event = $eventRepository->find($id);
        if (!$event || $event->getSession()->getSeason()->getLeague()->getId() !== $this->getUser()->getLeague()->getId()) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $user = $this->getUser();
        $players = $playerRepository->findAll();
        $player = null;
        foreach ($players as $p) {
            if ($p->getFirstname() === $user->getUsername()) {
                $player = $p;
                break;
            }
        }

        if (!$player) {
            return new JsonResponse(['error' => 'No associated player profile found for registration.'], 403);
        }

        if ($event->getRegistrants()->contains($player)) {
            $event->getRegistrants()->removeElement($player);
            $isRegistered = false;
        } else {
            $event->getRegistrants()->add($player);
            $isRegistered = true;
        }

        $eventRepository->saveEvent($event);

        return new JsonResponse([
            'success' => true,
            'isRegistered' => $isRegistered,
            'message' => $isRegistered ? 'Registered successfully.' : 'Unregistered successfully.'
        ]);
    }
}
