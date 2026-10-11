<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Création / modification d'un créneau depuis le calendrier (POST /api/plan/new, PUT /api/plan/edit/{id}).
 * Le groupe "create" ne s'applique qu'à la création (l'événement est alors obligatoire).
 */
final class PlanInput
{
    public function __construct(
        #[Assert\NotBlank(message: "L'événement est obligatoire.", groups: ['create'])]
        #[Assert\Regex('/^\d+$/', message: 'Événement invalide.', groups: ['create'])]
        public readonly int|string|null $eventId = null,

        #[Assert\NotBlank(message: "Le nom de l'activité est obligatoire.")]
        #[Assert\Length(max: 255, maxMessage: "Le nom de l'activité ne doit pas dépasser {{ limit }} caractères.")]
        public readonly ?string $resourceId = null,

        #[Assert\NotBlank(message: 'La date est obligatoire.')]
        #[Assert\Regex('/^\d{4}-\d{2}-\d{2}/', message: 'Date invalide.')]
        public readonly ?string $start = null,

        #[Assert\NotBlank(message: "L'heure de début est obligatoire.")]
        #[Assert\Regex('/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure de début invalide (HH:MM).')]
        public readonly ?string $startTime = null,

        #[Assert\NotBlank(message: "L'heure de fin est obligatoire.")]
        #[Assert\Regex('/^([01]\d|2[0-3]):[0-5]\d$/', message: 'Heure de fin invalide (HH:MM).')]
        public readonly ?string $endTime = null,

        // Le calendrier envoie parfois un nombre, parfois une chaîne ("3")
        #[Assert\Regex('/^\d{1,3}$/', message: 'Le nombre de personnes doit être un entier entre 0 et 999.')]
        public readonly int|string|null $nbPers = 0,

        #[Assert\Length(max: 10, maxMessage: "L'information ne doit pas dépasser {{ limit }} caractères.")]
        public readonly ?string $information = null,
    ) {
    }

    #[Assert\IsTrue(message: "L'heure de fin doit être après l'heure de début.")]
    public function isEndAfterStart(): bool
    {
        // Les formats sont vérifiés séparément ; "HH:MM" se compare comme une chaîne
        return null === $this->startTime || null === $this->endTime || $this->endTime > $this->startTime;
    }

    public function nbPers(): int
    {
        return (int) ($this->nbPers ?? 0);
    }
}
