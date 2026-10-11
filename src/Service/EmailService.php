<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private ?string $sending_email_from;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $uploadsDir,
        // Canal dédié : var/log/mail.log (voir config/packages/monolog.yaml)
        #[Autowire(service: 'monolog.logger.mail')]
        private readonly LoggerInterface $mailLogger,
    ) {
        $this->sending_email_from = $_ENV['APP_SENDING_EMAIL_FROM'] ?? null;
    }

    /**
     * Envoie un email via Symfony Mailer et journalise le résultat dans var/log/mail.log.
     *
     * @return bool true si le mail a été pris en charge (mis en file d'envoi), false sinon
     */
    public function send(array $params): bool
    {
        $recipients = $this->normalizeEmails((string) ($params['to'] ?? ''));
        $context    = [
            'to'       => array_map([self::class, 'maskEmail'], $recipients),
            'subject'  => $params['subject'] ?? '',
            'template' => $params['template'] ?? '',
        ];

        if ([] === $recipients) {
            $this->mailLogger->warning('Mail non envoyé : aucun destinataire', $context);

            return false;
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($params['from'], 'Apel Saint-François'))
                ->subject($params['subject'])
                ->htmlTemplate('emails/' . $params['template'] . '.html.twig')
                ->context([
                    'message' => $params['message'] ?? [],
                ]);

            foreach ($recipients as $addr) {
                $email->addTo(new Address($addr));
            }

            if (!empty($params['cc'])) {
                $email->cc($params['cc']);
            }

            if (!empty($params['replyTo'])) {
                foreach ($this->normalizeEmails((string) $params['replyTo']) as $addr) {
                    $email->addReplyTo($addr);
                }
            }

            $attached = 0;
            foreach ($params['attachments'] ?? [] as $file) {
                // $file est une entité Attachment
                $filePath = $this->uploadsDir . '/' . basename($file->getFilename());
                if (is_file($filePath)) {
                    $email->attachFromPath($filePath, $file->getOriginalName());
                    ++$attached;
                } else {
                    $this->mailLogger->warning('Pièce jointe introuvable sur le disque', $context + ['file' => $file->getFilename()]);
                }
            }

            // Avec Messenger, le mail est mis en file d'attente : l'envoi réel (succès ou échec)
            // est journalisé par MailLogSubscriber au moment où le worker l'envoie.
            $this->mailer->send($email);
            $this->mailLogger->info('Mail mis en file d\'envoi', $context + ['attachments' => $attached]);

            return true;
        } catch (\Throwable $e) {
            $this->mailLogger->error('Échec de l\'envoi du mail', $context + [
                'error'     => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    /**
     * Prepare the email to be sent to the admin after subscription.
     *
     * @param Subscriptions $subscription
     * @param Events $event
     * @return array|false
     */
    public function prepareEmailToAdminAfterSubscription($subscription, $event): array|false
    {
        try {
            $params = [];
            // Check if the event has a sending email
            if (
                !is_null($this->sending_email_from) &&
                !is_null($event->getManagerNotification()) &&
                !empty($event->getManagerNotification())
            ) {
                // Prepare the email message
                $availabilityMessage = "";
                foreach ($subscription->getAvailabilities() as $availability) {
                    $startDate = new \DateTime($availability['startDate']);
                    $endDate   = new \DateTime($availability['endDate']);

                    $availabilityMessage .= $startDate->format('d/m/Y \d\e H:i') . " à " . $endDate->format('H:i') . "\n";
                }

                $text = $subscription->getFirstname() . " " . $subscription->getLastname() . " s'est inscrit pour l'événement (" . $subscription->getEvent()->getName() . ")" . ".\n \n" .
                    "Voici le détail des disponibilités:\n" .
                    $availabilityMessage;

                // Params for email
                $params = [
                    'from'     => $this->sending_email_from,
                    'to'       => $event->getManagerNotification(),
                    'replyTo'  => $subscription->getEmail() ?? null,
                    'subject'  => "Nouveau bénévole pour " . $subscription->getEvent()->getName(),
                    'message'  => $text,
                    'template' => 'subscriptions',
                ];
            } else {
                return false;
            }

            $this->mailLogger->debug('Mail admin préparé (nouvelle inscription)', ['event' => $event->getId()]);
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            $this->mailLogger->error('Échec de la préparation du mail admin (nouvelle inscription)', ['event' => $event->getId(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Prepare the email to be sent to the admin if auto-assignment fails.
     *
     * @param Subscriptions $subscription
     * @param Events $event
     * @return array|false
     */
    public function prepareEmailToAdminAutoAssignFailed($subscription, $event): array|false
    {
        try {
            $params = [];
            // Check if the event has a sending email
            if (
                !is_null($this->sending_email_from) &&
                !is_null($event->getManagerNotification()) &&
                !empty($event->getManagerNotification())
            ) {
                // Prepare the email message
                $availabilityMessage = "";
                foreach ($subscription->getAvailabilities() as $availability) {
                    $startDate = new \DateTime($availability['startDate']);
                    $endDate   = new \DateTime($availability['endDate']);

                    $availabilityMessage .= $startDate->format('d/m/Y \d\e H:i') . " à " . $endDate->format('H:i') . "\n";
                }

                $text = "L'auto-assignation du bénévole " . $subscription->getFirstname() . " " . $subscription->getLastname() . " a échoué pour l'événement " .    $event->getName() . ".\n \n" .
                "Voici le détail des disponibilités:\n" .
                $availabilityMessage;

                // Params for email
                $params = [
                    'from'     => $this->sending_email_from,
                    'to'       => $event->getManagerNotification(),
                    'subject'  => "Échec de l'auto-assignation pour " . $event->getName(),
                    'message'  => $text,
                    'template' => 'auto_assign_failed',
                ];
            }
            $this->mailLogger->debug('Mail admin préparé (échec d\'auto-affectation)', ['event' => $event->getId()]);
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            $this->mailLogger->error('Échec de la préparation du mail admin (auto-affectation)', ['event' => $event->getId(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Prepare the email to be sent to the volunteer after subscription.
     *
     * @param Subscriptions $subscription
     * @param Events $event
     * @return array|false
     */
    public function prepareEmailToVolunteerAfterSubscription($subscription, $event): array|false
    {
        try {
            // Prepare the email message
            $availabilityMessage = "";
            foreach ($subscription->getAvailabilities() as $availability) {
                $startDate = new \DateTime($availability['startDate']);
                $endDate   = new \DateTime($availability['endDate']);

                $availabilityMessage .= $startDate->format('d/m/Y \d\e H:i') . " à " . $endDate->format('H:i') . "\n";
            }
            if (!is_null($event->getMessageEmail()) && !empty($event->getMessageEmail())) {
                // Remplace les balises <p> par des sauts de ligne
                $textWithLineBreaks = str_replace('</p>', "\r\n", $event->getMessageEmail());
                $textWithLineBreaks = str_replace('<br>', "\n", $textWithLineBreaks);
                // Supprime les autres balises HTML s'il y en a
                $cleanText = strip_tags($textWithLineBreaks);
                // Décode les entités HTML
                $decodedText = html_entity_decode($cleanText);
                $message =
                    html_entity_decode($decodedText) .
                    "\n\n Voici le détail de vos disponibilités:\n" .
                    $availabilityMessage;
            } else {
                // Default message
                $message =
                    "Bonjour,\n\n" .
                    "Merci pour votre inscription à l'événement \"" . $event->getName() . " du " . $event->getStartAt()->format('d/m/Y') . " à " . $event->getStartAt()->format('H:i') . "\".\n\n" .
                    "Nous avons bien reçu vos disponibilités et nous vous contacterons bientôt avec plus de détails.\n\n" .
                    "Nous comptons sur votre participation.\n" .
                    "En cas de modifications, merci de nous prévenir au plus vite, afin que nous puissions mettre à jour le planning.\n\n" .
                    "Cordialement,\n" .
                    "Les bénévoles de l'APEL.\n\n\n".
                    "Voici le détail de vos disponibilités:\n" .
                    $availabilityMessage;
            }

            // Params for email
            $params = [
                'from'        => $this->sending_email_from,
                'to'          => $subscription->getEmail(),
                'replyTo'     => $this->sending_email_from . (!is_null($event->getManagerNotification()) ? ',' . $event->getManagerNotification() : ''),
                'subject'     => "Confirmation d'inscription [" . $event->getName() . "]",
                'message'     => $message,
                'template'    => 'volunteer_confirmation',
                'attachments' => $event->getAttachments() ?? []
            ];

            $this->mailLogger->debug('Mail bénévole préparé', ['subscription' => $subscription->getId()]);
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            $this->mailLogger->error('Échec de la préparation du mail bénévole', ['subscription' => $subscription->getId(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * jean.dupont@gmail.com -> je***@gmail.com : les logs n'ont pas besoin des adresses complètes.
     */
    public static function maskEmail(string $email): string
    {
        $at = strrpos($email, '@');
        if (false === $at) {
            return '***';
        }

        return mb_substr($email, 0, min(2, $at)) . '***' . substr($email, $at);
    }

    private function normalizeEmails(string $rawEmails): array
    {
        $emails = explode(',', $rawEmails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails); // supprime les vides
        $emails = array_unique($emails); // supprime les doublons

        return $emails;
    }
}