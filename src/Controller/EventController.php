<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\CancelEventFormType;
use App\Form\EventType;
use App\Model\CancelEventModel;
use App\Model\EventState;
use App\Repository\EventRepository;
use App\Services\EventServices;
use App\Services\MailerServices;
use Doctrine\ORM\EntityManager;
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
                                EventRepository $eventRepository
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
        $memberInEvent = $availableEvent->getMembers();

        return $this->render('event/detail.html.twig', [
            "availableEvent" => $availableEvent,
            "user" => $user,
            "memberInEvent" => $memberInEvent
        ]);
    }

    /**
     * @Route("/events/{id}/cancel", name="app_event_cancel_show", requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function showCancelEvent(Event $event, Request $request): Response
    {
        $this->logger->info("Acc??s ?? la page d'annulation de l'??v??nement {$event->getId()}");

        if ($event->getId() === null) {
            $this->logger->warning("L'??v??nement fourni est invalide.");
            $this->addFlash("error", "L'??v??nement fourni n'existe pas ou plus.");
            return $this->redirectToRoute("app_main");
        }

        if (!$this->getUser()) {
            $this->logger->warning("Utilisateur non authentifi?? sur la plateforme.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? sur la plateforme pour acc??der ?? cette fonctionnalit??.");
            return $this->redirectToRoute("app_login");
        }

        if (!($this->getUser() instanceof User)) {
            $this->logger->warning("L'utilisateur n'est pas une instance de App\Entity\User.");
            $this->addFlash("error", "Une erreur interne est survenue, ce service n'est pas disponible pour le moment.");
            return $this->redirectToRoute("app_main");
        }

        $profile = $this->getUser()->getProfil();

        if (!$profile) {
            $this->logger->warning("L'utilisateur courant n'a pas de profil associ??.");
            $this->addFlash("error", "Une erreur interne est survenue, ce service n'est pas disponible pour le moment.");
            return $this->redirectToRoute("app_main");
        }

        $admin = $this->getUser()->getRoles()[0];

        if (!($this->eventServices->isUserOrganizerOfEvent($this->getUser(), $event) || $admin)) {
            $this->logger->info("Tentative d'annulation de l'??v??nement {$event->getId()} par l'utilisateur non organisateur {$this->getUser()->getUsername()}");
            $this->addFlash("error", "Vous n'avez pas la permission d'annuler un ??v??nement dont vous n'??tes pas l'organisateur.");
            return $this->redirectToRoute("app_main");
        }

        if (EventState::isFinished($event->getState())) {
            $this->logger->info("Annulation d'un ??v??nement d??j?? termin??.");
            $this->addFlash("error", "Vous ne pouvez pas annuler un ??v??nement d??j?? termin??.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        $cancelFormData = new CancelEventModel();
        $cancelFormData->setEventId($event->getId());
        $cancelForm = $this->createForm(CancelEventFormType::class, $cancelFormData);

        return $this->render("event/cancel.html.twig", [
            "cancelForm" => $cancelForm->createView(),
            "eventId" => $event->getId()
        ]);
    }

    /**
     * @Route("/events/{id}/cancel", name="app_event_cancel", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function cancelEvent(Event $event, Request $request): Response
    {
        $this->logger->info("Appel du service d'annulation d'??v??nement.");

        // Check if the user is authentified
        $currentUser = $this->getUser();
        if (!$currentUser) {
            $this->logger->warning("Utilisateur non authentifi?? sur la plateforme.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? sur l'application pour faire ceci.");
            return $this->redirectToRoute("app_login");
        }

        // CSRF security check
        $csrfToken = $request->request->get("token");
        if (!$this->isCsrfTokenValid('cancel-event-' . $event->getId(), $csrfToken)) {
            $this->logger->warning("Tentative de suppression frauduleuse d'??v??nement. Tokens CSRF invalides.");
            $this->addFlash("error", "Validation de la demande impossible.");
            return $this->redirectToRoute("app_main");
        }

        // Check if the event is archived
        if ($this->eventServices->isEventArchived($event)) {
            $this->logger->info("Tentative de suppression d'un ??v??nement archiv??.");
            $this->addFlash("error", "Impossible d'annuler une sortie d??j?? termin??e et archiv??e.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        $admin = $this->getUser()->getRoles()[0];

        // Check if the used is allowed to cancel the event
        if (!($this->eventServices->isUserOrganizerOfEvent($currentUser, $event) || $admin)) {
            $this->logger->info("Tentative de suppression d'un ??v??nement dont l'utilisateur n'est pas organisateur.");
            $this->addFlash("error", "Vous ne pouvez pas annuler cet ??v??nement, vous n'en ??tes pas l'organisateur.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }

        // Cancel the event and send emails if needed
        $removed = $this->eventServices->cancelEvent($event);
        if ($removed) {
            $this->logger->info("L'??v??nement \"{$event->getName()}\" a ??t?? supprim??.");
            $this->addFlash("success", "L'??v??nement a bien ??t?? annul??.");
            $this->mailerServices->sendCancellationMail($event, "L'??v??nement \"{$event->getName()}\" a ??t?? annul??.");
            return $this->redirectToRoute("app_main");
        } else {
            $this->addFlash("error", "Impossible d'annuler l'??v??nement, une erreur interne est survenue.");
            return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
        }
    }

    /**
     * @Route("/events/{id}/subscribe", name="app_event_inscription", requirements={"id"="\d+"})
     */
    public function addMemberToEvent(Request $request, Event $availableEvent): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->logger->warning("L'utilisateur n'est pas authentifi?? sur la plateforme.");
            $this->addFlash("error", "Vous devez vous authentifier pour acc??der ?? cette fonctionnalit??.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->logger->debug("Une erreur interne est survenue: l'utilisateur n'est pas une instance de User.");
            $this->addFlash("error", "Une erreur interne est survenue.");
            return $this->redirectToRoute("app_main");
        }

        $member = $user->getProfil();

        if(!$member) {
            $this->logger->warning("Utilisateur non authentifi?? sur la plateforme.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? sur l'application pour faire ceci.");
            return $this->redirectToRoute("app_login");
        }

        //verifier si l'evenement n'est pas nul
        if(!$availableEvent){
            $this->logger->warning("L'??v??nement est nul.");
            $this->addFlash("error", "Il n'y pas d'??v??nement en cours");
            return $this->redirectToRoute("app_main");
        }

        //verifier si l'evenement n'est pas clotur??
        if($this->eventServices->isEventSubscribtionClosed($availableEvent)) {
            $this->logger->info("L'inscription n'est plus possible, l'??v??nement est clotur??.");
            $this->addFlash("error", "L'inscription n'est plus possible, l'??v??nement est clotur??.");
            return $this->redirectToRoute("app_main");
        }

        //verifier si il ne participe pas deja a levenement
        if ($this->eventServices->isUserRegisteredOnEvent($availableEvent, $member)) {
            $this->logger->info("L'utilisateur {$member->getId()} est d??j?? inscrit ?? l'??v??nement {$availableEvent->getId()}");
            $this->addFlash("error", "Vous ??tes d??j?? inscrit ?? cet ??v??nement.");
            return $this->redirectToRoute("app_event_detail", ["id" => $availableEvent->getId()]);
        }

        $this->eventRepository->addMemberToEvent($availableEvent, $member, true);
        // $this->memberRepository->addEventToMember($member, $availableEvent, false);
        $this->addFlash("success", "Vous vous ??tes bien enregistr?? ?? l'??v??nement");

        return $this->redirectToRoute('app_event_detail', ['id' => $availableEvent->getId()]);
    }

    /**
     * Unsubs the current user from the given event
     * @Route("/events/{id}/unsub", name="app_event_unsub", requirements={"id": "\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function unsubEvent(?Event $event): Response
    {
        if (!$event) {
            $this->logger->warning("Tentative d'unsub sur un ??v??nement qui n'existe pas en base.");
            $this->addFlash("error", "Impossible de se d??sinscrire d'un ??v??nement qui n'existe pas.");
            return $this->redirectToRoute("app_main");
        }

        $user = $this->getUser();
        if (!$user) {
            $this->logger->info("L'utilisateur n'est pas authentifi?? sur la plateforme.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? sur la plateforme pour acc??der ?? cette fonctionnalit??.");
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
            $this->addFlash("success", "Vous avez bien ??t?? retir?? de la liste des participants de l'??v??nement.");
        } else {
            $this->logger->warning("Une erreur interne est survenue lors du retrait de l'utilisateur {$profile->getId()} de la liste des participants de l'??v??nement {$event->getId()}");
            $this->addFlash("error", "Vous n'avez pas pu ??tre retir?? de la liste des participants.");
        }

        return $this->redirectToRoute("app_event_detail", ["id" => $event->getId()]);
    }

    /**
     * @Route("/events/new", name="app_event_new", methods={"GET", "POST"})
     */
    public function newEvent(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->logger->info("Tentative de cr??ation d'un event par un utilisateur non authentifi??.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? pour faire cette action.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->logger->warning("L'utilisateur n'est pas une instance de l'entit?? User.");
            $this->addFlash("error", "Une erreur interne est survenue, ce service n'est pas disponible.");
            return $this->redirectToRoute("app_main");
        }

        $member = $user->getProfil();

        if (!$member) {
            $this->logger->warning("L'utilisateur {$user->getId()} n'a pas de profil associ??.");
            $this->addFlash("error", "Une erreur interne est survenue, veuillez contacter l'administrateur de la plateforme.");
            return $this->redirectToRoute("app_main");
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        $publishedEvent = $form->get('saveAndPublish')->isClicked()
            ? 'event_creating'
            : 'event_opening';
        if($publishedEvent == 'event_opening') {
            $event->setState(EventState::getCreating());
        }
        if($publishedEvent == 'event_creating'){
            $event->setState(EventState::getOpen());    
        }
        $event->setSite($member->getSite());
        $event->setOrganizer($member);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('event/new.html.twig', [
            'event' => $event,
            'form' => $form
        ]);
    }

    /**
     * @Route("/events/{id}/publish", name="app_event_publish", requirements={"id": "\d+"}, methods={"GET", "POST"})
     */
    public function publishEvent(Request $request, Event $availableEvent): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->logger->info("Tentative de cr??ation d'un event par un utilisateur non authentifi??.");
            $this->addFlash("error", "Vous devez ??tre authentifi?? pour faire cette action.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->logger->warning("L'utilisateur n'est pas une instance de l'entit?? User.");
            $this->addFlash("error", "Une erreur interne est survenue, ce service n'est pas disponible.");
            return $this->redirectToRoute("app_main");
        }

        $member = $user->getProfil();

        if (!$member) {
            $this->logger->warning("L'utilisateur {$user->getId()} n'a pas de profil associ??.");
            $this->addFlash("error", "Une erreur interne est survenue, veuillez contacter l'administrateur de la plateforme.");
            return $this->redirectToRoute("app_main");
        }

        $organizer = $this->eventServices->isUserOrganizerOfEvent($user,$availableEvent);

        if(!$organizer) {
            $this->logger->warning("L'utilisateur n'est pas l'organisateur de l'??v??nement.");
            $this->addFlash("error", "Vous ne pouvez publiez un ??v??nement dont vous n'??tes pas l'organisateur.");
            return $this->redirectToRoute("app_main");
        }
        if($availableEvent->getState() == 'OPEN') {
            $this->logger->warning("L'??v??nement est d??j?? publi??e.");
            $this->addFlash("error", "Vous avez d??j?? publi?? l'??v??nement.");
            return $this->redirectToRoute("app_main");
        }

        $this->eventServices->publishEvent($availableEvent);
        return $this->redirectToRoute('app_main');
    }

    /**
     * @Route("/events/{id}/update", name="app_event_update", requirements={"id": "\d+"}, methods={"GET", "POST"})
     */
    public function updateEvent(Request $request,Event $availableEvent, EntityManagerInterface $entityManager) {

        $user = $this->getUser();

        $form = $this->createForm(EventType::class, $availableEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($availableEvent);
            $entityManager->flush();
            return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/update.html.twig', [
            "form" => $form->createView(),
            "availableEvent" => $availableEvent,
            "user" => $user
        ]);
    }

}
