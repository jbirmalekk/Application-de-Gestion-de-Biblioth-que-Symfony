<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupons')]
#[ORM\UniqueConstraint(name: 'UNIQ_COUPON_CODE', fields: ['code'])]
class Coupon
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_PERCENTAGE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valeur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantMinimum = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(nullable: true)]
    private ?int $usageMax = null;

    #[ORM\Column]
    private int $usageActuel = 0;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;
        return $this;
    }

    public function getMontantMinimum(): ?string
    {
        return $this->montantMinimum;
    }

    public function setMontantMinimum(?string $montantMinimum): static
    {
        $this->montantMinimum = $montantMinimum;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeImmutable $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getUsageMax(): ?int
    {
        return $this->usageMax;
    }

    public function setUsageMax(?int $usageMax): static
    {
        $this->usageMax = $usageMax;
        return $this;
    }

    public function getUsageActuel(): int
    {
        return $this->usageActuel;
    }

    public function setUsageActuel(int $usageActuel): static
    {
        $this->usageActuel = $usageActuel;
        return $this;
    }

    public function incrementUsage(): static
    {
        $this->usageActuel++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Vérifier si le coupon est valide (actif, non expiré, usage disponible)
     */
    public function isValid(): bool
    {
        if (!$this->actif) {
            return false;
        }

        if ($this->dateExpiration && $this->dateExpiration < new \DateTimeImmutable()) {
            return false;
        }

        if ($this->usageMax !== null && $this->usageActuel >= $this->usageMax) {
            return false;
        }

        return true;
    }

    /**
     * Calculer la remise pour un montant donné
     */
    public function calculerRemise(float $montant): float
    {
        if (!$this->isValid()) {
            return 0.0;
        }

        if ($this->montantMinimum && $montant < (float) $this->montantMinimum) {
            return 0.0;
        }

        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                return round($montant * ((float) $this->valeur / 100), 2);
            case self::TYPE_FIXED:
                return min((float) $this->valeur, $montant);
            case self::TYPE_FREE_SHIPPING:
                return 0.0; // Géré séparément
            default:
                return 0.0;
        }
    }

    public function __toString(): string
    {
        return $this->code ?? 'Coupon';
    }
}

