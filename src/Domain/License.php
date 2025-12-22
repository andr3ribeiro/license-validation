<?php

namespace App\Domain;

/**
 * License - Represents an entitlement to a product
 * 
 * A license grants access to a specific product for a customer.
 * One license key can have multiple licenses (one per product).
 */
class License extends Entity
{
    private string $licenseKeyId;
    private string $productId;
    private string $status; // valid, suspended, cancelled, expired
    private \DateTime $startsAt;
    private \DateTime $expiresAt;
    private ?\DateTime $activatedAt;

    public function __construct(
        string $id,
        string $licenseKeyId,
        string $productId,
        \DateTime $startsAt,
        \DateTime $expiresAt,
        string $status = 'valid',
        ?\DateTime $activatedAt = null,
        \DateTime $createdAt = new \DateTime(),
        \DateTime $updatedAt = new \DateTime()
    ) {
        parent::__construct($id, $createdAt, $updatedAt);
        
        if ($expiresAt <= $startsAt) {
            throw new \InvalidArgumentException("Expiration date must be after start date");
        }

        $this->licenseKeyId = $licenseKeyId;
        $this->productId = $productId;
        $this->status = $status;
        $this->startsAt = $startsAt;
        $this->expiresAt = $expiresAt;
        $this->activatedAt = $activatedAt;
    }

    public function getLicenseKeyId(): string
    {
        return $this->licenseKeyId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartsAt(): \DateTime
    {
        return $this->startsAt;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function getActivatedAt(): ?\DateTime
    {
        return $this->activatedAt;
    }

    /**
     * Check if license is currently valid (not expired and active)
     */
    public function isValid(): bool
    {
        $now = new \DateTime();
        
        if ($this->status !== 'valid') {
            return false;
        }

        if ($now < $this->startsAt || $now > $this->expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Check if license has expired
     */
    public function isExpired(): bool
    {
        $now = new \DateTime();
        return $now > $this->expiresAt;
    }

    /**
     * Check if license can be activated
     */
    public function canActivate(): bool
    {
        return $this->activatedAt === null && $this->status === 'valid';
    }

    /**
     * Activate the license (mark when product first validated it)
     */
    public function activate(): void
    {
        if (!$this->canActivate()) {
            throw new \LogicException("License cannot be activated in its current state");
        }
        $this->activatedAt = new \DateTime();
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Check if license can be suspended
     */
    public function canSuspend(): bool
    {
        return $this->status === 'valid';
    }

    /**
     * Suspend the license
     */
    public function suspend(): void
    {
        if (!$this->canSuspend()) {
            throw new \LogicException("Cannot suspend license in {$this->status} status");
        }
        $this->status = 'suspended';
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Reactivate a suspended license
     */
    public function reactivate(): void
    {
        if ($this->status !== 'suspended') {
            throw new \LogicException("Can only reactivate suspended licenses");
        }
        $this->status = 'valid';
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Cancel the license (permanent)
     */
    public function cancel(): void
    {
        if ($this->status === 'cancelled') {
            throw new \LogicException("License is already cancelled");
        }
        $this->status = 'cancelled';
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Mark license as expired
     */
    public function markExpired(): void
    {
        $this->status = 'expired';
        $this->setUpdatedAt(new \DateTime());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'license_key_id' => $this->licenseKeyId,
            'product_id' => $this->productId,
            'status' => $this->status,
            'starts_at' => $this->startsAt->format(\DateTime::ISO8601),
            'expires_at' => $this->expiresAt->format(\DateTime::ISO8601),
            'activated_at' => $this->activatedAt?->format(\DateTime::ISO8601),
            'created_at' => $this->createdAt->format(\DateTime::ISO8601),
            'updated_at' => $this->updatedAt->format(\DateTime::ISO8601),
        ];
    }
}
