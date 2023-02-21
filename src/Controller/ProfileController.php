<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MemberRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    private $logger;
    private $memberRepository;

    public function __construct(LoggerInterface $logger, MemberRepository $memberRepository)
    {
        $this->logger = $logger;
        $this->memberRepository = $memberRepository;
    }

    /**
     * @Route("/profile", name="app_profile")
     */
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash("error", "Vous devez être authentifié pour accéder à cette page.");
            return $this->redirectToRoute("app_login");
        }

        if (!($user instanceof User)) {
            $this->addFlash("error", "Une erreur interne est survenue.");
            $this->logger->error("L'utilisateur n'est pas une instance de App\Entity\User.");
            return $this->redirectToRoute("app_main");
        }

        $profile = $this->memberRepository->findMemberProfileForUser($user);

        if (!$profile) {
            $this->addFlash("error", "Une erreur interne est survenue.");
            $this->logger->error("Le profil de l'utilisateur est null.");
            return $this->redirectToRoute("app_main");
        }

        return $this->render('profile/index.html.twig', [
            "email" => $user->getEmail(),
            "username" => $user->getUsername(),
            "firstname" => $user->getProfil()->getFirstname(),
            "lastName" => $user->getProfil()->getName(),
            "phone" => $user->getProfil()->getPhone(),
            "city" => $user->getProfil()->getSite()->getName()
        ]);
    }

    /**
     * @Route("/profile/detail", name="app_profile_detail")
     */
    public function detail(): Response
    {
        return $this->render('profile/detail.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }
}
