<?php

namespace App\Controller;

use App\Model\EventFilterModel;
use App\Entity\User;
use App\Form\EventFilterFormType;
use App\Repository\EventRepository;
use App\Services\EventServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\TwigFunction;

class MainController extends AbstractController
{
    private $eventRepository;
    private $eventServices;

    public function __construct(EventRepository $eventRepository, EventServices $eventServices)
    {
        $this->eventRepository = $eventRepository;
        $this->eventServices = $eventServices;
    }
    
    /**
     * @Route("/", name="app_main")
     */
    public function index(Request $request, Environment $twig): Response
    {
        $eventFilter = new EventFilterModel();
        $form = $this->createForm(EventFilterFormType::class, $eventFilter);
        $form->handleRequest($request);
        
        $currentMemberId = -1;

        if($this->getUser() && $this->getUser() instanceof User && $this->getUser()->getProfil()) 
        {
            $currentMemberId = $this->getUser()->getProfil()->getId();
        }

        // Getting all current events
        $event_list = $this->eventRepository->findFilteredEvents($eventFilter, $currentMemberId);
        $twig->addFunction(new TwigFunction("isUserRegisteredOnEvent", function($event, $member) {
            return $this->eventServices->isUserRegisteredOnEvent($event, $member);
        }));
        $twig->addFunction(new TwigFunction("isUserOrganizerOfEvent", function ($user, $event) {
            return $this->eventServices->isUserOrganizerOfEvent($user, $event);
        }));
        $twig->addFunction(new TwigFunction("isPublish", function ($event) {
            return $this->eventServices->isPublished($event);
        }));


        return new Response($twig->render('main/index.html.twig', [
            "eventFilterForm" => $form->createView(),
            "event_list" => $event_list,
        ]));
    }

}
