<?php

namespace App\Repository;

use App\Model\EventFilterModel;
use App\Entity\Event;
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

    public function removeMemberFromEvent(Event $event, Member $member, bool $flush = false): bool
    {
        $result = $event->getMembers()->removeElement($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
        return $result;
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

    public function findFilteredEvents(EventFilterModel $criteria, int $member): array
    {
        $currentDate = new \DateTime();

        $query =  $this->createQueryBuilder('event')
            ->select('event')
            ->leftjoin('event.site', 'site')
            ->leftjoin('event.organizer', 'organizer')
            ->leftjoin('event.members', 'members');

        if (!empty($criteria->getSite())) {
            $query = $query
                ->andWhere('site.id = :site')
                ->setParameter('site', $criteria->getSite());
        }

        if (!empty($criteria->getEventName())) {
            $query = $query
                ->andWhere('event.name LIKE :event_name')
                ->setParameter('event_name', "%{$criteria->getEventName()}%");
        }

        if (!empty($criteria->getStartDate())) {
            $query = $query
                ->andWhere('event.startDate >= :start_date')
                ->setParameter('start_date', $criteria->getStartDate());
        }

        if (!empty($criteria->getEndDate())) {
            $query = $query
                ->andWhere("DATE_ADD(event.startDate, event.duration, 'MINUTE') <= :end_date")
                ->setParameter('end_date', $criteria->getEndDate());
        }

        if (!empty($criteria->getIsOrganizer())) {
            $query = $query
                ->andWhere('organizer.id = :organizer')
                ->setParameter('organizer', $member);
        }

        if (!empty($criteria->getIsMember())) {
            $query = $query
                ->andWhere('members.id = :member')
                ->setParameter('member', $member);
        }

        if (!empty($criteria->getIsNotMember())) {
            $query = $query
                ->andWhere('members.id != :member')
                ->setParameter('member', $member);
        }

        if (empty($criteria->getIsPassedEvent()) or !($criteria->getIsPassedEvent())) {
            $query = $query
                ->andWhere('event.startDate > :current_date')
                ->setParameter('current_date', $currentDate);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Flushes the current changes.
     * Do not use directly.
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
