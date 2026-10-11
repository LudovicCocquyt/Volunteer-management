<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Recherche des bénévoles disponibles sur un horaire (POST /api/subscriptions).
 */
final class SlotSearchInput
{
    public function __construct(
        #[Assert\NotBlank(message: "L'événement est obligatoire.")]
        #[Assert\Regex('/^\d+$/', message: 'Événement invalide.')]
        public readonly int|string|null $eventId = null,

        #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
        public readonly ?string $start = null,

        public readonly ?string $end = null,
    ) {
    }

    #[Assert\IsTrue(message: 'Date de début invalide.')]
    public function isStartValid(): bool
    {
        return null === $this->start || null !== $this->startDate();
    }

    public function startDate(): ?\DateTimeImmutable
    {
        try {
            return null === $this->start ? null : new \DateTimeImmutable($this->start);
        } catch (\Exception) {
            return null;
        }
    }
}
