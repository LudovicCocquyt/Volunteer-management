<?php
namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    private MailerInterface $mailer;

    private $appSendingEmail;

    private \DateTime $date;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $this->appSendingEmail = $_ENV['APP_SENDING_EMAIL'] ?? null;
        $this->date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * Send an email using the Symfony Mailer component.
     *
     * @param array $params
     * @return bool|string Returns true on success, or an error message on failure.
     */
    public function send(array $params): bool | string
    {
        if (!$this->mailer instanceof MailerInterface) {
            return false;
        }

        try {
            $from = new Address($params['from'], 'Apel Saint-François');
            $recipients = $this->normalizeEmails($params['to']);

            $email = (new TemplatedEmail())
                ->from($from)
                ->subject($params['subject'])
                ->htmlTemplate('emails/' . $params['template'] . '.html.twig')
                ->context([
                    "message" => $params['message'] ?? []
                ]);

            // Ajout des destinataires un par un
            foreach ($recipients as $addr) {
                if (!empty($addr)) {
                    $email->addTo(new Address($addr));
                }
            }

            if (!empty($params['cc'])) {
                $email->cc($params['cc']);
            }

            if (!empty($params['replyTo'])) {
                $replyTo = $this->normalizeEmails($params['replyTo']);
                foreach ($replyTo as $addr) {
                    $email->addReplyTo($addr);
                }
            }

            $this->mailer->send($email);
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL SEND] Email envoyé avec succès à ' . $recipients . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return true;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL SEND] Erreur lors de l\'envoi de l\'email à ' . $params['to'] . ': ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return $e->getMessage();
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
                !is_null($this->appSendingEmail) &&
                !is_null($event->getSendingEmail()) &&
                !empty($event->getSendingEmail())
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
                    'from'     => $this->appSendingEmail,
                    'to'       => $event->getSendingEmail(),
                    'replyTo'  => $subscription->getEmail() ?? null,
                    'subject'  => "Nouveau bénévole pour " . $subscription->getEvent()->getName(),
                    'message'  => $text,
                    'template' => 'subscriptions',
                ];
            } else {
                return false;
            }

            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Email admin préparé avec succès à ' . $event->getSendingEmail() . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Erreur lors de la préparation de l\'email à l\'admin: ' . $event->getSendingEmail() . ' - ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../error_log');
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
                !is_null($this->appSendingEmail) &&
                !is_null($event->getSendingEmail()) &&
                !empty($event->getSendingEmail())
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
                    'from'     => $this->appSendingEmail,
                    'to'       => $event->getSendingEmail(),
                    'subject'  => "Échec de l'auto-assignation pour " . $event->getName(),
                    'message'  => $text,
                    'template' => 'auto_assign_failed',
                ];
            }
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Email d\'échec d\'auto-assignation à l\'admin préparé avec succès à ' . $event->getSendingEmail() . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Erreur lors de la préparation de l\'email d\'échec d\'auto-assignation à l\'admin: ' . $event->getSendingEmail() . ' - ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../error_log');
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
                'from'     => $this->appSendingEmail,
                'to'       => $subscription->getEmail(),
                'replyTo'  => $this->appSendingEmail . (!is_null($event->getSendingEmail()) ? ',' . $event->getSendingEmail() : ''),
                'subject'  => "Confirmation d'inscription [" . $event->getName() . "]",
                'message'  => $message,
                'template' => 'volunteer_confirmation',
            ];

            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Email au bénévole préparé avec succès ' . $subscription->getEmail() . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return $params;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            error_log($this->date->format('Y-m-d H:i:s') . ' [MAIL PREPARE] Erreur lors de la préparation de l\'email au bénévole: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../error_log');
            return false;
        }
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