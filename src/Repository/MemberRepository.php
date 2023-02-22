<?php

namespace App\Repository;

use App\Entity\Member;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Member>
 *
 * @method Member|null find($id, $lockMode = null, $lockVersion = null)
 * @method Member|null findOneBy(array $criteria, array $orderBy = null)
 * @method Member[]    findAll()
 * @method Member[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Member::class);
        $this->logger = $logger;
    }

    public function add(Member $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Member $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns the profile of a given user.
     * Initializes User's profile property.
     * @param User $user User to get the profile of.
     * @return Member|null Member profile or null if it does not exists.
     */
    public function findMemberProfileForUser(User $user): ?Member
    {
        try {
            $profile = $this->createQueryBuilder('m')
                ->leftJoin("m.user", "u")
                ->andWhere('u.id = :userId')
                ->select('m')
                ->setParameter("userId", $user->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if (!$profile) {
                $this->logger->warning("Impossible de récupérer le profil utilisateur : retour null.");
                return null;
            }

            $user->setProfil($profile);

            return $profile;
        } catch (NonUniqueResultException $e) {
            $this->logger->warning("Erreur lors de la récupération du profil de l'utilisateur: " . $e->getMessage());
            return null;
        }
    }

    public function addEventToMember(Member $entity, Event $event, bool $flush = false): void {

        $entity->addOrganizedEvent($event);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Member[] Returns an array of Member objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Member
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
