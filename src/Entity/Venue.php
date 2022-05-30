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
    private $shop_id;

    #[ORM\Column(type: 'integer')]
    private $venue_id;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[ORM\Column(type: 'string', length: 255)]
    private $access_key;

    #[ORM\Column(type: 'boolean')]
    private $is_kratom;

    #[ORM\Column(type: 'datetime_immutable')]
    private $created_at;

    #[ORM\Column(type: 'datetime_immutable')]
    private $updated_at;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): ?int
    {
        return $this->shop_id;
    }

    public function setShopId(int $shop_id): self
    {
        $this->shop_id = $shop_id;

        return $this;
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

    public function getAccessKey(): ?string
    {
        return $this->access_key;
    }

    public function setAccessKey(string $access_key): self
    {
        $this->access_key = $access_key;

        return $this;
    }

    public function isIsKratom(): ?bool
    {
        return $this->is_kratom;
    }

    public function setIsKratom(bool $is_kratom): self
    {
        $this->is_kratom = $is_kratom;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
