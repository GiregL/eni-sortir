<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Member;
use App\Entity\User;
use App\Form\EventType;
use App\Model\EventState;
use App\Repository\EventRepository;
use App\Repository\MemberRepository;
use App\Services\EventServices;
use App\Services\MailerServices;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
                                EventRepository $eventRepository,
                                )
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
        $event_list = $this->eventRepository->findAllAvailableEvents();

        return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $user,
            "event_list" => $event_list
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
    public function addMemberToEvent(Request $request, Event $availableEvent,): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->logger->warning("L'utilisateur n'est pas authentifié sur la plateforme.");
            $this->addFlash("error", "Vous devez vous authentifier pour accéder à cette fonctionnalité.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->logger->debug("Une erreur interne est survenue: l'utilisateur n'est pas une instance de User.");
            $this->addFlash("error", "Une erreur interne est survenue.");
            return $this->redirectToRoute("app_main");
        }

        $member = $user->getProfil();

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
        if($this->eventServices->isEventSubscribtionClosed($availableEvent)) {
            $this->logger->info("L'inscription n'est plus possible, l'évènement est cloturé.");
            $this->addFlash("error", "L'inscription n'est plus possible, l'évènement est cloturé.");
            return $this->redirectToRoute("app_main");
        }

        //verifier si il ne participe pas deja a levenement
        if ($this->eventServices->isUserRegisteredOnEvent($availableEvent, $member)) {
            $this->logger->info("L'utilisateur {$member->getId()} est déjà inscrit à l'événement {$availableEvent->getId()}");
            $this->addFlash("error", "Vous êtes déjà inscrit à cet évènement.");
            return $this->redirectToRoute("app_event_detail", ["id" => $availableEvent->getId()]);
        }

        $this->eventRepository->addMemberToEvent($availableEvent, $member, true);
        // $this->memberRepository->addEventToMember($member, $availableEvent, false);
        $this->addFlash("success", "Vous vous êtes bien enregistré à l'évènement");

        return $this->redirectToRoute('app_event_detail', ['id' => $availableEvent->getId()]);
        /*return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $member,
        ]);*/
    }

    /**
     * Unsubs the current user from the given event
     * @Route("/events/{id}/unsub", name="app_event_unsub", requirements={"id": "\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function unsubEvent(?Event $event): Response
    {
        if (!$event) {
            $this->logger->warning("Tentative d'unsub sur un évènement qui n'existe pas en base.");
            $this->addFlash("error", "Impossible de se désinscrire d'un évènement qui n'existe pas.");
            return $this->redirectToRoute("app_main");
        }

        $user = $this->getUser();
        if (!$user) {
            $this->logger->info("L'utilisateur n'est pas authentifié sur la plateforme.");
            $this->addFlash("error", "Vous devez être authentifié sur la plateforme pour accéder à cette fonctionnalité.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->logger->warning("Une erreur interne est survenue, l'utilisateur n'est pas une instance de User.");
            $this->addFlash("error", "Une erreur interne est survenue.");
            return $this->redirectToRoute("app_main");
        }

        $profile = $user->getProfil();

        if (!$profile) {
            $this->logger->warning("Le profil de l'utilisateur n'existe pas.");
            $this->addFlash("error", "Le profil de l'utilisateur est introuvable.");
            return $this->redirectToRoute("app_main");
        }

        $result = $this->eventServices->unsubEvent($event, $profile);
        if ($result) {
            $this->addFlash("success", "Vous avez bien été retiré de la liste des participants de l'évènement.");
        } else {
            $this->logger->warning("Une erreur interne est survenue lors du retrait de l'utilisateur {$profile->getId()} de la liste des participants de l'évènement {$event->getId()}");
            $this->addFlash("error", "Vous n'avez pas pu être retiré de la liste des participants.");
        }

        return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
    }

    /**
     * @Route("/events/new", name="app_event_new", methods={"GET", "POST"})
     */
    public function newEvent(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $member = $this->getUser()->getProfil();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        $event->setState(EventState::getCreating());
        $event->setSite($member->getSite());
        $event->setOrganizer($member);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
}
