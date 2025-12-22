<?php

namespace App\Domain;

/**
 * Product - Represents a product that can be licensed
 * 
 * Products belong to a specific brand. Each product is uniquely
 * identified by (brand_id, slug) combination.
 */
class Product extends Entity
{
    private string $brandId;
    private string $name;
    private string $slug;
    private string $description;
    private string $status; // active, inactive

    public function __construct(
        string $id,
        string $brandId,
        string $name,
        string $slug,
        string $description = '',
        string $status = 'active',
        \DateTime $createdAt = new \DateTime(),
        \DateTime $updatedAt = new \DateTime()
    ) {
        parent::__construct($id, $createdAt, $updatedAt);
        $this->brandId = $brandId;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->status = $status;
    }

    public function getBrandId(): string
    {
        return $this->brandId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new \InvalidArgumentException("Invalid product status: $status");
        }
        $this->status = $status;
        $this->setUpdatedAt(new \DateTime());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brandId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt->format(\DateTime::ISO8601),
            'updated_at' => $this->updatedAt->format(\DateTime::ISO8601),
        ];
    }
}
