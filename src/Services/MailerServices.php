<?php

namespace App\Services;

use App\Entity\Event;
use App\Entity\Member;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Mailer services
 */
class MailerServices
{

    private $logger;
    private $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Sends an email with the given reason to all the members of an event.
     */
    public function sendCancellationMail(Event $event, string $reason): void
    {
        $from = "";
        foreach ($event->getMembers() as $member) {
            $email = (new Email())
                ->from($from)
                ->to($member->getMail())
                ->subject('Annulation d\'événement')
                ->text($reason);

            $this->mailer->send($email);
        }

    }
}