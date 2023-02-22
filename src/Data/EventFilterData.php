<?php
namespace App\Data;

use App\Entity\Site;
use DateTime;

class EventFilterData
{

    /**
     * @var Site
     */
    public $site;

    /**
     * @var string
     */
    public $event_name;

    /**
     * @var DateTime
     */
    public $start_date;

    /**
     * @var DateTime
     */
    public $end_date;

    /**
     * @var boolean
     */
    public $is_organizer;

    /**
     * @var boolean
     */
    public $is_member;

    /**
     * @var boolean
     */
    public $is_not_member;

    /**
     * @var boolean
     */
    public $is_passed_event;

}