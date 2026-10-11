<?php

namespace App\EventSubscriber;

use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Journalise l'envoi réel des mails (dans le worker Messenger) dans var/log/mail-*.log.
 */
final class MailLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.mail')]
        private readonly LoggerInterface $mailLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SentMessageEvent::class   => 'onSent',
            FailedMessageEvent::class => 'onFailed',
        ];
    }

    public function onSent(SentMessageEvent $event): void
    {
        $this->mailLogger->info('Mail envoyé', $this->context($event->getMessage()->getOriginalMessage()));
    }

    public function onFailed(FailedMessageEvent $event): void
    {
        $this->mailLogger->error('Échec de l\'envoi du mail', $this->context($event->getMessage()) + [
            'error'     => $event->getError()->getMessage(),
            'exception' => $event->getError()::class,
        ]);
    }

    private function context(RawMessage $message): array
    {
        if (!$message instanceof Email) {
            return [];
        }

        return [
            'to'      => array_map(fn (Address $a) => EmailService::maskEmail($a->getAddress()), $message->getTo()),
            'subject' => $message->getSubject(),
        ];
    }
}
