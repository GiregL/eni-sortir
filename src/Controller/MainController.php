<?php

namespace App\Controller;

use App\Data\EventFilterData;
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

        // Getting all current events
        $event_list = $this->eventRepository->findFilteredEvents($eventFilter);

        return $this->render('main/index.html.twig', [
            "eventFilterForm" => $form->createView(),
            "event_list" => $event_list
        ]);
    }
}
