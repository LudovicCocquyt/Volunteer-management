<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Affectation / retrait d'un bénévole sur un créneau (PUT /api/plan/volunteer).
 */
final class VolunteerAssignmentInput
{
    public function __construct(
        #[Assert\NotNull(message: "L'action (affecter ou retirer) est obligatoire.")]
        public readonly ?bool $assign = null,

        #[Assert\NotBlank(message: 'Le créneau est obligatoire.')]
        #[Assert\Regex('/^\d+$/', message: 'Créneau invalide.')]
        public readonly int|string|null $planId = null,

        #[Assert\NotNull(message: 'Le bénévole est obligatoire.')]
        #[Assert\Collection(
            fields: ['id' => [new Assert\NotBlank(message: 'Le bénévole est obligatoire.'), new Assert\Regex('/^\d+$/', message: 'Bénévole invalide.')]],
            allowExtraFields: true,
        )]
        public readonly ?array $subscription = null,
    ) {
    }

    public function planId(): int
    {
        return (int) $this->planId;
    }

    public function subscriptionId(): int
    {
        return (int) ($this->subscription['id'] ?? 0);
    }
}
