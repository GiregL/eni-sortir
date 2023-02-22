<?php

namespace App\Repository;

use App\Data\EventFilterData;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function add(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function addMemberToEvent(Event $entity, Member $member, bool $flush = false): void {

        $entity->addMember($member);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllAvailableEvents(): array
    {
        $currentDate = new \DateTime();

        return $this->createQueryBuilder('e')
            ->andWhere('e.startDate > :currentDate')
            ->andWhere('e.dateLimitRegister > :currentDate')
//            ->andWhere('e.startDate + e.duration < :currentDate')
            ->setParameter("currentDate", $currentDate)
            ->getQuery()
            ->getResult();
    }

    public function findFilteredEvents(EventFilterData $criteria): array
    {
        $currentDate = new \DateTime();

        $query =  $this->createQueryBuilder('event')
            ->select('site', 'event', 'members', 'organizer')
            ->join('event.site', 'site')
            ->join('event.members', 'members')
            ->join('event.organizer', 'organizer');

        if (!empty($criteria->site)) {
            $query = $query
                ->andWhere('site.id = :site')
                ->setParameter('site', $criteria->site);
        }

        if (!empty($criteria->event_name)) {
            $query = $query
                ->andWhere('event.event_name LIKE :event_name')
                ->setParameter('event_name', "%{$criteria->event_name}%");
        }

        if (!empty($criteria->start_date)) {
            $query = $query
                ->andWhere('event.startDate >= :start_date')
                ->setParameter('start_date', $criteria->start_date);
        }

        if (!empty($criteria->end_date)) {
            $query = $query
                ->andWhere('event.endDate <= :end_date')
                ->setParameter('end_date', $criteria->end_date);
        }

        if (!empty($criteria->is_organizer)) {
            $query = $query
                ->andWhere('organizer.id = :organizer')
                ->setParameter('organizer', $criteria->is_organizer);
        }

        if (!empty($criteria->is_member)) {
            $query = $query
                ->andWhere('members.id = :member')
                ->setParameter('member', $criteria->is_member);
        }

        if (!empty($criteria->is_not_member)) {
            $query = $query
                ->andWhere('members.id = :member')
                ->setParameter('member', $criteria->is_not_member);
        }

        if (empty($criteria->is_passed_event) or !($criteria->is_passed_event)) {
            $query = $query
                ->andWhere('event.startDate > :current_date')
                ->setParameter('current_date', $currentDate);
        }

        return $query->getQuery()->getResult();
    }
}
