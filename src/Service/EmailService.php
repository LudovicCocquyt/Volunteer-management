<?php
namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    private MailerInterface $mailer;

    private $appSendingEmail;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $this->appSendingEmail = $_ENV['APP_SENDING_EMAIL'] ?? null;
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
            $email = (new TemplatedEmail())
            ->from($params['from'])
            ->to(str_replace(" ", "", $params['to']))
            ->subject($params['subject'])
            ->htmlTemplate('emails/' . $params['template'] . '.html.twig')
            ->context([
                "message" => $params['message'] ?? []
            ]);

            if (!empty($params['cc'])) {
                $email->cc($params['cc']);
            }

            if (!empty($params['replyTo'])) {
                $email->replyTo($params['replyTo']);
            }

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            return $e->getMessage();
        }
    }

    /**
     * Prepare the email to be sent to the new volunteer after subscription.
     *
     * @param Subscriptions $subscription
     * @param Events $event
     * @return array
     */
    public function prepareEmailToNewVolunteer($subscription, $event): array
    {
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
                "Voici les détails des disponibilités:\n" .
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
        }

        return $params;
    }
}
