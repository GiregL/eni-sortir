<?php

namespace App\Controller;

use App\Data\EventFilterData;
use App\Entity\User;
use App\Form\EventFilterFormType;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MainController extends AbstractController
{
    private $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }
    
    /**
     * @Route("/", name="app_main")
     */
    public function index(Request $request): Response
    {
        $eventFilter = new EventFilterData();
        $form = $this->createForm(EventFilterFormType::class, $eventFilter);
        $form->handleRequest($request);
        $currentMemberId = -1;
        if($this->getUser() && $this->getUser() instanceof User && $this->getUser()->getProfil()) 
        {
            $currentMemberId = $this->getUser()->getProfil()->getId();
        }

        // Getting all current events
        $event_list = $this->eventRepository->findFilteredEvents($eventFilter, $currentMemberId);

        return $this->render('main/index.html.twig', [
            "eventFilterForm" => $form->createView(),
            "event_list" => $event_list
        ]);
    }
}
