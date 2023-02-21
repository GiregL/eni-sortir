<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Event management services
 */
class EventServices
{
    private $logger;
    private $eventRepository;

    public function __construct(LoggerInterface $logger,
                                EventRepository $eventRepository)
    {
        $this->logger = $logger;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Checks if the given user is the organizer of the given event.
     * @param User $user User
     * @param Event $event Event
     * @return bool True if the user is the organizer of the event.
     */
    public function isUserOrganizerOfEvent(UserInterface $user, Event $event): bool
    {
        $profil = $user->getProfil();
        if (!$profil) {
            $this->logger->warning("Vérification d'un organisateur d'event avec un profil non persisté.");
            return false;
        }

        $profilId = $profil->getId();
        if (!$profilId) {
            $this->logger->warning("Vérification d'un organisateur d'event avec un utilisateur non persisté.");
            return false;
        }

        $eventId = $event->getId();
        if (!$eventId) {
            $this->logger->warning("Vérification d'un organisateur d'event avec un event non persisté.");
            return false;
        }

        return $event->getOrganizer()->getId() === $profilId;
    }

    /**
     * Checks if an event is finished or not.
     * @param Event $event Event to check.
     * @return bool True if finished, else false.
     */
    public function isEventFinished(Event $event): bool
    {
        return $event->getStartDate()->getTimestamp() + $event->getDuration() < (new \DateTime())->getTimestamp();
    }

    public function isEventStarted(Event $event): bool
    {
        return $event->getStartDate()->getTimestamp() < (new \DateTime())->getTimestamp();
    }

    /**
     * Service that cancels an event on the platform.
     * @param Event $event Event to cancel.
     */
    public function cancelEvent(Event $event): bool
    {
        $this->logger->info("Appel du service d'annulation d'événement pour l'ID {$event->getId()}");
        if ($this->isEventStarted($event)) {
            return false;
        }

        $this->eventRepository->remove($event, true);
        return true;
    }

    /**
     * Checks if an event is archived.
     * An event is archived when done since at least one month.
     */
    public function isEventArchived(Event $event): bool
    {
        $this->logger->info("Appel du service de vérification d'événement archivé pour l'ID {$event->getId()}");
        if (!$event->getStartDate()) {
            return false;
        }

        $now = new \DateTime();
        $endDate = $event->getStartDate()->getTimestamp() + $event->getDuration();
        $endedPlusOneMonth = date(strtotime("+1 month", $endDate));

        return $now >= $endedPlusOneMonth;
    }
}