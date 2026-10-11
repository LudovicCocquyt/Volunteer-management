<?php

namespace App\Entity;

use App\Repository\PlansRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

// | Id int                         |
// | StartDate datetime             |
// | EndDate datetime               |
// | NbPers int (nullable)          |
// | EventId int (ManyToOne)        |
// | Availabilities (OneToMany)    | bénévoles affectés
// | ActivityName varchar(255)      | (nullable)
// | Information varchar(15)        | (nullable)

#[ORM\Entity(repositoryClass: PlansRepository::class)]
class Plans
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: "L'heure de fin doit être après l'heure de début.")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 999, notInRangeMessage: 'Le nombre de personnes doit être compris entre {{ min }} et {{ max }}.')]
    private ?int $nbPers = null;

    #[ORM\ManyToOne(inversedBy: 'plans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'événement est obligatoire.")]
    private ?Events $event = null;

    /**
     * Disponibilités affectées à ce créneau (voir Availability).
     *
     * @var Collection<int, Availability>
     */
    #[ORM\OneToMany(targetEntity: Availability::class, mappedBy: 'plan')]
    private Collection $availabilities;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'activité est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom de l'activité ne doit pas dépasser {{ limit }} caractères.")]
    private ?string $activityName = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(max: 10, maxMessage: "L'information ne doit pas dépasser {{ limit }} caractères.")]
    private ?string $information = null;

    public function __construct()
    {
        $this->availabilities = new ArrayCollection();
    }

    /**
     * @return Collection<int, Availability>
     */
    #[Ignore]
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    /**
     * Bénévoles affectés à ce créneau (déduits des disponibilités).
     *
     * @return Collection<int, Subscriptions>
     */
    public function getSubscriptions(): Collection
    {
        $subscriptions = new ArrayCollection();
        foreach ($this->availabilities as $slot) {
            if ($slot->getSubscription() && !$subscriptions->contains($slot->getSubscription())) {
                $subscriptions->add($slot->getSubscription());
            }
        }

        return $subscriptions;
    }

    /**
     * Affecte le bénévole : sa disponibilité sur cet horaire (créée si besoin) pointe vers ce créneau.
     * Un bénévole ne peut être affecté qu'à un seul créneau par horaire.
     */
    public function addSubscription(Subscriptions $subscription): static
    {
        $slot     = $subscription->slotFor($this->startDate, $this->endDate);
        $previous = $slot->getPlan();

        if ($previous !== $this) {
            $previous?->getAvailabilities()->removeElement($slot);
            $slot->setPlan($this);
            $this->availabilities->add($slot);
        }

        return $this;
    }

    /** Retire l'affectation ; la disponibilité est conservée (le bénévole reste disponible). */
    public function removeSubscription(Subscriptions $subscription): static
    {
        foreach ($this->availabilities->toArray() as $slot) {
            if ($slot->getSubscription() === $subscription) {
                $slot->setPlan(null);
                $this->availabilities->removeElement($slot);
            }
        }

        return $this;
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

    public function getNbPers(): ?int
    {
        return $this->nbPers;
    }

    public function setNbPers(?int $nbPers): static
    {
        $this->nbPers = $nbPers;

        return $this;
    }

    public function getEvent(): ?Events
    {
        return $this->event;
    }

    public function setEvent(?Events $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getActivityName(): ?string
    {
        return $this->activityName;
    }

    public function setActivityName(?string $activityName): static
    {
        $this->activityName = $activityName;

        return $this;
    }

    public function getInformation(): ?string
    {
        return $this->information;
    }

    public function setInformation(?string $information): static
    {
        $this->information = $information;

        return $this;
    }
}