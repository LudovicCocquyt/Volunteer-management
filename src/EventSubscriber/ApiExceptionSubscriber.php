<?php

namespace App\EventSubscriber;

use App\Http\ApiError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Erreurs HTTP de l'API (/api/...) renvoyées dans le format JSON commun (voir ApiError),
 * avec des messages en français et sans détail technique.
 *
 * - Données invalides (#[MapRequestPayload]) -> 422 + liste des champs en erreur
 * - JSON illisible                              -> 400
 * - Ressource introuvable                       -> 404
 *
 * Les erreurs de droits sont laissées à la sécurité Symfony, et les erreurs 500
 * au gestionnaire standard (journalisées dans var/log/prod-*.log).
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // Après la sécurité (1) et la journalisation (0), avant la page d'erreur standard (-128)
        return [KernelEvents::EXCEPTION => ['onException', -64]];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $event->setResponse(ApiError::fromViolations($previous->getViolations()));

            return;
        }

        $status  = $exception->getStatusCode();
        $message = match (true) {
            400 === $status => 'Requête invalide.',
            404 === $status => 'Élément introuvable.',
            405 === $status => 'Méthode non autorisée.',
            422 === $status => 'Données invalides.',
            $status >= 500  => 'Erreur interne, réessayez plus tard.',
            default         => 'Requête refusée.',
        };

        $event->setResponse(ApiError::response($message, $status));
    }
}
