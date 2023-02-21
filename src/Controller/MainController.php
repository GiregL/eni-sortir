<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(): Response
    {
        $availableEvents = $this->eventRepository->findAllAvailableEvents();

        return $this->render('main/index.html.twig', [
            "availableEvents" => $availableEvents,
        ]);
    }
}
