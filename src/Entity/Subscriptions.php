<?php

namespace App\Entity;

use App\Repository\SubscriptionsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: SubscriptionsRepository::class)]
class Subscriptions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    #[Assert\Length(max: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex('/^[0-9+().\s-]{6,25}$/', message: "Le numéro de téléphone n'est pas valide.")]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'enfant est obligatoire.")]
    #[Assert\Length(max: 255)]
    private ?string $childName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La classe est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $class = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    private ?Events $event = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Le commentaire ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $comment = null;

    /**
     * Disponibilités du bénévole et leurs affectations (voir Availability).
     *
     * @var Collection<int, Availability>
     */
    #[ORM\OneToMany(targetEntity: Availability::class, mappedBy: 'subscription', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['startDate' => 'ASC'])]
    #[Assert\Valid]
    private Collection $availabilitySlots;

    public function __construct()
    {
        $this->availabilitySlots = new ArrayCollection();
    }

    /**
     * @return Collection<int, Availability>
     */
    #[Ignore]
    public function getAvailabilitySlots(): Collection
    {
        return $this->availabilitySlots;
    }

    /** Disponibilité sur cet horaire, créée si besoin. */
    public function slotFor(\DateTimeInterface $start, \DateTimeInterface $end, bool $create = true): ?Availability
    {
        foreach ($this->availabilitySlots as $slot) {
            if ($slot->matches($start, $end)) {
                return $slot;
            }
        }

        if (!$create) {
            return null;
        }

        $slot = new Availability($this, \DateTime::createFromInterface($start), \DateTime::createFromInterface($end));
        $this->availabilitySlots->add($slot);

        return $slot;
    }

    /**
     * Créneaux auxquels le bénévole est affecté (déduits des disponibilités).
     *
     * @return Collection<int, Plans>
     */
    public function getPlans(): Collection
    {
        $plans = new ArrayCollection();
        foreach ($this->availabilitySlots as $slot) {
            if ($slot->getPlan() && !$plans->contains($slot->getPlan())) {
                $plans->add($slot->getPlan());
            }
        }

        return $plans;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getChildName(): ?string
    {
        return $this->childName;
    }

    public function setChildName(string $childName): static
    {
        $this->childName = $childName;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

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

    /**
     * Disponibilités au format historique (tableaux startDate / endDate / available),
     * utilisé par le front, les templates, les mails et les comptages.
     */
    public function getAvailabilities(): array
    {
        $slots = $this->availabilitySlots->toArray();
        usort($slots, fn (Availability $a, Availability $b) => $a->getStartDate() <=> $b->getStartDate());

        return array_map(fn (Availability $slot) => $slot->toArray(), $slots);
    }

    /**
     * Synchronise les disponibilités avec la liste reçue du formulaire :
     * horaires ajoutés -> créés, horaires retirés -> supprimés (avec leur affectation),
     * horaires conservés -> affectation conservée. Le champ "available" reçu est ignoré :
     * l'affectation ne se modifie que depuis le planning.
     */
    public function setAvailabilities(?array $availabilities): static
    {
        $wanted = [];
        foreach ($availabilities ?? [] as $item) {
            if (!is_array($item) || empty($item['startDate']) || empty($item['endDate'])) {
                continue;
            }
            try {
                $start = new \DateTime($item['startDate']);
                $end   = new \DateTime($item['endDate']);
            } catch (\Exception) {
                continue;
            }
            $wanted[Availability::key($start, $end)] = [$start, $end];
        }

        foreach ($this->availabilitySlots->toArray() as $slot) {
            $key = Availability::key($slot->getStartDate(), $slot->getEndDate());
            if (isset($wanted[$key])) {
                unset($wanted[$key]); // déjà présente : on la garde telle quelle
            } else {
                $this->availabilitySlots->removeElement($slot); // orphanRemoval -> supprimée en base
            }
        }

        foreach ($wanted as [$start, $end]) {
            $this->availabilitySlots->add(new Availability($this, $start, $end));
        }

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
