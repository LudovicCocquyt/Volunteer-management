<?php

namespace App\Entity;

use App\Repository\EventsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// | Id int                        |
// | Name string                   |
// | StartAt datetime              |
// | Location string               |
// | Published bool                |
// | Archived bool                 |
// | Description text              |
// | OneToManyPlans array          |
// | OneToMany Subscriptions array |
// | StartCalendar datetime        |
// | EndCalendar datetime          |

#[ORM\Entity(repositoryClass: EventsRepository::class)]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\Column]
    private ?bool $archived = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Plans>
     */
    #[ORM\OneToMany(targetEntity: Plans::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $plans;

    /**
     * @var Collection<int, Subscriptions>
     */
    #[ORM\OneToMany(targetEntity: Subscriptions::class, mappedBy: 'event')]
    private Collection $subscriptions;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startCalendar = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endCalendar = null;

    public function __construct()
    {
        $this->plans         = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Array<int, Plans>
     */
    public function getPlans(): array
    {
        return $this->plans->toArray();
    }

    public function addPlan(Plans $plan): static
    {
        if (!$this->plans->contains($plan)) {
            $this->plans->add($plan);
            $plan->setEvent($this);
        }

        return $this;
    }

    public function removePlan(Plans $plan): static
    {
        if ($this->plans->removeElement($plan)) {
            // set the owning side to null (unless already changed)
            if ($plan->getEvent() === $this) {
                $plan->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscriptions>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscriptions $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setEvent($this);
        }

        return $this;
    }

    public function removeSubscription(Subscriptions $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getEvent() === $this) {
                $subscription->setEvent(null);
            }
        }

        return $this;
    }

    public function getStartCalendar(): ?\DateTimeInterface
    {
        return $this->startCalendar;
    }

    public function setStartCalendar(?\DateTimeInterface $startCalendar): static
    {
        $this->startCalendar = $startCalendar;

        return $this;
    }

    public function getEndCalendar(): ?\DateTimeInterface
    {
        return $this->endCalendar;
    }

    public function setEndCalendar(?\DateTimeInterface $endCalendar): static
    {
        $this->endCalendar = $endCalendar;

        return $this;
    }
}
