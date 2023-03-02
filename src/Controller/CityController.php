<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Form\SearchCityType;
use App\Model\SearchCityFilterModel;
use App\Repository\CityRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/city")
 */
class CityController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="app_city_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(CityRepository $cityRepository): Response
    {
        $formModel = new SearchCityFilterModel();
        $form = $this->createForm(SearchCityType::class, $formModel);

        return $this->render('city/index.html.twig', [
            'cities' => $cityRepository->findAll(),
            'filter' => $form->createView()
        ]);
    }

    /**
     * @Route("/", name="app_city_index_search", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function searchCity(Request $request, CityRepository $cityRepository): Response
    {
        $this->logger->info("Recherche de ville.");
        $formModel = new SearchCityFilterModel();
        $form = $this->createForm(SearchCityType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cityName = $formModel->getCityName();

            $cities = $cityRepository->findByNameLike($cityName);

            return $this->render("city/index.html.twig", [
                "cities" => $cities,
                "filter" =>$form->createView()
            ]);
        } else {
            $this->addFlash("error", "Une erreur est survenue.");
            return $this->redirectToRoute("app_city_index");
        }
    }

    /**
     * @Route("/new", name="app_city_new", methods={"GET", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(Request $request, CityRepository $cityRepository): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cityRepository->add($city, true);

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_city_show", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function show(City $city): Response
    {
        return $this->render('city/show.html.twig', [
            'city' => $city,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_city_edit", methods={"GET", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Request $request, City $city, CityRepository $cityRepository): Response
    {
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cityRepository->add($city, true);

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('city/edit.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_city_delete", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, City $city, CityRepository $cityRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$city->getId(), $request->request->get('_token'))) {
            $cityRepository->remove($city, true);
        }

        return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
    }
}
