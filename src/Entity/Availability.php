<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Une disponibilité d'un bénévole sur un horaire, et son éventuelle affectation.
 *
 * Source unique de vérité : plan = null -> disponible, plan renseigné -> affecté à ce créneau.
 * Remplace le JSON Subscriptions::availabilities et la relation ManyToMany Subscriptions <-> Plans.
 */
#[ORM\Entity]
#[ORM\Table(name: 'availability')]
#[ORM\UniqueConstraint(name: 'availability_slot_unique', columns: ['subscription_id', 'start_date', 'end_date'])]
class Availability
{
    public const JSON_FORMAT = 'Y-m-d\TH:i:s';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'availabilitySlots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Subscriptions $subscription = null;

    // Créneau supprimé -> la disponibilité redevient libre (SET NULL)
    #[ORM\ManyToOne(inversedBy: 'availabilities')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Plans $plan = null;

    public function __construct(?Subscriptions $subscription = null, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null)
    {
        $this->subscription = $subscription;
        $this->startDate    = $startDate;
        $this->endDate      = $endDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    #[Ignore]
    public function getSubscription(): ?Subscriptions
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscriptions $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    #[Ignore]
    public function getPlan(): ?Plans
    {
        return $this->plan;
    }

    public function setPlan(?Plans $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function isAssigned(): bool
    {
        return null !== $this->plan;
    }

    /** Même horaire, à la seconde près (heure locale, sans tenir compte du fuseau de l'objet). */
    public function matches(\DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        return self::key($this->startDate, $this->endDate) === self::key($start, $end);
    }

    public static function key(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        return $start->format('Y-m-d H:i:s') . '|' . $end->format('Y-m-d H:i:s');
    }

    /** Format historique (ancien JSON) attendu par le front, les templates et les mails. */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate->format(self::JSON_FORMAT),
            'endDate'   => $this->endDate->format(self::JSON_FORMAT),
            'available' => !$this->isAssigned(),
            'planId'    => $this->plan?->getId(),
        ];
    }
}
