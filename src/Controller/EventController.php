<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Event controller.
 * Manages events.
 */
class EventController extends AbstractController
{
    private $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * @Route("/events", name="app_events_available")
     */
    public function availableEvents(): Response
    {
        // Getting all current events
        $availableEvents = $this->eventRepository->findAllAvailableEvents();

        return $this->render('event/index.html.twig', [
            "availableEvents" => $availableEvents
        ]);
    }
}