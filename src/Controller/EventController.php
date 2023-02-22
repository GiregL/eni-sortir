<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Site;
use App\Entity\Member;
use App\Repository\EventRepository;
use App\Repository\MemberRepository;
use App\Repository\SiteRepository;
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
    private $eventServices;
    private $mailerServices;
    private $eventRepository;
    private $memberRepository;

    public function __construct(LoggerInterface $logger,
                                EventServices $eventServices,
                                MailerServices $mailerServices,
                                EventRepository $eventRepository,
                                MemberRepository $memberRepository)
    {
        $this->logger = $logger;
        $this->eventServices = $eventServices;
        $this->mailerServices = $mailerServices;
        $this->eventRepository = $eventRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * @Route("/events/{id}", name="app_event_detail", requirements={"id"="\d+"})
     */
    public function detailEvents(Event $availableEvent): Response
    {
        $user = $this->getUser();
        
        return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $user,
        ]);
    }

    /**
     * @Route("/events/{id}/cancel", name="app_event_cancel", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function cancelEvent(Event $event, Request $request): Response
    {
        $this->logger->info("Appel du service d'annulation d'événement.");

        // Check if the user is authentified
        $currentUser = $this->getUser();
        if (!$currentUser) {
            $this->logger->warning("Utilisateur non authentifié sur la plateforme.");
            $this->addFlash("error", "Vous devez être authentifié sur l'application pour faire ceci.");
            return $this->redirectToRoute("app_login");
        }

        // CSRF security check
        $csrfToken = $request->request->get("token");
        if (!$this->isCsrfTokenValid('cancel-event-' . $event->getId(), $csrfToken)) {
            $this->logger->warning("Tentative de suppression frauduleuse d'événement. Tokens CSRF invalides.");
            $this->addFlash("error", "Validation de la demande impossible.");
            return $this->redirectToRoute("app_main");
        }

        // Check if the event is archived
        if ($this->eventServices->isEventArchived($event)) {
            $this->logger->info("Tentative de suppression d'un évènement archivé.");
            $this->addFlash("error", "Impossible d'annuler une sortie déjà terminée et archivée.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        // Check if the used is allowed to cancel the event
        if (!$this->eventServices->isUserOrganizerOfEvent($currentUser, $event)) {
            $this->logger->info("Tentative de suppression d'un événement dont l'utilisateur n'est pas organisateur.");
            $this->addFlash("error", "Vous ne pouvez pas supprimer cet événement, vous n'en êtes pas l'organisateur.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        // Cancel the event and send emails if needed
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

    /**
     * @Route("/events/{id}/subscribe", name="app_event_inscription", requirements={"id"="\d+"})
     */
    public function addMemberToEvent(Request $request, Event $availableEvent): Response {

        $member = $this->getUser()->getProfil();
        $this->eventRepository->addMemberToEvent($availableEvent, $member, true);
        $this->memberRepository->addEventToMember($member, $availableEvent, true);

        return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $member,
        ]);
    }
}
