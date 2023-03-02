<?php

namespace App\Controller;

use App\Repository\MemberRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_main');
        }

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $user = $userRepository->findByEmailUsername($lastUsername);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error && $user) {
            if ($user->getDateRemoved()) {
                $error = $this->addFlash("error", "Votre compte a été supprimé par l'administrateur ");
            } else if ($user->isActive()) {
                $error = $this->addFlash("error", "Votre compte a été désactivé par l'administrateur ");
            }
        } else if  ($error) {
            $error = $this->addFlash("error", "l'email ou le mot de passe sont incorrects");
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error, "user"=> $user]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
