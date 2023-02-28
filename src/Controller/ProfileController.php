<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Member;
use App\Entity\Site;
use App\Form\ProfileUpdateFormType;
use App\Model\ProfileUpdateModel;
use App\Repository\MemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
     * @Route("/profile", name="app_profile", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function index(): Response
    {
        $user = $this->getUserAndProfile();

        if (!$user) {
            return $this->redirectToRoute("app_main");
        }

        $formModel = new ProfileUpdateModel();
        $formModel->setPseudo($user->getUsername());
        $formModel->setEmail($user->getEmail());
        $formModel->setFirstName($user->getProfil()->getFirstname());
        $formModel->setLastName($user->getProfil()->getName());
        $formModel->setPhone($user->getProfil()->getPhone());
        $formModel->setPassword("");
        $formModel->setConfirmPassword("");
        $formModel->setCity($user->getProfil()->getSite());
        $formModel->setNameImage($user->getNameImage());

        $form = $this->createForm(ProfileUpdateFormType::class, $formModel);

        return $this->render('profile/index.html.twig', [
            "updateForm" => $form->createView()
        ]);
    }

    /**
     * @Route("/profile", "app_profile_update", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function updateProfile(Request $request,
                                  EntityManagerInterface $entityManager,
                                  UserPasswordHasherInterface $passwordHasher): Response
    {
        $profileUpdate = new ProfileUpdateModel();
        $form = $this->createForm(ProfileUpdateFormType::class, $profileUpdate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Getting current user and profile
            $user = $this->getUserAndProfile();

            if (!$user) {
                $this->redirectToRoute("app_main");
            }
            

            $image = $form->get('nameImage')->getData();
            
            /**
             * @var UploadedFile $image
             */
            if($image) {
                $newFileName = $user->getNameImage() . '-' . uniqid().'.' . $image->guessExtension();
                $image->move($this->getParameter('images_directory'),$newFileName);
                $user->setNameImage($newFileName);
            }

            // Updating values
            if ($profileUpdate->getEmail()) {
                $user->setEmail($profileUpdate->getEmail());
                $user->getProfil()->setMail($profileUpdate->getEmail());
            }
            if ($profileUpdate->getPseudo()) { $user->setUsername($profileUpdate->getPseudo()); }
            if ($profileUpdate->getFirstName()) { $user->getProfil()->setFirstname($profileUpdate->getFirstName()); }
            if ($profileUpdate->getLastName()) { $user->getProfil()->setName($profileUpdate->getLastName()); }
            if ($profileUpdate->getPhone()) { $user->getProfil()->setPhone($profileUpdate->getPhone()); }
            if ($profileUpdate->getCity()) { $user->getProfil()->setSite($profileUpdate->getCity()); }
            if ($profileUpdate->getNameImage()) { $user->setNameImage($newFileName); } 

            // Check if a new password should be set (both fields should not be empty or blank)
            if ($profileUpdate->getPassword() !== null && trim($profileUpdate->getPassword()) !== ""
                && $profileUpdate->getConfirmPassword() !== null && trim($profileUpdate->getConfirmPassword()) !== "") {

                if ($profileUpdate->getPassword() === $profileUpdate->getConfirmPassword()) {
                    $newPassword = $passwordHasher->hashPassword($user, $profileUpdate->getPassword());
                    $user->setPassword($newPassword);

                    $this->addFlash("success", "Votre mot de passe a bien été modifié.");
                } else {
                    $this->addFlash("error", "Le mot de passe et sa confirmation ne correspondent pas.");
                }
            }

            // Persist datas
            $entityManager->persist($user);
            $entityManager->flush();

            // Show profile page
            $this->addFlash("success", "Profil mis à jour.");
            return $this->index();
        } else {
            $this->addFlash("error", "Les données fournies sont invalides.");
            return $this->index();
        }
    }

    /**
     * Get the current user with his profile fulfilled.
     * @return User|null
     */
    private function getUserAndProfile() : ?User
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash("error", "Vous devez être authentifié pour accéder à cette page.");
            return null;
        }

        if (!($user instanceof User)) {
            $this->addFlash("error", "Une erreur interne est survenue.");
            $this->logger->error("L'utilisateur n'est pas une instance de App\Entity\User.");
            return null;
        }

        $profile = $this->memberRepository->findMemberProfileForUser($user);

        if (!$profile) {
            $this->addFlash("error", "Une erreur interne est survenue.");
            $this->logger->error("Le profil de l'utilisateur est null.");
            return null;
        }

        return $user;
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

    /**
     * @Route("/profile/detail/{id}", name="app_profile_detail_id", requirements={"id"="\d+"})
     */
    public function detailOrganizer(Member $profilOrganizer, Site $site): Response
    {
        $user = $this->getUserAndProfile();

        if (!$user) {
            $this->addFlash("error", "Vous devez être authentifié pour accéder à cette page.");
            return $this->redirectToRoute("app_login");
        }

        return $this->render('profile/detail.html.twig', [
            "profilOrganizer" => $profilOrganizer,
            "user" => $user,
            "site" => $site
        ]);
    }
}
