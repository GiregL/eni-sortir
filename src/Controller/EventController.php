<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Services\EventServices;
use App\Services\MailerServices;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Event controller.
 * Manages events.
 */
class EventController extends AbstractController
{
    private $logger;
    private $eventRepository;
    private $eventServices;
    private $mailerServices;

    public function __construct(EventRepository $eventRepository,
                                LoggerInterface $logger,
                                EventServices $eventServices,
                                MailerServices $mailerServices)
    {
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
        $this->eventServices = $eventServices;
        $this->mailerServices = $mailerServices;
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

    /**
     * @Route("/events/{id}", name="app_event_detail", requirements={"id"="\d+"})
     */
    public function detailEvents(Event $availableEvent): Response
    {
        return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent
        ]);
    }

    /**
     * @Route("/events/{id}/cancel", name="app_event_cancel", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function cancelEvent(Event $event, Request $request): Response
    {
        $this->logger->info("Appel du service d'annulation d'événement.");

        $currentUser = $this->getUser();
        if (!$currentUser) {
            $this->logger->warning("Utilisateur non authentifié sur la plateforme.");
            $this->addFlash("error", "Vous devez être authentifié sur l'application pour faire ceci.");
            return $this->redirectToRoute("app_login");
        }

        $csrfToken = $request->request->get("token");
        if (!$this->isCsrfTokenValid('cancel-event-' . $event->getId(), $csrfToken)) {
            $this->logger->warning("Tentative de suppression frauduleuse d'événement. Tokens CSRF invalides.");
            $this->addFlash("error", "Validation de la demande impossible.");
            return $this->redirectToRoute("app_main");
        }

        if (!$this->eventServices->isUserOrganizerOfEvent($currentUser, $event)) {
            $this->logger->info("Tentative de suppression d'un événement dont l'utilisateur n'est pas organisateur.");
            $this->addFlash("error", "Vous ne pouvez pas supprimer cet événement, vous n'en êtes pas l'organisateur.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        $removed = $this->eventServices->cancelEvent($event);
        if ($removed) {
            $this->logger->info("L'événement \"{$event->getName()}\" a été supprimé.");
            $this->addFlash("success", "L'événement a bien été annulé.");
            $this->mailerServices->sendCancellationMail($event, "L'événement \"{$event->getName()}\" a été annulé.");
            return $this->redirectToRoute("app_main");
        } else {
            $this->addFlash("error", "Impossible d'annuler l'événement, une erreur interne est survenue.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }
    }
}
