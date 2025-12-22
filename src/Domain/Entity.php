<?php

namespace App\Domain;

/**
 * Base Entity class for all domain models
 */
abstract class Entity
{
    protected string $id;
    protected \DateTime $createdAt;
    protected \DateTime $updatedAt;

    public function __construct(string $id, \DateTime $createdAt, \DateTime $updatedAt)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Convert entity to array for API responses
     */
    abstract public function toArray(): array;
}
