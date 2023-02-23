<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\Member;
use App\Entity\User;
use App\Model\EventState;
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
        return $event->getState() === EventState::getFinished();
    }

    /**
     * Checks if a user can subscribe to the event
     */
    public function isEventSubscribtionClosed(Event $event): bool
    {
        return $event->getState() === EventState::getOpen();
    }

    /**
     * Checks if a user is registered on the event (member or organizer)
     */
    public function isUserRegisteredOnEvent(Event $event, Member $member): bool
    {
        return $event->getOrganizer() === $member || $event->getMembers()->contains($member);
    }

    public function isEventStarted(Event $event): bool
    {
        return $event->getState() === EventState::getOngoing();
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

        $event->setState(EventState::getCanceled());
        $this->eventRepository->flush();                // Persist changes to the database.
        return true;
    }

    /**
     * Checks if an event is archived.
     * An event is archived when done since at least one month.
     */
    public function isEventArchived(Event $event): bool
    {
        $this->logger->info("Appel du service de vérification d'événement archivé pour l'ID {$event->getId()}");
        if (!$event->getState()) {
            return false;
        }

        return $event->getState() === EventState::getArchived();
    }

    /**
     * Checks if an event should have the state of Archived.
     * @param Event $event
     * @return bool
     */
    public function shouldBeArchived(Event $event): bool
    {
        $finished = $event->getState() === EventState::getFinished();
        $finishedTimestamp = date_modify($event->getStartDate(), "+{$event->getDuration()} minutes")->getTimestamp();

        $nowTimestamp = (new \DateTime())->getTimestamp();
        $withMonthTimeStamp = strtotime("+1 month", $finishedTimestamp);

        return $finished && ($withMonthTimeStamp <= $nowTimestamp);
    }

    /**
     * Unsubs a member from an event
     */
    public function unsubEvent(Event $event, Member $member): bool
    {
        $now = (new \DateTime())->getTimestamp();

        if ($event->getDateLimitRegister()->getTimestamp() >= $now
            && $event->getMembers()->contains($member)
            && $event->getOrganizer() !== $member
            && !$this->isEventArchived($event)) {
            return $this->eventRepository->removeMemberFromEvent($event, $member, true);
        } else {
            return false;
        }
    }

    /**
     * Publish an event
     */
    public function publishEvent(Event $event, Member $member) {

        $event->setState(EventState::getOpen());
    }

    public function isPublished(Event $event): bool
    {
        return in_array($event->getState(), [
            EventState::getOpen(),
            EventState::getClosed(),
            EventState::getOngoing(),
            EventState::getFinished()
        ]);
    }
}