<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Member;
use App\Entity\Site;
use App\Form\RegistrationFormType;
use App\Model\RegistrationModel;
use App\Security\AuthControllerAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             UserAuthenticatorInterface $userAuthenticator,
                             AuthControllerAuthenticator $authenticator,
                             EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $registrationModel = new RegistrationModel();
        $form = $this->createForm(RegistrationFormType::class, $registrationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setEmail($registrationModel->getEmail());
            $user->setUsername($registrationModel->getUsername());
            $user->setActive(false);

            $profil = new Member();
            $profil->setUser($user);
            $profil->setSite($registrationModel->getCity());
            $profil->setMail($registrationModel->getEmail());
            $profil->setAdmin(false);

            $user->setProfil($profil);

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
