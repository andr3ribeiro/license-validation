<?php

namespace App\Domain;

/**
 * Brand - Represents a tenant in the system
 *
 * Each brand is an independent tenant with their own products and licenses.
 * Brands authenticate via API keys for provisioning and validation APIs.
 */
class Brand extends Entity
{
    private string $name;
    private string $slug;
    private string $apiKeyBrand;
    private string $apiKeyProduct;
    private string $status; // active, suspended, deleted
    private ?\DateTime $deletedAt;

    public function __construct(
        string $id,
        string $name,
        string $slug,
        string $apiKeyBrand,
        string $apiKeyProduct,
        string $status = 'active',
        \DateTime $createdAt = new \DateTime(),
        \DateTime $updatedAt = new \DateTime(),
        ?\DateTime $deletedAt = null
    ) {
        parent::__construct($id, $createdAt, $updatedAt);
        $this->name = $name;
        $this->slug = $slug;
        $this->apiKeyBrand = $apiKeyBrand;
        $this->apiKeyProduct = $apiKeyProduct;
        $this->status = $status;
        $this->deletedAt = $deletedAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getApiKeyBrand(): string
    {
        return $this->apiKeyBrand;
    }

    public function getApiKeyProduct(): string
    {
        return $this->apiKeyProduct;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'suspended', 'deleted'])) {
            throw new \InvalidArgumentException("Invalid brand status: $status");
        }
        $this->status = $status;
        $this->setUpdatedAt(new \DateTime());
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deletedAt === null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
        $this->status = 'deleted';
        $this->setUpdatedAt(new \DateTime());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'created_at' => $this->createdAt->format(\DateTime::ISO8601),
            'updated_at' => $this->updatedAt->format(\DateTime::ISO8601),
            'deleted_at' => $this->deletedAt?->format(\DateTime::ISO8601),
        ];
    }
}
