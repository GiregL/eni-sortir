<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Member;
use App\Form\EventType;
use App\Model\EventState;
use App\Repository\EventRepository;
use App\Repository\MemberRepository;
use App\Services\EventServices;
use App\Services\MailerServices;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(LoggerInterface $logger,
                                EventServices $eventServices,
                                MailerServices $mailerServices,
                                EventRepository $eventRepository)
    {
        $this->logger = $logger;
        $this->eventServices = $eventServices;
        $this->mailerServices = $mailerServices;
        $this->eventRepository = $eventRepository;
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
        
        if(!$member) {
            $this->logger->warning("Utilisateur non authentifié sur la plateforme.");
            $this->addFlash("error", "Vous devez être authentifié sur l'application pour faire ceci.");
            return $this->redirectToRoute("app_login");
        }

        //verifier si l'evenement n'est pas nul
        if(!$availableEvent){
            $this->logger->warning("L'évènement est nul.");
            $this->addFlash("error", "Il n'y pas d'évènement en cours");
            return $this->redirectToRoute("app_main");
        }
        //verifier si l'evenement n'est pas cloturé
        if($availableEvent->getState() == EventState::getClosed() && $availableEvent->getState() != EventState::getOpen()) {
            $this->logger->warning("L'inscription n'est plus possible, l'évènement est cloturé.");
            $this->addFlash("error", "L'inscription n'est plus possible, l'évènement est cloturé.");
            return $this->redirectToRoute("app_main");
        }

        //verifier si il ne participe pas deja a levenement

        $this->eventRepository->addMemberToEvent($availableEvent, $member, true);
        //$this->memberRepository->addEventToMember($member, $availableEvent, false);

        return $this->redirectToRoute('app_event_detail', ['id' => $availableEvent->getId()]);
        /*return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $member,
        ]);*/
    }

    /**
     * @Route("/events/new", name="app_event_new", methods={"GET", "POST"})
     */
    public function newEvent(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_events_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
}
