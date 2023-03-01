<?php

namespace App\Controller;

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
        $userRemoved = $userRepository->findByEmailUsernameRemove($lastUsername);
        $userDisabled = $userRepository->findByEmailUsernameDisable($lastUsername);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($userRemoved->getDateRemoved()) {
            $error = $this->addFlash("error", "l'utilisateur a été supprimé par l'administrateur");
        } else if ($error) {
            $error = $this->addFlash("error", "l'email ou le mot de passe sont incorrects");
        }
        if ($userDisabled->getProfil()->isAsset()) {
            $error = $this->addFlash("error", "l'utilisateur a été désactivé par l'administrateur");
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
