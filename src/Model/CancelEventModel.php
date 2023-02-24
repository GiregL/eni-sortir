<?php

namespace App\Model;

class CancelEventModel
{
    private $eventId;
    private $cancelMessage;

    /**
     * @return mixed
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param mixed $eventId
     */
    public function setEventId($eventId): void
    {
        $this->eventId = $eventId;
    }

    /**
     * @return mixed
     */
    public function getCancelMessage()
    {
        return $this->cancelMessage;
    }

    /**
     * @param mixed $cancelMessage
     */
    public function setCancelMessage($cancelMessage): void
    {
        $this->cancelMessage = $cancelMessage;
    }
}