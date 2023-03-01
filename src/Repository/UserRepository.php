<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

    public function findByEmailOrUsername(string $emailUsername)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :emailUsername')
            ->orWhere('u.username = :emailUsername')
            ->andWhere('u.dateRemoved is NULL')
            ->setParameter('emailUsername', $emailUsername)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmailUsernameRemove(string $emailUsername)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :emailUsername')
            ->orWhere('u.username = :emailUsername')
            ->setParameter('emailUsername', $emailUsername)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmailUsernameDisable(string $emailUsername)
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.user', 'm')
            ->where('u.email = :emailUsername')
            ->orWhere('u.username = :emailUsername')
            ->andWhere('m.asset = true')
            ->setParameter('emailUsername', $emailUsername)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllUsers() {

        return $this->createQueryBuilder('u')
            ->andWhere('u.dateRemoved IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
