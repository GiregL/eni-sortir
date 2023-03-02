<?php

namespace App\Controller;

use App\Entity\Member;
use App\Entity\User;
use App\Form\AddUserFormType;
use App\Form\BatchAddUsersFormType;
use App\Model\BatchAddUsersModel;
use App\Model\ProfileUpdateModel;
use App\Repository\MemberRepository;
use App\Services\UserServices;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController {

    private $logger;
    private $memberRepository;
    private $userServices;

    public function __construct(LoggerInterface $logger, MemberRepository $memberRepository, UserServices $userServices)
    {
        $this->logger = $logger;
        $this->memberRepository = $memberRepository;
        $this->userServices = $userServices;
    }

    /**
     * @Route("/admin/users", name="app_user_index")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(): Response
    {
        $listeUsers = $this->memberRepository->findAllMembers();

        return $this->render('user/index.html.twig', [
            "listeUsers" => $listeUsers
        ]);
    }

    /**
     * @Route("/admin/users/form", name="app_user_form_add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function formAdd(): Response
    {
        $form = $this->createForm(AddUserFormType::class, new ProfileUpdateModel(), [
            'action' => $this->generateUrl('app_user_add'),
        ]);

        return $this->render('user/add.html.twig', [
            "updateForm" => $form->createView()
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
            $user->setActive(false);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
            $member->setFirstname($profile->getFirstName());
            $member->setName($profile->getLastName());
            $member->setPhone($profile->getPhone());
            $member->setMail($profile->getEmail());
            $member->setAdmin(false);
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
            return $this->redirectToRoute("app_user_index");
        } else {
            $this->addFlash("error", "Les données fournies sont invalides.");
            return $this->redirectToRoute("app_user_form_add");
        }
    }

    /**
     * @Route("/admin/users/{id}/remove", name="app_user_remove", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function removeUser(Member $member, EntityManagerInterface $em) {
        
        $user = $member->getUser();
        $user->setDateRemoved(new DateTime());
        $em->flush();
        $this->addFlash("success", "Le compte a bien été supprimé.");
        return $this->redirectToRoute('app_user_index');
    }

    /**
     * @Route("/admin/users/{id}/disable", name="app_user_disable", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function disableUser(Member $member, EntityManagerInterface $em) {
        
        $user = $member->getUser();
        $user->setActive(true);
        $em->flush();
        $this->addFlash("success", "Le compte a bien été desactivé.");
        return $this->redirectToRoute('app_user_index');
    }

    /**
     * @Route("/admin/users/add-all", name="app_user_addall_get", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function getBatchAddUsersFromCSV(): Response
    {
        $this->logger->info("Appel du service de la page d'import de multiples utilisateurs depuis un fichier CSV.");

        $importFormModel = new BatchAddUsersModel();
        $importForm = $this->createForm(BatchAddUsersFormType::class, $importFormModel);

        return $this->render("user/addall.html.twig", [ "importForm" => $importForm->createView()]);
    }

    /**
     * @Route("/admin/users/add-all", name="app_user_addall_post", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function postBatchAddUsersFromCSV(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info("Appel du service d'import de multiples utilisateurs depuis un fichier CSV.");

        $importFormModel = new BatchAddUsersModel();
        $importForm = $this->createForm(BatchAddUsersFormType::class, $importFormModel);
        $importForm->handleRequest($request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            $formFile = $importForm->get("usersFile")->getData();

            if ($formFile instanceof UploadedFile) {
                if (($handler = fopen($formFile->getRealPath(), "r")) !== false) {

                    $counter = 0;
                    $start = true;
                    while (($userLine = fgetcsv($handler)) !== false) {
                        // Skip first line (headers)
                        if ($start) {
                            $start = false;
                            continue;
                        }

                        try {
                            dump($userLine);
                            $user = $this->userServices->createUserFromCSVLine($counter, $userLine);

                            if (!$user) {
                                throw new \Exception("Echec de l'insersion d'un utilisateur.");
                            }

                            $entityManager->persist($user);
                        } catch (\Exception $e) {
                            $this->logger->warning(
                                sprintf("La création de l'utilisateur de la ligne %d du fichier CSV a échoué\t\n%s\t\n%s",
                                    $counter,
                                    $e->getMessage(),
                                    print_r($userLine)));
                            $counter--;
                        }
                        $counter++;
                    }

                    try {
                        $entityManager->flush();
                    } catch (\Exception $e) {
                        $this->logger->warning("Erreur lors de l'insertion des utilisateurs: " . $e->getMessage());
                    }

                    fclose($handler);
                    $this->logger->info("Import de {$counter} nouveaux utilisateurs réalisé.");
                    $this->addFlash("success", "Import de {$counter} nouveaux utilisateurs réalisé.");
                    // TODO: Rediriger vers une page ayant la liste des utilisateurs de la plateforme.
                    return $this->redirectToRoute("app_main");
                } else {
                    $this->logger->warning("Une erreur interne est survenue, impossible de lire le fichier temporaire d'import d'utilisateurs.");
                    $this->addFlash("error", "Une erreur interne est survenue, l'import des utilisateurs n'a pas pu être fait.");
                    return $this->redirectToRoute("app_main");
                }
            } else {
                $this->logger->warning("Une erreur interne est survenue, le fichier uploadé n'est pas un UploadedFile.");
                $this->addFlash("error", "Une erreur interne est survenue, ce service n'est pas disponible.");
                return $this->redirectToRoute("app_main");
            }

        } else {
            $this->logger->info("Formulaire d'import d'utilisateurs invalide.");

            foreach ($importForm->getErrors() as $error) {
                $this->logger->debug("Form error: " . $error);
            }

            $this->addFlash("error", "Le formulaire envoyé est invalide.");
            return $this->redirectToRoute("app_user_addall_get");
        }
    }
}