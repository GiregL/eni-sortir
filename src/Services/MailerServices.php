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
    private $platformEmailFrom;

    public function __construct($platformEmailFrom,
                                LoggerInterface $logger,
                                MailerInterface $mailer)
    {
        $this->platformEmailFrom = $platformEmailFrom;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Sends an email with the given reason to all the members of an event.
     */
    public function sendCancellationMail(Event $event, string $reason): void
    {
        $this->logger->info("Appel du service d'envois de mails pour l'annulation d'un évenement. Avec l'ID : {$event->getId()}");

        $organizerEmail = new Email();
        $organizerEmail
            ->from($this->platformEmailFrom)
            ->to($event->getOrganizer()->getMail())
            ->subject("Confirmation de l'annulation d'événement")
            ->text("Votre événement \"{$event->getName()}\" a bien été annulé.");
        $this->mailer->send($organizerEmail);

        foreach ($event->getMembers() as $member) {
            $email = (new Email())
                ->from($this->platformEmailFrom)
                ->to($member->getMail())
                ->subject('Annulation d\'événement')
                ->text($reason);

            $this->mailer->send($email);
        }

    }
}