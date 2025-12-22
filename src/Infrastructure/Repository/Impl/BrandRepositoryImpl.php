<?php

namespace App\Infrastructure\Repository\Impl;

use App\Domain\Brand;
use App\Infrastructure\Database;
use App\Infrastructure\Repository\BrandRepository as BrandRepositoryInterface;

/**
 * MySQL implementation of BrandRepository
 */
class BrandRepositoryImpl implements BrandRepositoryInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function save(Brand $brand): void
    {
        $stmt = $this->db->query(
            "INSERT INTO brands (id, name, slug, api_key_brand, api_key_product, status, created_at, updated_at, deleted_at) 
             VALUES (:id, :name, :slug, :api_key_brand, :api_key_product, :status, :created_at, :updated_at, :deleted_at)
             ON DUPLICATE KEY UPDATE 
             name = VALUES(name), 
             status = VALUES(status), 
             updated_at = VALUES(updated_at), 
             deleted_at = VALUES(deleted_at)",
            [
                ':id' => $brand->getId(),
                ':name' => $brand->getName(),
                ':slug' => $brand->getSlug(),
                ':api_key_brand' => $brand->getApiKeyBrand(),
                ':api_key_product' => $brand->getApiKeyProduct(),
                ':status' => $brand->getStatus(),
                ':created_at' => $brand->getCreatedAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $brand->getUpdatedAt()->format('Y-m-d H:i:s'),
                ':deleted_at' => $brand->getDeletedAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(string $id): ?Brand
    {
        $result = $this->db->query(
            "SELECT * FROM brands WHERE id = :id",
            [':id' => $id]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findBySlug(string $slug): ?Brand
    {
        $result = $this->db->query(
            "SELECT * FROM brands WHERE slug = :slug AND deleted_at IS NULL",
            [':slug' => $slug]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByApiKeyBrand(string $apiKey): ?Brand
    {
        $result = $this->db->query(
            "SELECT * FROM brands WHERE api_key_brand = :api_key AND deleted_at IS NULL",
            [':api_key' => $apiKey]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByApiKeyProduct(string $apiKey): ?Brand
    {
        $result = $this->db->query(
            "SELECT * FROM brands WHERE api_key_product = :api_key AND deleted_at IS NULL",
            [':api_key' => $apiKey]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findAllActive(): array
    {
        $results = $this->db->query(
            "SELECT * FROM brands WHERE status = 'active' AND deleted_at IS NULL ORDER BY created_at DESC"
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    private function hydrate(array $row): Brand
    {
        return new Brand(
            $row['id'],
            $row['name'],
            $row['slug'],
            $row['api_key_brand'],
            $row['api_key_product'],
            $row['status'],
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at']),
            $row['deleted_at'] ? new \DateTime($row['deleted_at']) : null
        );
    }
}
