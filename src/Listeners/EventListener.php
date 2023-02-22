<?php

namespace App\Listeners;

use App\Entity\Event;
use App\Model\EventState;
use App\Repository\EventRepository;
use App\Services\EventServices;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Event entity listener
 * @AsEntityListener(event= Events::postLoad, method="postLoad", entity=Event::class)
 */
class EventListener
{
    private $eventServices;
    private $eventRepository;

    public function __construct(EventServices $eventServices,
                                EventRepository $eventRepository)
    {
        $this->eventServices = $eventServices;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Event listener after loading it into the entity manager.
     * It updates the event state.
     * @param Event $event Event selected.
     * @return void
     */
    public function postLoad(Event $event, LifecycleEventArgs $_eventArgs): void
    {
        if ($this->eventServices->shouldBeArchived($event)) {
            $event->setState(EventState::getArchived());
            $this->eventRepository->flush();
        }
    }

}