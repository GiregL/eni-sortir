<?php
namespace App\Model;

use App\Entity\Site;
use DateTime;

/**
 * Evenet Filter form DTO
 */
class EventFilterModel
{
    private $site;
    private $event_name;
    private $start_date;
    private $end_date;
    private $is_organizer;
    private $is_member;
    private $is_not_member;
    private $is_passed_event;

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite($site): void
    {
        $this->site = $site;
    }

    public function getEventName(): ?string
    {
        return $this->event_name;
    }

    public function setEventName($event_name): void
    {
        $this->event_name = $event_name;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->start_date;
    }

    public function setStartDate($start_date): void
    {
        $this->start_date = $start_date;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    public function setEndDate($end_date): void
    {
        $this->end_date = $end_date;
    }

    public function getIsOrganizer(): ?bool
    {
        return $this->is_organizer;
    }

    public function setIsOrganizer($is_organizer): void
    {
        $this->is_organizer = $is_organizer;
    }

    public function getIsMember(): ?bool
    {
        return $this->is_member;
    }

    public function setIsMember($is_member): void
    {
        $this->is_member = $is_member;
    }

    public function getIsNotMember(): ?bool
    {
        return $this->is_not_member;
    }

    public function setIsNotMember($is_not_member): void
    {
        $this->is_not_member = $is_not_member;
    }

    public function getIsPassedEvent(): ?bool
    {
        return $this->is_passed_event;
    }

    public function setIsPassedEvent(bool $is_passed_event): void
    {
        $this->is_passed_event = $is_passed_event;
    }
}