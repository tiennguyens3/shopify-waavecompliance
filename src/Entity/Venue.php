<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\VenueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenueRepository::class)]
#[ApiResource]
class Venue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $venue_id;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVenueId(): ?int
    {
        return $this->venue_id;
    }

    public function setVenueId(int $venue_id): self
    {
        $this->venue_id = $venue_id;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
