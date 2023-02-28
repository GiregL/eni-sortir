<?php

namespace App\Services;

use App\Entity\Member;
use App\Entity\User;
use App\Exceptions\UserServicesException;
use App\Repository\SiteRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Services for user management
 */
class UserServices
{
    private $passwordHasher;
    private $siteRepository;
    private $logger;

    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory,
                                SiteRepository $siteRepository,
                                LoggerInterface $logger)
    {
        $this->passwordHasher = $passwordHasherFactory->getPasswordHasher(User::class);
        $this->siteRepository = $siteRepository;
        $this->logger = $logger;
    }

    /**
     * Creates a user from a CSV line of the given format:
     * pseudo;firstName;lastName;phone;email;password;confirmPassword;city
     *
     * It retrieves the city from its name.
     * @param array $line Line of data
     * @return User|null
     */
    public function createUserFromCSVLine(int $lineNumber, array $line): ?User
    {
        if (count($line) !== 8) {
            $this->logger->warning("Le format du fichier CSV est incorrect à la ligne " . $lineNumber);
            return null;
        }

        // User <-> Profile declaration
        $user = new User();
        $profile = new Member();
        $user->setProfil($profile);
        $profile->setUser($user);

        // Username
        $user->setUsername($line[0]);

        // First name and Last name
        $profile->setFirstname($line[1]);
        $profile->setName($line[2]);

        // Phone number
        $profile->setPhone($line[3]);

        // Email
        $profile->setMail($line[4]);
        $user->setEmail($line[4]);

        // Password validation
        if ($line[5] !== $line[6]) {
            $this->logger->warning("Les mots de passes ne correspondent pas pour l'utilisateur $lineNumber");
            return null;
        }
        $hash = $this->passwordHasher->hash($line[5]);
        $user->setPassword($hash);

        // City recuperation
        $site = $this->siteRepository->findOneBySiteName($line[7]);
        if (!$site) {
            $this->logger->warning("Aucune unique ville n'a été trouvée pour l'utilisateur $lineNumber");
            return null;
        }
        $profile->setSite($site);

        return $user;
    }

}