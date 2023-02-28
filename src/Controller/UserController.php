<?php

namespace App\Controller;

use App\Entity\Member;
use App\Entity\User;
use App\Form\AddUserFormType;
use App\Form\ProfileUpdateFormType;
use App\Model\ProfileUpdateModel;
use App\Model\RegistrationModel;
use App\Repository\MemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController {

    private $logger;
    private $memberRepository;

    public function __construct(LoggerInterface $logger, MemberRepository $memberRepository)
    {
        $this->logger = $logger;
        $this->memberRepository = $memberRepository;
    }

    /**
     * @Route("/admin/users/index", name="app_user_index")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(): Response
    {
        $listeUsers = $this->memberRepository->findAll();

        return $this->render('user/index.html.twig', [
            "listeUsers" => $listeUsers
        ]);
    }

    /**
     * @Route("/admin/users/add", name="app_user_add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request,
                        EntityManagerInterface $entityManager,
                        UserPasswordHasherInterface $userPasswordHasher,
                        UserPasswordHasherInterface $passwordHasher): Response
    {

        $profile = new ProfileUpdateModel();
        $member = new Member();
        $user = new User();
        $form = $this->createForm(AddUserFormType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $member->setUser($user);
            
            $user->setUsername($profile->getPseudo());
            $user->setEmail($profile->getEmail());
            $user->setRoles(["ROLE_USER"]);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
            $member->setFirstname($profile->getFirstName());
            $member->setName($profile->getLastName());
            $member->setPhone($profile->getPhone());
            $member->setAdmin(false);
            $member->setAsset(false);
            $profile->getConfirmPassword();
            $member->setSite($profile->getCity());
            $user->setProfil($member);

            // Check if a new password should be set (both fields should not be empty or blank)
            if ($profile->getPassword() !== null && trim($profile->getPassword()) !== ""
                && $profile->getConfirmPassword() !== null && trim($profile->getConfirmPassword()) !== "") {

                if ($profile->getPassword() === $profile->getConfirmPassword()) {
                    $newPassword = $passwordHasher->hashPassword($user,$profile->getPassword());
                    $user->setPassword($newPassword);
                } else {
                    $this->addFlash("error", "Le mot de passe et sa confirmation ne correspondent pas.");
                }
            }

            // Persist datas
            $entityManager->persist($user);
            $entityManager->flush();

            // Show profile page
            $this->addFlash("success", "L'utilisateur et son profil ont bien été crée.");

            return $this->render('user/add.html.twig', [
                "updateForm" => $form->createView()
            ]);
        } else {
            $this->addFlash("error", "Les données fournies sont invalides.");
            return $this->render('user/add.html.twig', [
            "updateForm" => $form->createView()
        ]);
        }

        return $this->redirectToRoute("app_main");
    }

}