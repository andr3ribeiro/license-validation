<?php

namespace App\Infrastructure\Repository\Impl;

use App\Domain\Product;
use App\Infrastructure\Database;
use App\Infrastructure\Repository\ProductRepository as ProductRepositoryInterface;

/**
 * MySQL implementation of ProductRepository
 */
class ProductRepositoryImpl implements ProductRepositoryInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function save(Product $product): void
    {
        $this->db->query(
            "INSERT INTO products (id, brand_id, name, slug, description, status, created_at, updated_at) 
             VALUES (:id, :brand_id, :name, :slug, :description, :status, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE 
             name = VALUES(name), 
             slug = VALUES(slug), 
             description = VALUES(description), 
             status = VALUES(status), 
             updated_at = VALUES(updated_at)",
            [
                ':id' => $product->getId(),
                ':brand_id' => $product->getBrandId(),
                ':name' => $product->getName(),
                ':slug' => $product->getSlug(),
                ':description' => $product->getDescription(),
                ':status' => $product->getStatus(),
                ':created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(string $id): ?Product
    {
        $result = $this->db->query(
            "SELECT * FROM products WHERE id = :id",
            [':id' => $id]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByBrandAndSlug(string $brandId, string $slug): ?Product
    {
        $result = $this->db->query(
            "SELECT * FROM products WHERE brand_id = :brand_id AND slug = :slug",
            [':brand_id' => $brandId, ':slug' => $slug]
        )->fetch();

        return $result ? $this->hydrate($result) : null;
    }

    public function findByBrandId(string $brandId): array
    {
        $results = $this->db->query(
            "SELECT * FROM products WHERE brand_id = :brand_id ORDER BY created_at DESC",
            [':brand_id' => $brandId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    public function findActiveByBrandId(string $brandId): array
    {
        $results = $this->db->query(
            "SELECT * FROM products WHERE brand_id = :brand_id AND status = 'active' ORDER BY created_at DESC",
            [':brand_id' => $brandId]
        )->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    private function hydrate(array $row): Product
    {
        return new Product(
            $row['id'],
            $row['brand_id'],
            $row['name'],
            $row['slug'],
            $row['description'] ?? '',
            $row['status'],
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}
