<?php

namespace App\Domain;

/**
 * LicenseKey - Represents a unique key given to customers
 * 
 * One license key can unlock multiple licenses (for different products
 * or different subscriptions of the same brand).
 * 
 * Example: Customer buys RankMath Pro + Content AI addon = 1 key, 2 licenses
 */
class LicenseKey extends Entity
{
    private string $brandId;
    private string $customerEmail;
    private string $key;
    private string $status; // active, inactive, cancelled
    private string $createdByBrandId;

    public function __construct(
        string $id,
        string $brandId,
        string $customerEmail,
        string $key,
        string $createdByBrandId,
        string $status = 'active',
        \DateTime $createdAt = new \DateTime(),
        \DateTime $updatedAt = new \DateTime()
    ) {
        parent::__construct($id, $createdAt, $updatedAt);
        $this->brandId = $brandId;
        $this->customerEmail = $customerEmail;
        $this->key = $key;
        $this->status = $status;
        $this->createdByBrandId = $createdByBrandId;
    }

    public function getBrandId(): string
    {
        return $this->brandId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedByBrandId(): string
    {
        return $this->createdByBrandId;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive', 'cancelled'])) {
            throw new \InvalidArgumentException("Invalid license key status: $status");
        }
        $this->status = $status;
        $this->setUpdatedAt(new \DateTime());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function suspend(): void
    {
        if ($this->status !== 'active') {
            throw new \LogicException("Cannot suspend license key that is not active");
        }
        $this->setStatus('inactive');
    }

    public function reactivate(): void
    {
        if ($this->status !== 'inactive') {
            throw new \LogicException("Cannot reactivate license key that is not inactive");
        }
        $this->setStatus('active');
    }

    public function cancel(): void
    {
        $this->setStatus('cancelled');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'customer_email' => $this->customerEmail,
            'status' => $this->status,
            'created_at' => $this->createdAt->format(\DateTime::ISO8601),
            'updated_at' => $this->updatedAt->format(\DateTime::ISO8601),
        ];
    }
}
